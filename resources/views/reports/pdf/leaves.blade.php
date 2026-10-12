@extends('reports.pdf.layout')

@section('title', 'Laporan Pengajuan Cuti & Izin')
@section('subtitle', 'Daftar pengajuan permohonan cuti dan izin karyawan')

@section('content')
<table class="table-data">
    <thead>
        <tr>
            <th style="width: 4%;" class="text-center">No</th>
            <th style="width: 10%;">ID Karyawan</th>
            <th style="width: 16%;">Nama Karyawan</th>
            <th style="width: 12%;">Jenis Cuti</th>
            <th style="width: 9%;" class="text-center">Mulai</th>
            <th style="width: 9%;" class="text-center">Selesai</th>
            <th style="width: 7%;" class="text-center">Durasi</th>
            <th style="width: 15%;">Alasan</th>
            <th style="width: 10%;" class="text-center">Status</th>
            <th style="width: 8%;">Disetujui</th>
        </tr>
    </thead>
    <tbody>
        @forelse($records as $index => $row)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $row->employee?->employee_code ?? '-' }}</td>
                <td><strong>{{ $row->employee?->name ?? 'Karyawan Dihapus' }}</strong></td>
                <td>{{ $row->leave_type }}</td>
                <td class="text-center">{{ $row->start_date ? $row->start_date->format('d/m/Y') : '-' }}</td>
                <td class="text-center">{{ $row->end_date ? $row->end_date->format('d/m/Y') : '-' }}</td>
                <td class="text-center">{{ $row->duration_days }} Hari</td>
                <td>{{ $row->reason }}</td>
                <td class="text-center">{{ $row->status_label }}</td>
                <td>{{ $row->approver?->name ?? '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="10" class="text-center" style="padding: 15px;">Tidak ada data yang ditemukan.</td>
            </tr>
        @endforelse
    </tbody>
</table>
@endsection
