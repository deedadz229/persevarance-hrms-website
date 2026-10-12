<?php

namespace App\Exports;

use App\Models\Payroll;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PayrollExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
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
            'Periode',
            'Gaji Pokok',
            'Total Tunjangan',
            'Total Potongan',
            'Potongan BPJS',
            'Potongan PPh21',
            'Gaji Bersih (Net)',
            'Status',
            'Waktu Dihasilkan',
        ];
    }

    /**
     * @param  Payroll  $row
     * @return array{0: string, 1: string, 2: string, 3: string, 4: float, 5: float, 6: float, 7: float, 8: float, 9: float, 10: string, 11: string}
     */
    public function map(mixed $row): array
    {
        $bpjs = (float) $row->details->where('type', 'deduction')->filter(fn ($d) => str_contains($d->name, 'BPJS'))->sum('amount');
        $pph21 = (float) $row->details->where('type', 'deduction')->filter(fn ($d) => str_contains($d->name, 'PPh21'))->sum('amount');

        return [
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
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
