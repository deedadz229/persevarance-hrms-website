<?php

namespace App\Exports;

use App\Models\Overtime;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OvertimeExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
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
            'Tanggal Lembur',
            'Jam Mulai',
            'Jam Selesai',
            'Durasi (Jam)',
            'Uraian Tugas / Alasan',
            'Status',
            'Disetujui Oleh',
            'Waktu Diproses',
            'Alasan Penolakan',
        ];
    }

    /**
     * @param  Overtime  $row
     * @return array{0: string, 1: string, 2: string, 3: string, 4: string, 5: string, 6: float, 7: string, 8: string, 9: string, 10: string, 11: string}
     */
    public function map(mixed $row): array
    {
        return [
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
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
