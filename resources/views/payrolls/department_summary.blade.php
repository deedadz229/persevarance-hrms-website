@extends('layouts.app')

@section('title', 'Rekap Departemen · Payroll')

@section('content')
<style>
    .page-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 20px; flex-wrap: wrap; }
    .page-title-box h2 { margin: 0; font-size: 22px; font-weight: 700; color: var(--ink); letter-spacing: -.02em; }
    .page-title-box p { margin: 4px 0 0; color: var(--muted); font-size: 13px; }

    .nav-tabs { display: flex; gap: 8px; border-bottom: 1px solid var(--line); margin-bottom: 20px; }
    .nav-tab { padding: 10px 16px; font-size: 13.5px; font-weight: 600; text-decoration: none; color: var(--muted); border-bottom: 2px solid transparent; transition: all .15s ease; }
    .nav-tab:hover { color: var(--ink); }
    .nav-tab.is-active { color: var(--brand); border-bottom-color: var(--brand); }

    .filter-card { padding: 18px 20px; margin-bottom: 20px; }
    .filter-form { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
    .filter-group { min-width: 140px; }
    .form-control { width: 100%; padding: 9px 12px; border: 1px solid var(--line); border-radius: 8px; font-size: 13px; background: #fafbfc; color: var(--ink); outline: none; }
    .form-control:focus { border-color: var(--brand); background: #fff; box-shadow: 0 0 0 3px rgba(12, 143, 130, .1); }

    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; cursor: pointer; border: 1px solid transparent; transition: all .15s ease; }
    .btn-primary { background: var(--brand); color: #fff; }
    .btn-primary:hover { background: var(--brand-dark); }

    .table-container { overflow-x: auto; }
    .table-summary { width: 100%; border-collapse: collapse; text-align: left; }
    .table-summary th { padding: 13px 16px; background: #fbfcfc; color: var(--muted); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid var(--line); white-space: nowrap; }
    .table-summary td { padding: 14px 16px; border-bottom: 1px solid var(--line); font-size: 13px; vertical-align: middle; }
    .table-summary tbody tr:hover { background: #fafcfc; }
    .table-summary tfoot tr td { background: #f8fafc; font-weight: 700; border-top: 2px solid var(--line); padding: 14px 16px; font-size: 13.5px; }
</style>

<div class="page-header">
    <div class="page-title-box">
        <h2>Rekap Departemen</h2>
        <p>Agregasi dan ringkasan alokasi beban penggajian per departemen PTI</p>
    </div>
</div>

<nav class="nav-tabs" aria-label="Menu Tab Payroll">
    <a href="{{ route('payrolls.index') }}" class="nav-tab">Daftar Payroll</a>
    <a href="{{ route('payrolls.evaluation') }}" class="nav-tab">Evaluasi Pra-Finalisasi</a>
    <a href="{{ route('payrolls.department-summary') }}" class="nav-tab is-active">Rekap Departemen</a>
</nav>

<div class="card filter-card">
    <form action="{{ route('payrolls.department-summary') }}" method="GET" class="filter-form">
        <div class="filter-group">
            <select name="month" class="form-control">
                @foreach ($months as $num => $name)
                    <option value="{{ $num }}" {{ $currentMonth == $num ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="filter-group" style="max-width: 120px;">
            <select name="year" class="form-control">
                @for ($y = (int) date('Y') + 1; $y >= 2024; $y--)
                    <option value="{{ $y }}" {{ $currentYear == $y ? 'selected' : '' }}>
                        {{ $y }}
                    </option>
                @endfor
            </select>
        </div>

        <div>
            <button type="submit" class="btn btn-primary" style="padding: 9px 14px;">Tampilkan Rekap</button>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-container">
        <table class="table-summary">
            <thead>
                <tr>
                    <th>Departemen</th>
                    <th style="text-align: center;">Jumlah Karyawan</th>
                    <th style="text-align: right;">Total Gaji Pokok</th>
                    <th style="text-align: right;">Total Tunjangan</th>
                    <th style="text-align: right;">Total Potongan</th>
                    <th style="text-align: right;">Total Gaji Bersih</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $grandEmployees = 0;
                    $grandBasic = 0;
                    $grandAllowance = 0;
                    $grandDeduction = 0;
                    $grandNet = 0;
                @endphp
                @forelse ($summary as $row)
                    @php
                        $grandEmployees += (int) $row->total_employees;
                        $grandBasic += (float) $row->total_basic_salary;
                        $grandAllowance += (float) $row->total_allowance;
                        $grandDeduction += (float) $row->total_deduction;
                        $grandNet += (float) $row->total_net_salary;
                    @endphp
                    <tr>
                        <td><strong>{{ $row->name }}</strong></td>
                        <td style="text-align: center;">
                            <span style="font-weight: 600; padding: 2px 8px; border-radius: 999px; background: var(--canvas);">
                                {{ $row->total_employees }} Orang
                            </span>
                        </td>
                        <td style="text-align: right;">Rp {{ number_format((float) $row->total_basic_salary, 0, ',', '.') }}</td>
                        <td style="text-align: right; color: #0c8f82;">+Rp {{ number_format((float) $row->total_allowance, 0, ',', '.') }}</td>
                        <td style="text-align: right; color: #b92e2e;">-Rp {{ number_format((float) $row->total_deduction, 0, ',', '.') }}</td>
                        <td style="text-align: right;">
                            <strong style="color: var(--brand-dark);">
                                Rp {{ number_format((float) $row->total_net_salary, 0, ',', '.') }}
                            </strong>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--muted); padding: 36px;">
                            Tidak ada data penggajian untuk periode {{ $months[$currentMonth] ?? $currentMonth }} {{ $currentYear }}.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td>Total Keseluruhan</td>
                    <td style="text-align: center;">{{ $grandEmployees }} Orang</td>
                    <td style="text-align: right;">Rp {{ number_format($grandBasic, 0, ',', '.') }}</td>
                    <td style="text-align: right; color: #0c8f82;">+Rp {{ number_format($grandAllowance, 0, ',', '.') }}</td>
                    <td style="text-align: right; color: #b92e2e;">-Rp {{ number_format($grandDeduction, 0, ',', '.') }}</td>
                    <td style="text-align: right; color: var(--brand-dark); font-size: 15px;">
                        Rp {{ number_format($grandNet, 0, ',', '.') }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
