<?php

namespace App\Exports;

use App\Models\LeaveRequest;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LeaveExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
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
            'Jenis Cuti',
            'Tanggal Mulai',
            'Tanggal Selesai',
            'Durasi (Hari)',
            'Alasan',
            'Status',
            'Disetujui Oleh',
            'Waktu Diproses',
            'Alasan Penolakan',
        ];
    }

    /**
     * @param  LeaveRequest  $row
     * @return array{0: string, 1: string, 2: string, 3: string, 4: string, 5: string, 6: int, 7: string, 8: string, 9: string, 10: string, 11: string}
     */
    public function map(mixed $row): array
    {
        return [
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
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
