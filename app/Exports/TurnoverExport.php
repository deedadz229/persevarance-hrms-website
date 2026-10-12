<?php

namespace App\Exports;

use App\Models\Employee;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TurnoverExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
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
            'Jabatan',
            'Tipe Hubungan Kerja',
            'Tanggal Bergabung',
            'Status Karyawan',
            'Tanggal Resign',
            'Status Khusus',
        ];
    }

    /**
     * @param  Employee  $row
     * @return array{0: string, 1: string, 2: string, 3: string, 4: string, 5: string, 6: string, 7: string, 8: string}
     */
    public function map(mixed $row): array
    {
        return [
            $row->employee_code,
            $row->name,
            $row->department?->name ?? '-',
            $row->position?->name ?? '-',
            ucfirst($row->employment_type ?? 'Permanent'),
            $row->join_date ? $row->join_date->format('Y-m-d') : '-',
            ucfirst((string) $row->status),
            $row->resigned_at ? $row->resigned_at->format('Y-m-d') : '-',
            $row->special_status ?? '-',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
