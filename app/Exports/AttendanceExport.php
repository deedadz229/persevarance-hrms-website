<?php

namespace App\Exports;

use App\Models\Attendance;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private Collection $records,
    ) {}

    public function collection(): Collection
    {
        return $this->records;
    }

    public function headings(): array
    {
        return [
            'ID Karyawan',
            'Nama Karyawan',
            'Departemen',
            'Tanggal',
            'Jam Masuk',
            'Jam Keluar',
            'Status Kehadiran',
            'WiFi Terverifikasi',
            'Status Lokasi / GPS',
            'Catatan',
        ];
    }

    /**
     * @param  Attendance  $row
     * @return array{0: string, 1: string, 2: string, 3: string, 4: string, 5: string, 6: string, 7: string, 8: string, 9: string}
     */
    public function map(mixed $row): array
    {
        $isWfh = $row->location_status === 'wfh' || str_contains(strtolower($row->notes ?? ''), 'wfh');
        $isLate = ($row->check_in && substr($row->check_in, 0, 5) > '08:30') || $row->location_status === 'late' || str_contains(strtolower($row->notes ?? ''), 'telat');
        $statusLabel = $isWfh ? 'WFH' : ($isLate ? 'Terlambat' : 'Hadir Tepat Waktu');

        return [
            $row->employee?->employee_code ?? '-',
            $row->employee?->name ?? 'Karyawan Dihapus',
            $row->employee?->department?->name ?? '-',
            $row->attendance_date ? date('Y-m-d', strtotime($row->attendance_date)) : '-',
            $row->check_in ? substr($row->check_in, 0, 5) : '-',
            $row->check_out ? substr($row->check_out, 0, 5) : '-',
            $statusLabel,
            $row->wifi_verified ? 'Ya' : 'Tidak',
            $row->location_status ?? 'valid',
            $row->notes ?? '-',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
