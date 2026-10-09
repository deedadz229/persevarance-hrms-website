<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $targetDate = $request->has('date') ? $request->query('date') : now()->toDateString();
        $search = $request->query('search');
        $departmentId = $request->query('department_id');
        $status = $request->query('status');

        $departments = Department::query()->orderBy('name')->get();

        // Calculate daily summary cards for target date (or today if date is cleared)
        $summaryDate = $targetDate ?: now()->toDateString();
        $dateAttendances = Attendance::query()
            ->whereDate('attendance_date', $summaryDate)
            ->get();

        $hadirCount = 0;
        $wfhCount = 0;
        $telatCount = 0;

        foreach ($dateAttendances as $att) {
            $computed = $att->computed_status;
            if ($computed === 'wfh') {
                $wfhCount++;
            } elseif ($computed === 'telat') {
                $telatCount++;
            } else {
                $hadirCount++;
            }
        }

        $totalActiveEmployees = Employee::query()
            ->where('status', 'active')
            ->count();
        $absenCount = max(0, $totalActiveEmployees - $dateAttendances->count());

        $summary = [
            'hadir' => $hadirCount,
            'wfh' => $wfhCount,
            'telat' => $telatCount,
            'absen' => $absenCount,
            'date' => $summaryDate,
        ];

        // If status filter is 'absen', display active employees who have not checked in on target date
        $absentEmployees = null;
        if ($status === 'absen' && $summaryDate) {
            $absentQuery = Employee::query()
                ->with(['department', 'position'])
                ->where('status', 'active')
                ->whereDoesntHave('attendances', function ($q) use ($summaryDate) {
                    $q->whereDate('attendance_date', $summaryDate);
                });

            if ($search) {
                $absentQuery->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%");
                });
            }

            if ($departmentId) {
                $absentQuery->where('department_id', $departmentId);
            }

            $absentEmployees = $absentQuery->paginate(15)->withQueryString();
            $attendances = Attendance::query()->whereRaw('1 = 0')->paginate(15);
        } else {
            $query = Attendance::query()
                ->with(['employee.department', 'employee.position']);

            if ($targetDate) {
                $query->whereDate('attendance_date', $targetDate);
            }

            if ($search) {
                $query->whereHas('employee', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%");
                });
            }

            if ($departmentId) {
                $query->whereHas('employee', function ($q) use ($departmentId) {
                    $q->where('department_id', $departmentId);
                });
            }

            if ($status === 'wfh') {
                $query->where(function ($q) {
                    $q->where('location_status', 'wfh')
                        ->orWhere('notes', 'like', '%wfh%');
                });
            } elseif ($status === 'telat') {
                $query->where(function ($q) {
                    $q->whereTime('check_in', '>', '08:30:00')
                        ->orWhere('location_status', 'late')
                        ->orWhere('notes', 'like', '%telat%');
                })->where(function ($q) {
                    $q->whereNull('location_status')
                        ->orWhere('location_status', '!=', 'wfh');
                })->where(function ($q) {
                    $q->whereNull('notes')
                        ->orWhere('notes', 'not like', '%wfh%');
                });
            } elseif ($status === 'hadir') {
                $query->whereTime('check_in', '<=', '08:30:00')
                    ->where(function ($q) {
                        $q->whereNull('location_status')
                            ->orWhere('location_status', '!=', 'wfh');
                    })
                    ->where(function ($q) {
                        $q->whereNull('notes')
                            ->orWhere('notes', 'not like', '%wfh%');
                    });
            }

            $attendances = $query->latest('attendance_date')
                ->latest('check_in')
                ->paginate(15)
                ->withQueryString();
        }

        return view('attendances.index', [
            'attendances' => $attendances,
            'absentEmployees' => $absentEmployees,
            'departments' => $departments,
            'summary' => $summary,
            'currentDate' => $targetDate,
            'currentSearch' => $search,
            'currentDepartment' => $departmentId,
            'currentStatus' => $status,
        ]);
    }

    public function show(Attendance $attendance): View
    {
        $attendance->loadMissing(['employee.department', 'employee.position']);

        return view('attendances.show', [
            'attendance' => $attendance,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $targetDate = $request->query('date');
        $search = $request->query('search');
        $departmentId = $request->query('department_id');
        $status = $request->query('status');

        $query = Attendance::query()
            ->with(['employee.department', 'employee.position']);

        if ($targetDate) {
            $query->whereDate('attendance_date', $targetDate);
        }

        if ($search) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%");
            });
        }

        if ($departmentId) {
            $query->whereHas('employee', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        if ($status === 'wfh') {
            $query->where(function ($q) {
                $q->where('location_status', 'wfh')
                    ->orWhere('notes', 'like', '%wfh%');
            });
        } elseif ($status === 'telat') {
            $query->where(function ($q) {
                $q->whereTime('check_in', '>', '08:30:00')
                    ->orWhere('location_status', 'late')
                    ->orWhere('notes', 'like', '%telat%');
            });
        } elseif ($status === 'hadir') {
            $query->whereTime('check_in', '<=', '08:30:00')
                ->where(function ($q) {
                    $q->whereNull('location_status')
                        ->orWhere('location_status', '!=', 'wfh');
                });
        }

        $records = $query->latest('attendance_date')->get();

        $filename = 'rekap_absensi_'.($targetDate ?: date('Y-m-d')).'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Tanggal',
                'ID Karyawan',
                'Nama Karyawan',
                'Departemen',
                'Jam Masuk',
                'Jam Keluar',
                'Status Kehadiran',
                'WiFi Terverifikasi',
                'WiFi SSID',
                'IP Address',
                'Latitude',
                'Longitude',
                'Status Lokasi',
                'Status Wajah',
                'Catatan',
            ]);

            foreach ($records as $att) {
                fputcsv($handle, [
                    $att->attendance_date?->format('Y-m-d') ?? '-',
                    $att->employee?->employee_code ?? '-',
                    $att->employee?->name ?? '-',
                    $att->employee?->department?->name ?? '-',
                    $att->check_in?->format('H:i:s') ?? '-',
                    $att->check_out?->format('H:i:s') ?? '-',
                    $att->status_label,
                    $att->wifi_verified ? 'Verified' : 'Not Verified',
                    $att->wifi_ssid ?? '-',
                    $att->ip_address ?? '-',
                    $att->latitude ?? '-',
                    $att->longitude ?? '-',
                    $att->location_status ?? '-',
                    $att->face_status ?? '-',
                    $att->notes ?? '-',
                ]);
            }

            fclose($handle);
        }, Response::HTTP_OK, $headers);
    }
}
