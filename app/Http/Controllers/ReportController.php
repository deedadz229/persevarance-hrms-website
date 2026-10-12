<?php

namespace App\Http\Controllers;

use App\Exports\AttendanceExport;
use App\Exports\AuditLogExport;
use App\Exports\LeaveExport;
use App\Exports\OvertimeExport;
use App\Exports\PayrollExport;
use App\Exports\TurnoverExport;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Overtime;
use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureCanViewReports($request);

        $canExport = $request->user()?->role?->name !== 'taiwan_account'
            && ($request->user()?->role?->name === 'super_admin' || ($request->user()?->role?->permissions->contains('name', 'export_reports') ?? false));

        $canViewAudit = $request->user()?->role?->name !== 'taiwan_account'
            && ($request->user()?->role?->name === 'super_admin' || ($request->user()?->role?->permissions->contains('name', 'view_audit_logs') ?? false));

        return view('reports.index', [
            'canExport' => $canExport,
            'canViewAudit' => $canViewAudit,
        ]);
    }

    // ==========================================
    // 1. LAPORAN ABSENSI
    // ==========================================

    public function attendances(Request $request): View
    {
        $this->ensureCanViewReports($request);

        $month = $request->filled('month') ? (int) $request->query('month') : null;
        $year = $request->filled('year') ? (int) $request->query('year') : null;
        $departmentId = $request->filled('department_id') ? (int) $request->query('department_id') : null;
        $status = $request->query('status');
        $search = $request->query('search');

        $query = $this->buildAttendanceQuery($month, $year, $departmentId, $status, $search);

        // Summary calculations
        $allRecords = (clone $query)->get();

        $totalCount = $allRecords->count();
        $hadirCount = 0;
        $wfhCount = 0;
        $telatCount = 0;

        foreach ($allRecords as $record) {
            $isWfh = $record->location_status === 'wfh' || str_contains(strtolower($record->notes ?? ''), 'wfh');
            $isLate = ($record->check_in && substr($record->check_in, 0, 5) > '08:30') || $record->location_status === 'late' || str_contains(strtolower($record->notes ?? ''), 'telat');

            if ($isWfh) {
                $wfhCount++;
            } elseif ($isLate) {
                $telatCount++;
            } else {
                $hadirCount++;
            }
        }

        $attendances = $query->paginate(20)->withQueryString();

        return view('reports.attendances', [
            'attendances' => $attendances,
            'departments' => Department::orderBy('name')->get(),
            'months' => Payroll::getMonthNames(),
            'currentMonth' => $month,
            'currentYear' => $year,
            'currentDepartment' => $departmentId,
            'currentStatus' => $status,
            'currentSearch' => $search,
            'summary' => [
                'total' => $totalCount,
                'hadir' => $hadirCount,
                'wfh' => $wfhCount,
                'telat' => $telatCount,
            ],
            'canExport' => $this->canExport($request),
        ]);
    }

    public function exportAttendances(Request $request): StreamedResponse|BinaryFileResponse|Response
    {
        $this->ensureCanExportReports($request);

        $format = $this->resolveExportFormat($request);

        $month = $request->filled('month') ? (int) $request->query('month') : null;
        $year = $request->filled('year') ? (int) $request->query('year') : null;
        $departmentId = $request->filled('department_id') ? (int) $request->query('department_id') : null;
        $status = $request->query('status');
        $search = $request->query('search');

        $records = $this->buildAttendanceQuery($month, $year, $departmentId, $status, $search)->get();

        $this->logReportExport($request, 'attendances', $request->query());

        $timestamp = date('Ymd_His');

        if ($format === 'xlsx') {
            return Excel::download(new AttendanceExport($records), "laporan_absensi_{$timestamp}.xlsx");
        }

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.pdf.attendances', ['records' => $records])
                ->setPaper('a4', 'landscape');

            return $pdf->download("laporan_absensi_{$timestamp}.pdf");
        }

        $filename = "laporan_absensi_{$timestamp}.csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'ID Karyawan',
                'Nama Karyawan',
                'Departemen',
                'Tanggal',
                'Jam Masuk',
                'Jam Keluar',
                'Status Kehadiran',
                'WiFi Terverifikasi',
                'Alamat IP',
                'Status Lokasi / GPS',
                'Status Wajah',
                'Catatan',
            ]);

            foreach ($records as $row) {
                $isWfh = $row->location_status === 'wfh' || str_contains(strtolower($row->notes ?? ''), 'wfh');
                $isLate = ($row->check_in && substr($row->check_in, 0, 5) > '08:30') || $row->location_status === 'late' || str_contains(strtolower($row->notes ?? ''), 'telat');
                $statusLabel = $isWfh ? 'WFH' : ($isLate ? 'Terlambat' : 'Hadir Tepat Waktu');

                fputcsv($handle, [
                    $row->employee?->employee_code ?? '-',
                    $row->employee?->name ?? 'Karyawan Dihapus',
                    $row->employee?->department?->name ?? '-',
                    $row->attendance_date ? date('Y-m-d', strtotime($row->attendance_date)) : '-',
                    $row->check_in ? substr($row->check_in, 0, 5) : '-',
                    $row->check_out ? substr($row->check_out, 0, 5) : '-',
                    $statusLabel,
                    $row->wifi_verified ? 'Ya' : 'Tidak',
                    $row->ip_address ?? '-',
                    $row->location_status ?? 'valid',
                    $row->face_verified ? 'Terverifikasi' : 'Tidak Terverifikasi',
                    $row->notes ?? '-',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    private function buildAttendanceQuery(?int $month, ?int $year, ?int $departmentId, ?string $status, ?string $search)
    {
        $query = Attendance::query()
            ->with(['employee.department', 'employee.position'])
            ->latest('attendance_date')
            ->latest('check_in');

        if ($month) {
            $query->whereMonth('attendance_date', $month);
        }

        if ($year) {
            $query->whereYear('attendance_date', $year);
        }

        if ($departmentId) {
            $query->whereHas('employee', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        if ($search) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%");
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
                $q->whereNull('location_status')->orWhere('location_status', '!=', 'wfh');
            });
        } elseif ($status === 'hadir') {
            $query->whereTime('check_in', '<=', '08:30:00')
                ->where(function ($q) {
                    $q->whereNull('location_status')->orWhere('location_status', '!=', 'wfh');
                });
        }

        return $query;
    }

    // ==========================================
    // 2. LAPORAN CUTI & IZIN
    // ==========================================

    public function leaves(Request $request): View
    {
        $this->ensureCanViewReports($request);

        $month = $request->filled('month') ? (int) $request->query('month') : null;
        $year = $request->filled('year') ? (int) $request->query('year') : null;
        $departmentId = $request->filled('department_id') ? (int) $request->query('department_id') : null;
        $status = $request->query('status');
        $leaveType = $request->query('leave_type');
        $search = $request->query('search');

        $query = $this->buildLeaveQuery($month, $year, $departmentId, $status, $leaveType, $search);

        // Summary calculations
        $summaryQuery = clone $query;
        $summary = [
            'total' => (clone $summaryQuery)->count(),
            'approved' => (clone $summaryQuery)->where('status', 'approved')->count(),
            'pending' => (clone $summaryQuery)->where('status', 'pending')->count(),
            'rejected' => (clone $summaryQuery)->where('status', 'rejected')->count(),
        ];

        $leaves = $query->paginate(20)->withQueryString();

        return view('reports.leaves', [
            'leaves' => $leaves,
            'departments' => Department::orderBy('name')->get(),
            'months' => Payroll::getMonthNames(),
            'currentMonth' => $month,
            'currentYear' => $year,
            'currentDepartment' => $departmentId,
            'currentStatus' => $status,
            'currentLeaveType' => $leaveType,
            'currentSearch' => $search,
            'summary' => $summary,
            'canExport' => $this->canExport($request),
        ]);
    }

    public function exportLeaves(Request $request): StreamedResponse|BinaryFileResponse|Response
    {
        $this->ensureCanExportReports($request);

        $format = $this->resolveExportFormat($request);

        $month = $request->filled('month') ? (int) $request->query('month') : null;
        $year = $request->filled('year') ? (int) $request->query('year') : null;
        $departmentId = $request->filled('department_id') ? (int) $request->query('department_id') : null;
        $status = $request->query('status');
        $leaveType = $request->query('leave_type');
        $search = $request->query('search');

        $records = $this->buildLeaveQuery($month, $year, $departmentId, $status, $leaveType, $search)->get();

        $this->logReportExport($request, 'leaves', $request->query());

        $timestamp = date('Ymd_His');

        if ($format === 'xlsx') {
            return Excel::download(new LeaveExport($records), "laporan_cuti_izin_{$timestamp}.xlsx");
        }

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.pdf.leaves', ['records' => $records])
                ->setPaper('a4', 'landscape');

            return $pdf->download("laporan_cuti_izin_{$timestamp}.pdf");
        }

        $filename = "laporan_cuti_izin_{$timestamp}.csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'ID Karyawan',
                'Nama Karyawan',
                'Departemen',
                'Jenis Cuti',
                'Tanggal Mulai',
                'Tanggal Selesai',
                'Durasi (Hari)',
                'Alasan',
                'Status',
                'Disetujui Oleh',
                'Waktu Diproses',
                'Alasan Penolakan',
            ]);

            foreach ($records as $row) {
                fputcsv($handle, [
                    $row->employee?->employee_code ?? '-',
                    $row->employee?->name ?? 'Karyawan Dihapus',
                    $row->employee?->department?->name ?? '-',
                    $row->leave_type,
                    $row->start_date ? $row->start_date->format('Y-m-d') : '-',
                    $row->end_date ? $row->end_date->format('Y-m-d') : '-',
                    $row->duration_days,
                    $row->reason,
                    $row->status_label,
                    $row->approver?->name ?? '-',
                    $row->approved_at ? $row->approved_at->format('Y-m-d H:i') : '-',
                    $row->rejection_reason ?? '-',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    private function buildLeaveQuery(?int $month, ?int $year, ?int $departmentId, ?string $status, ?string $leaveType, ?string $search)
    {
        $query = LeaveRequest::query()
            ->with(['employee.department', 'employee.position', 'approver'])
            ->latest('start_date');

        if ($month) {
            $query->whereMonth('start_date', $month);
        }

        if ($year) {
            $query->whereYear('start_date', $year);
        }

        if ($departmentId) {
            $query->whereHas('employee', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        if ($status && in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $status);
        }

        if ($leaveType) {
            $query->where('leave_type', $leaveType);
        }

        if ($search) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    // ==========================================
    // 3. LAPORAN LEMBUR
    // ==========================================

    public function overtimes(Request $request): View
    {
        $this->ensureCanViewReports($request);

        $month = $request->filled('month') ? (int) $request->query('month') : null;
        $year = $request->filled('year') ? (int) $request->query('year') : null;
        $departmentId = $request->filled('department_id') ? (int) $request->query('department_id') : null;
        $status = $request->query('status');
        $search = $request->query('search');

        $query = $this->buildOvertimeQuery($month, $year, $departmentId, $status, $search);

        // Summary calculations
        $summaryQuery = clone $query;
        $totalHours = (float) (clone $summaryQuery)->where('status', 'approved')->sum('duration');

        $summary = [
            'total' => (clone $summaryQuery)->count(),
            'total_hours' => $totalHours,
            'approved' => (clone $summaryQuery)->where('status', 'approved')->count(),
            'pending' => (clone $summaryQuery)->where('status', 'pending')->count(),
            'rejected' => (clone $summaryQuery)->where('status', 'rejected')->count(),
        ];

        $overtimes = $query->paginate(20)->withQueryString();

        return view('reports.overtimes', [
            'overtimes' => $overtimes,
            'departments' => Department::orderBy('name')->get(),
            'months' => Payroll::getMonthNames(),
            'currentMonth' => $month,
            'currentYear' => $year,
            'currentDepartment' => $departmentId,
            'currentStatus' => $status,
            'currentSearch' => $search,
            'summary' => $summary,
            'canExport' => $this->canExport($request),
        ]);
    }

    public function exportOvertimes(Request $request): StreamedResponse|BinaryFileResponse|Response
    {
        $this->ensureCanExportReports($request);

        $format = $this->resolveExportFormat($request);

        $month = $request->filled('month') ? (int) $request->query('month') : null;
        $year = $request->filled('year') ? (int) $request->query('year') : null;
        $departmentId = $request->filled('department_id') ? (int) $request->query('department_id') : null;
        $status = $request->query('status');
        $search = $request->query('search');

        $records = $this->buildOvertimeQuery($month, $year, $departmentId, $status, $search)->get();

        $this->logReportExport($request, 'overtimes', $request->query());

        $timestamp = date('Ymd_His');

        if ($format === 'xlsx') {
            return Excel::download(new OvertimeExport($records), "laporan_lembur_{$timestamp}.xlsx");
        }

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.pdf.overtimes', ['records' => $records])
                ->setPaper('a4', 'landscape');

            return $pdf->download("laporan_lembur_{$timestamp}.pdf");
        }

        $filename = "laporan_lembur_{$timestamp}.csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'ID Karyawan',
                'Nama Karyawan',
                'Departemen',
                'Tanggal Lembur',
                'Jam Mulai',
                'Jam Selesai',
                'Durasi (Jam)',
                'Uraian Tugas / Alasan',
                'Status',
                'Disetujui Oleh',
                'Waktu Diproses',
                'Alasan Penolakan',
            ]);

            foreach ($records as $row) {
                fputcsv($handle, [
                    $row->employee?->employee_code ?? '-',
                    $row->employee?->name ?? 'Karyawan Dihapus',
                    $row->employee?->department?->name ?? '-',
                    $row->overtime_date ? $row->overtime_date->format('Y-m-d') : '-',
                    is_string($row->start_time) ? substr($row->start_time, 0, 5) : $row->start_time?->format('H:i'),
                    is_string($row->end_time) ? substr($row->end_time, 0, 5) : $row->end_time?->format('H:i'),
                    (float) $row->duration,
                    $row->reason,
                    $row->status_label,
                    $row->approver?->name ?? '-',
                    $row->approved_at ? $row->approved_at->format('Y-m-d H:i') : '-',
                    $row->rejection_reason ?? '-',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    private function buildOvertimeQuery(?int $month, ?int $year, ?int $departmentId, ?string $status, ?string $search)
    {
        $query = Overtime::query()
            ->with(['employee.department', 'employee.position', 'approver'])
            ->latest('overtime_date');

        if ($month) {
            $query->whereMonth('overtime_date', $month);
        }

        if ($year) {
            $query->whereYear('overtime_date', $year);
        }

        if ($departmentId) {
            $query->whereHas('employee', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        if ($status && in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    // ==========================================
    // 4. LAPORAN PAYROLL
    // ==========================================

    public function payrolls(Request $request): View
    {
        $this->ensureCanViewReports($request);

        $month = $request->filled('month') ? (int) $request->query('month') : null;
        $year = $request->filled('year') ? (int) $request->query('year') : null;
        $departmentId = $request->filled('department_id') ? (int) $request->query('department_id') : null;
        $status = $request->query('status');
        $search = $request->query('search');

        $query = $this->buildPayrollQuery($month, $year, $departmentId, $status, $search);

        // Summary calculations
        $summaryQuery = clone $query;
        $totalCount = (clone $summaryQuery)->count();
        $totalNetSalary = (float) (clone $summaryQuery)->sum('net_salary');

        $payrollIds = (clone $summaryQuery)->pluck('id');
        $totalBpjs = (float) PayrollDetail::query()
            ->whereIn('payroll_id', $payrollIds)
            ->where('type', 'deduction')
            ->where('name', 'like', '%BPJS%')
            ->sum('amount');

        $totalPph21 = (float) PayrollDetail::query()
            ->whereIn('payroll_id', $payrollIds)
            ->where('type', 'deduction')
            ->where('name', 'like', '%PPh21%')
            ->sum('amount');

        $summary = [
            'total_count' => $totalCount,
            'total_net_salary' => $totalNetSalary,
            'total_bpjs' => $totalBpjs,
            'total_pph21' => $totalPph21,
        ];

        $payrolls = $query->paginate(20)->withQueryString();

        return view('reports.payrolls', [
            'payrolls' => $payrolls,
            'departments' => Department::orderBy('name')->get(),
            'months' => Payroll::getMonthNames(),
            'currentMonth' => $month,
            'currentYear' => $year,
            'currentDepartment' => $departmentId,
            'currentStatus' => $status,
            'currentSearch' => $search,
            'summary' => $summary,
            'canExport' => $this->canExport($request),
        ]);
    }

    public function exportPayrolls(Request $request): StreamedResponse|BinaryFileResponse|Response
    {
        $this->ensureCanExportReports($request);

        $format = $this->resolveExportFormat($request);

        $month = $request->filled('month') ? (int) $request->query('month') : null;
        $year = $request->filled('year') ? (int) $request->query('year') : null;
        $departmentId = $request->filled('department_id') ? (int) $request->query('department_id') : null;
        $status = $request->query('status');
        $search = $request->query('search');

        $records = $this->buildPayrollQuery($month, $year, $departmentId, $status, $search)->get();

        $this->logReportExport($request, 'payrolls', $request->query());

        $timestamp = date('Ymd_His');

        if ($format === 'xlsx') {
            return Excel::download(new PayrollExport($records), "laporan_penggajian_{$timestamp}.xlsx");
        }

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.pdf.payrolls', ['records' => $records])
                ->setPaper('a4', 'landscape');

            return $pdf->download("laporan_penggajian_{$timestamp}.pdf");
        }

        $filename = "laporan_penggajian_{$timestamp}.csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'ID Karyawan',
                'Nama Karyawan',
                'Departemen',
                'Periode',
                'Gaji Pokok',
                'Total Tunjangan',
                'Total Potongan',
                'Potongan BPJS',
                'Potongan PPh21',
                'Gaji Bersih (Net)',
                'Status',
                'Waktu Dihasilkan',
            ]);

            foreach ($records as $row) {
                $bpjs = (float) $row->details->where('type', 'deduction')->filter(fn ($d) => str_contains($d->name, 'BPJS'))->sum('amount');
                $pph21 = (float) $row->details->where('type', 'deduction')->filter(fn ($d) => str_contains($d->name, 'PPh21'))->sum('amount');

                fputcsv($handle, [
                    $row->employee?->employee_code ?? '-',
                    $row->employee?->name ?? 'Karyawan Dihapus',
                    $row->employee?->department?->name ?? '-',
                    $row->period_label,
                    (float) $row->basic_salary,
                    (float) $row->total_allowance,
                    (float) $row->total_deduction,
                    $bpjs,
                    $pph21,
                    (float) $row->net_salary,
                    $row->status_label,
                    $row->generated_at ? $row->generated_at->format('Y-m-d H:i') : '-',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    private function buildPayrollQuery(?int $month, ?int $year, ?int $departmentId, ?string $status, ?string $search)
    {
        $query = Payroll::query()
            ->with(['employee.department', 'employee.position', 'details'])
            ->latest('id');

        if ($month) {
            $query->where('period_month', $month);
        }

        if ($year) {
            $query->where('period_year', $year);
        }

        if ($departmentId) {
            $query->whereHas('employee', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        if ($status && in_array($status, ['draft', 'generated', 'paid'], true)) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    // ==========================================
    // 5. LAPORAN TURNOVER KARYAWAN
    // ==========================================

    public function turnover(Request $request): View
    {
        $this->ensureCanViewReports($request);

        $year = $request->filled('year') ? (int) $request->query('year') : null;
        $departmentId = $request->filled('department_id') ? (int) $request->query('department_id') : null;
        $employmentType = $request->query('employment_type');
        $status = $request->query('status');
        $search = $request->query('search');

        $query = $this->buildTurnoverQuery($year, $departmentId, $employmentType, $status, $search);

        // Summary calculations
        $summaryQuery = clone $query;
        $summary = [
            'total' => (clone $summaryQuery)->count(),
            'active' => (clone $summaryQuery)->where('status', 'active')->count(),
            'inactive' => (clone $summaryQuery)->where('status', 'inactive')->count(),
            'resigned' => (clone $summaryQuery)->where(function ($q) {
                $q->where('status', 'resigned')->orWhereNotNull('resigned_at');
            })->count(),
        ];

        $employees = $query->paginate(20)->withQueryString();

        return view('reports.turnover', [
            'employees' => $employees,
            'departments' => Department::orderBy('name')->get(),
            'currentYear' => $year,
            'currentDepartment' => $departmentId,
            'currentEmploymentType' => $employmentType,
            'currentStatus' => $status,
            'currentSearch' => $search,
            'summary' => $summary,
            'canExport' => $this->canExport($request),
        ]);
    }

    public function exportTurnover(Request $request): StreamedResponse|BinaryFileResponse|Response
    {
        $this->ensureCanExportReports($request);

        $format = $this->resolveExportFormat($request);

        $year = $request->filled('year') ? (int) $request->query('year') : null;
        $departmentId = $request->filled('department_id') ? (int) $request->query('department_id') : null;
        $employmentType = $request->query('employment_type');
        $status = $request->query('status');
        $search = $request->query('search');

        $records = $this->buildTurnoverQuery($year, $departmentId, $employmentType, $status, $search)->get();

        $this->logReportExport($request, 'turnover', $request->query());

        $timestamp = date('Ymd_His');

        if ($format === 'xlsx') {
            return Excel::download(new TurnoverExport($records), "laporan_turnover_karyawan_{$timestamp}.xlsx");
        }

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.pdf.turnover', ['records' => $records])
                ->setPaper('a4', 'landscape');

            return $pdf->download("laporan_turnover_karyawan_{$timestamp}.pdf");
        }

        $filename = "laporan_turnover_karyawan_{$timestamp}.csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'ID Karyawan',
                'Nama Karyawan',
                'Departemen',
                'Jabatan',
                'Tipe Hubungan Kerja',
                'Tanggal Bergabung',
                'Status Karyawan',
                'Tanggal Resign',
                'Status Khusus',
            ]);

            foreach ($records as $row) {
                fputcsv($handle, [
                    $row->employee_code,
                    $row->name,
                    $row->department?->name ?? '-',
                    $row->position?->name ?? '-',
                    ucfirst($row->employment_type ?? 'Permanent'),
                    $row->join_date ? $row->join_date->format('Y-m-d') : '-',
                    ucfirst((string) $row->status),
                    $row->resigned_at ? $row->resigned_at->format('Y-m-d') : '-',
                    $row->special_status ?? '-',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    private function buildTurnoverQuery(?int $year, ?int $departmentId, ?string $employmentType, ?string $status, ?string $search)
    {
        $query = Employee::query()
            ->with(['department', 'position'])
            ->latest('join_date');

        if ($year) {
            $query->where(function ($q) use ($year) {
                $q->whereYear('join_date', $year)
                    ->orWhereYear('resigned_at', $year);
            });
        }

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        if ($employmentType) {
            $query->where('employment_type', $employmentType);
        }

        if ($status) {
            if ($status === 'resigned') {
                $query->where(function ($q) {
                    $q->where('status', 'resigned')->orWhereNotNull('resigned_at');
                });
            } else {
                $query->where('status', $status);
            }
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    // ==========================================
    // 6. LAPORAN AUDIT TRAIL
    // ==========================================

    public function auditLogs(Request $request): View
    {
        $this->ensureCanViewAuditLogs($request);

        $action = $request->query('action');
        $userId = $request->filled('user_id') ? (int) $request->query('user_id') : null;
        $entityType = $request->query('entity_type');
        $date = $request->query('date');
        $search = $request->query('search');

        $query = $this->buildAuditQuery($action, $userId, $entityType, $date, $search);

        $auditLogs = $query->paginate(25)->withQueryString();
        $users = User::orderBy('name')->get();

        // Get distinct actions from database
        $actions = AuditLog::query()->distinct()->pluck('action')->filter()->values();

        return view('reports.audit_logs', [
            'auditLogs' => $auditLogs,
            'users' => $users,
            'actions' => $actions,
            'currentAction' => $action,
            'currentUserId' => $userId,
            'currentEntityType' => $entityType,
            'currentDate' => $date,
            'currentSearch' => $search,
            'canExport' => $this->canExport($request),
        ]);
    }

    public function exportAuditLogs(Request $request): StreamedResponse|BinaryFileResponse|Response
    {
        $this->ensureCanViewAuditLogs($request);
        $this->ensureCanExportReports($request);

        $format = $this->resolveExportFormat($request);

        $action = $request->query('action');
        $userId = $request->filled('user_id') ? (int) $request->query('user_id') : null;
        $entityType = $request->query('entity_type');
        $date = $request->query('date');
        $search = $request->query('search');

        $records = $this->buildAuditQuery($action, $userId, $entityType, $date, $search)->get();

        $this->logReportExport($request, 'audit_logs', $request->query());

        $timestamp = date('Ymd_His');

        if ($format === 'xlsx') {
            return Excel::download(new AuditLogExport($records), "laporan_audit_trail_{$timestamp}.xlsx");
        }

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.pdf.audit_logs', ['records' => $records])
                ->setPaper('a4', 'landscape');

            return $pdf->download("laporan_audit_trail_{$timestamp}.pdf");
        }

        $filename = "laporan_audit_trail_{$timestamp}.csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Waktu Aktivitas',
                'Pengguna (User)',
                'Aksi (Action)',
                'Tipe Entitas',
                'ID Entitas',
                'Alamat IP',
                'User Agent',
                'Nilai Lama',
                'Nilai Baru',
            ]);

            foreach ($records as $row) {
                fputcsv($handle, [
                    $row->created_at ? $row->created_at->format('Y-m-d H:i:s') : '-',
                    $row->user?->name ?? 'Sistem',
                    $row->action,
                    $row->entity_type ?? '-',
                    $row->entity_id ?? '-',
                    $row->ip_address ?? '-',
                    $row->user_agent ?? '-',
                    $row->old_values ? json_encode($row->old_values, JSON_UNESCAPED_UNICODE) : '-',
                    $row->new_values ? json_encode($row->new_values, JSON_UNESCAPED_UNICODE) : '-',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    private function buildAuditQuery(?string $action, ?int $userId, ?string $entityType, ?string $date, ?string $search)
    {
        $query = AuditLog::query()->with('user')->latest('id');

        if ($action) {
            $query->where('action', $action);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($entityType) {
            $query->where('entity_type', 'like', "%{$entityType}%");
        }

        if ($date) {
            $query->whereDate('created_at', $date);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('entity_type', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }

    // ==========================================
    // RBAC & AUDIT LOGGING HELPERS
    // ==========================================

    private function canExport(Request $request): bool
    {
        $user = $request->user();
        if (! $user || $user->role?->name === 'taiwan_account') {
            return false;
        }

        return $user->role?->name === 'super_admin'
            || ($user->role?->permissions->contains('name', 'export_reports') ?? false);
    }

    private function ensureCanViewReports(Request $request): void
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $canView = $user->role?->name === 'super_admin'
            || $user->role?->name === 'taiwan_account'
            || ($user->role?->permissions->contains('name', 'view_reports') ?? false);

        if (! $canView) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat modul laporan.');
        }
    }

    private function ensureCanExportReports(Request $request): void
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        if ($user->role?->name === 'taiwan_account') {
            abort(403, 'Taiwan Account bersifat read-only dan tidak diizinkan mengekspor laporan.');
        }

        $canExport = $user->role?->name === 'super_admin'
            || ($user->role?->permissions->contains('name', 'export_reports') ?? false);

        if (! $canExport) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengekspor laporan.');
        }
    }

    private function ensureCanViewAuditLogs(Request $request): void
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        if ($user->role?->name === 'taiwan_account') {
            abort(403, 'Taiwan Account tidak memiliki hak akses untuk melihat Audit Trail.');
        }

        $canViewAudit = $user->role?->name === 'super_admin'
            || ($user->role?->permissions->contains('name', 'view_audit_logs') ?? false);

        if (! $canViewAudit) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat Laporan Audit Trail.');
        }
    }

    private function logReportExport(Request $request, string $reportType, array $filters): void
    {
        $user = $request->user();
        if ($user) {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'report_exported',
                'entity_type' => 'Report',
                'entity_id' => null,
                'old_values' => null,
                'new_values' => [
                    'report_type' => $reportType,
                    'filters' => $filters,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);
        }
    }

    private function resolveExportFormat(Request $request): string
    {
        $format = strtolower((string) $request->query('format', 'csv'));

        if ($format === 'excel') {
            return 'xlsx';
        }

        if (! in_array($format, ['csv', 'xlsx', 'pdf'], true)) {
            return 'csv';
        }

        return $format;
    }
}
