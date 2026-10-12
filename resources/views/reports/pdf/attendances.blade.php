@extends('reports.pdf.layout')

@section('title', 'Laporan Absensi Karyawan')
@section('subtitle', 'Rekapitulasi log presensi dan kehadiran')

@section('content')
<table class="table-data">
    <thead>
        <tr>
            <th style="width: 5%;" class="text-center">No</th>
            <th style="width: 10%;">ID Karyawan</th>
            <th style="width: 17%;">Nama Karyawan</th>
            <th style="width: 14%;">Departemen</th>
            <th style="width: 10%;" class="text-center">Tanggal</th>
            <th style="width: 8%;" class="text-center">Masuk</th>
            <th style="width: 8%;" class="text-center">Keluar</th>
            <th style="width: 12%;" class="text-center">Status</th>
            <th style="width: 16%;">Catatan</th>
        </tr>
    </thead>
    <tbody>
        @forelse($records as $index => $row)
            @php
                $isWfh = $row->location_status === 'wfh' || str_contains(strtolower($row->notes ?? ''), 'wfh');
                $isLate = ($row->check_in && substr($row->check_in, 0, 5) > '08:30') || $row->location_status === 'late' || str_contains(strtolower($row->notes ?? ''), 'telat');
                $statusLabel = $isWfh ? 'WFH' : ($isLate ? 'Terlambat' : 'Hadir Tepat Waktu');
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $row->employee?->employee_code ?? '-' }}</td>
                <td><strong>{{ $row->employee?->name ?? 'Karyawan Dihapus' }}</strong></td>
                <td>{{ $row->employee?->department?->name ?? '-' }}</td>
                <td class="text-center">{{ $row->attendance_date ? date('d/m/Y', strtotime($row->attendance_date)) : '-' }}</td>
                <td class="text-center">{{ $row->check_in ? substr($row->check_in, 0, 5) : '-' }}</td>
                <td class="text-center">{{ $row->check_out ? substr($row->check_out, 0, 5) : '-' }}</td>
                <td class="text-center">{{ $statusLabel }}</td>
                <td>{{ $row->notes ?? '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="9" class="text-center" style="padding: 15px;">Tidak ada data yang ditemukan.</td>
            </tr>
        @endforelse
    </tbody>
</table>
@endsection
