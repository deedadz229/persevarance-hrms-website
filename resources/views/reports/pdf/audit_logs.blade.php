@extends('reports.pdf.layout')

@section('title', 'Laporan Audit Trail')
@section('subtitle', 'Rekam jejak aktivitas operasional dan perubahan data pada sistem')

@section('content')
<table class="table-data">
    <thead>
        <tr>
            <th style="width: 4%;" class="text-center">No</th>
            <th style="width: 14%;">Waktu</th>
            <th style="width: 14%;">Pengguna</th>
            <th style="width: 18%;">Aksi</th>
            <th style="width: 16%;">Tipe Entitas</th>
            <th style="width: 8%;" class="text-center">ID</th>
            <th style="width: 12%;">Alamat IP</th>
            <th style="width: 14%;">Detail Nilai</th>
        </tr>
    </thead>
    <tbody>
        @forelse($records as $index => $row)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $row->created_at ? $row->created_at->format('d/m/Y H:i:s') : '-' }}</td>
                <td><strong>{{ $row->user?->name ?? 'Sistem' }}</strong></td>
                <td>{{ $row->action }}</td>
                <td>{{ $row->entity_type ?? '-' }}</td>
                <td class="text-center">{{ $row->entity_id ?? '-' }}</td>
                <td>{{ $row->ip_address ?? '-' }}</td>
                <td style="font-size: 7.5px;">
                    @if($row->new_values)
                        {{ \Illuminate\Support\Str::limit(json_encode($row->new_values, JSON_UNESCAPED_UNICODE), 70) }}
                    @else
                        -
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="text-center" style="padding: 15px;">Tidak ada data yang ditemukan.</td>
            </tr>
        @endforelse
    </tbody>
</table>
@endsection
