@extends('reports.pdf.layout')

@section('title', 'Laporan Lembur (Overtime)')
@section('subtitle', 'Rekapitulasi pengajuan dan persetujuan jam lembur karyawan')

@section('content')
<table class="table-data">
    <thead>
        <tr>
            <th style="width: 4%;" class="text-center">No</th>
            <th style="width: 10%;">ID Karyawan</th>
            <th style="width: 16%;">Nama Karyawan</th>
            <th style="width: 14%;">Departemen</th>
            <th style="width: 9%;" class="text-center">Tanggal</th>
            <th style="width: 7%;" class="text-center">Mulai</th>
            <th style="width: 7%;" class="text-center">Selesai</th>
            <th style="width: 7%;" class="text-center">Durasi</th>
            <th style="width: 16%;">Uraian Tugas</th>
            <th style="width: 10%;" class="text-center">Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($records as $index => $row)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $row->employee?->employee_code ?? '-' }}</td>
                <td><strong>{{ $row->employee?->name ?? 'Karyawan Dihapus' }}</strong></td>
                <td>{{ $row->employee?->department?->name ?? '-' }}</td>
                <td class="text-center">{{ $row->overtime_date ? $row->overtime_date->format('d/m/Y') : '-' }}</td>
                <td class="text-center">{{ is_string($row->start_time) ? substr($row->start_time, 0, 5) : $row->start_time?->format('H:i') }}</td>
                <td class="text-center">{{ is_string($row->end_time) ? substr($row->end_time, 0, 5) : $row->end_time?->format('H:i') }}</td>
                <td class="text-center">{{ (float) $row->duration }} Jam</td>
                <td>{{ $row->reason }}</td>
                <td class="text-center">{{ $row->status_label }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="10" class="text-center" style="padding: 15px;">Tidak ada data yang ditemukan.</td>
            </tr>
        @endforelse
    </tbody>
</table>
@endsection
