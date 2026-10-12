<?php

namespace App\Exports;

use App\Models\AuditLog;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AuditLogExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
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
            'Waktu Aktivitas',
            'Pengguna (User)',
            'Aksi (Action)',
            'Tipe Entitas',
            'ID Entitas',
            'Alamat IP',
            'User Agent',
            'Nilai Lama',
            'Nilai Baru',
        ];
    }

    /**
     * @param  AuditLog  $row
     * @return array{0: string, 1: string, 2: string, 3: string, 4: string, 5: string, 6: string, 7: string, 8: string}
     */
    public function map(mixed $row): array
    {
        return [
            $row->created_at ? $row->created_at->format('Y-m-d H:i:s') : '-',
            $row->user?->name ?? 'Sistem',
            $row->action,
            $row->entity_type ?? '-',
            (string) ($row->entity_id ?? '-'),
            $row->ip_address ?? '-',
            $row->user_agent ?? '-',
            $row->old_values ? json_encode($row->old_values, JSON_UNESCAPED_UNICODE) : '-',
            $row->new_values ? json_encode($row->new_values, JSON_UNESCAPED_UNICODE) : '-',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
