@extends('reports.pdf.layout')

@section('title', 'Laporan Penggajian (Payroll)')
@section('subtitle', 'Rekapitulasi gaji pokok, tunjangan, potongan BPJS, PPh21, dan gaji bersih')

@section('content')
<table class="table-data">
    <thead>
        <tr>
            <th style="width: 4%;" class="text-center">No</th>
            <th style="width: 9%;">ID Karyawan</th>
            <th style="width: 15%;">Nama Karyawan</th>
            <th style="width: 11%;">Departemen</th>
            <th style="width: 10%;">Periode</th>
            <th style="width: 11%;" class="text-right">Gaji Pokok</th>
            <th style="width: 10%;" class="text-right">Tunjangan</th>
            <th style="width: 9%;" class="text-right">Potongan</th>
            <th style="width: 12%;" class="text-right">Gaji Bersih</th>
            <th style="width: 9%;" class="text-center">Status</th>
        </tr>
    </thead>
    <tbody>
        @php
            $grandBasic = 0;
            $grandNet = 0;
        @endphp
        @forelse($records as $index => $row)
            @php
                $grandBasic += (float) $row->basic_salary;
                $grandNet += (float) $row->net_salary;
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $row->employee?->employee_code ?? '-' }}</td>
                <td><strong>{{ $row->employee?->name ?? 'Karyawan Dihapus' }}</strong></td>
                <td>{{ $row->employee?->department?->name ?? '-' }}</td>
                <td>{{ $row->period_label }}</td>
                <td class="text-right">Rp {{ number_format((float) $row->basic_salary, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format((float) $row->total_allowance, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format((float) $row->total_deduction, 0, ',', '.') }}</td>
                <td class="text-right"><strong>Rp {{ number_format((float) $row->net_salary, 0, ',', '.') }}</strong></td>
                <td class="text-center">{{ $row->status_label }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="10" class="text-center" style="padding: 15px;">Tidak ada data yang ditemukan.</td>
            </tr>
        @endforelse
    </tbody>
    @if(count($records) > 0)
    <tfoot>
        <tr style="background-color: #f1f5f9; font-weight: bold;">
            <td colspan="5" class="text-right">TOTAL KESELURUHAN:</td>
            <td class="text-right">Rp {{ number_format($grandBasic, 0, ',', '.') }}</td>
            <td colspan="2"></td>
            <td class="text-right">Rp {{ number_format($grandNet, 0, ',', '.') }}</td>
            <td></td>
        </tr>
    </tfoot>
    @endif
</table>
@endsection
