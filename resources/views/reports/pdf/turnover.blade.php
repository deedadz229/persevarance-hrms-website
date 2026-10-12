@extends('reports.pdf.layout')

@section('title', 'Laporan Turnover Karyawan')
@section('subtitle', 'Daftar pergerakan status aktif, probation, dan pengunduran diri karyawan')

@section('content')
<table class="table-data">
    <thead>
        <tr>
            <th style="width: 4%;" class="text-center">No</th>
            <th style="width: 10%;">ID Karyawan</th>
            <th style="width: 17%;">Nama Karyawan</th>
            <th style="width: 14%;">Departemen</th>
            <th style="width: 13%;">Jabatan</th>
            <th style="width: 10%;">Hubungan Kerja</th>
            <th style="width: 10%;" class="text-center">Tgl Masuk</th>
            <th style="width: 11%;" class="text-center">Status</th>
            <th style="width: 11%;" class="text-center">Tgl Resign</th>
        </tr>
    </thead>
    <tbody>
        @forelse($records as $index => $row)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $row->employee_code }}</td>
                <td><strong>{{ $row->name }}</strong></td>
                <td>{{ $row->department?->name ?? '-' }}</td>
                <td>{{ $row->position?->name ?? '-' }}</td>
                <td>{{ ucfirst($row->employment_type ?? 'Permanent') }}</td>
                <td class="text-center">{{ $row->join_date ? $row->join_date->format('d/m/Y') : '-' }}</td>
                <td class="text-center">{{ ucfirst((string) $row->status) }}</td>
                <td class="text-center">{{ $row->resigned_at ? $row->resigned_at->format('d/m/Y') : '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="9" class="text-center" style="padding: 15px;">Tidak ada data yang ditemukan.</td>
            </tr>
        @endforelse
    </tbody>
</table>
@endsection
