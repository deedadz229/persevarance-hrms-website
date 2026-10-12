@extends('layouts.app')

@section('title', __('payrolls.evaluation_title') . ' · ' . __('payrolls.title'))

@section('content')
<style>
    .page-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 20px; flex-wrap: wrap; }
    .page-title-box h2 { margin: 0; font-size: 22px; font-weight: 700; color: var(--ink); letter-spacing: -.02em; }
    .page-title-box p { margin: 4px 0 0; color: var(--muted); font-size: 13px; }

    .nav-tabs { display: flex; gap: 8px; border-bottom: 1px solid var(--line); margin-bottom: 20px; }
    .nav-tab { padding: 10px 16px; font-size: 13.5px; font-weight: 600; text-decoration: none; color: var(--muted); border-bottom: 2px solid transparent; transition: all .15s ease; }
    .nav-tab:hover { color: var(--ink); }
    .nav-tab.is-active { color: var(--brand); border-bottom-color: var(--brand); }

    .info-banner { padding: 14px 18px; border-radius: 9px; background: #eaf6f5; border: 1px solid #bce6d9; color: #0a5f57; font-size: 13px; margin-bottom: 20px; line-height: 1.5; }

    .filter-card { padding: 18px 20px; margin-bottom: 20px; }
    .filter-form { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
    .filter-group { min-width: 140px; }
    .form-control { width: 100%; padding: 9px 12px; border: 1px solid var(--line); border-radius: 8px; font-size: 13px; background: #fafbfc; color: var(--ink); outline: none; }
    .form-control:focus { border-color: var(--brand); background: #fff; box-shadow: 0 0 0 3px rgba(12, 143, 130, .1); }

    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; cursor: pointer; border: 1px solid transparent; transition: all .15s ease; }
    .btn-primary { background: var(--brand); color: #fff; }
    .btn-primary:hover { background: var(--brand-dark); }
    .btn-outline { border-color: var(--line); background: var(--surface); color: var(--ink); }
    .btn-outline:hover { background: #f0f4f5; }

    .table-container { overflow-x: auto; }
    .table-eval { width: 100%; border-collapse: collapse; text-align: left; }
    .table-eval th { padding: 13px 16px; background: #fbfcfc; color: var(--muted); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid var(--line); white-space: nowrap; }
    .table-eval td { padding: 14px 16px; border-bottom: 1px solid var(--line); font-size: 13px; vertical-align: middle; }
    .table-eval tbody tr:hover { background: #fafcfc; }

    .emp-info strong { display: block; font-weight: 600; color: var(--ink); font-size: 13.5px; }
    .emp-info span { display: block; margin-top: 2px; color: var(--muted); font-size: 12px; }

    .badge-status { display: inline-flex; align-items: center; padding: 3px 9px; border-radius: 999px; font-size: 11.5px; font-weight: 600; }
    .badge-draft { background: #fff4dd; color: #9a6a00; }
    .badge-generated { background: #e6f7f2; color: #0a7b6f; }
    .badge-paid { background: #eaf1f8; color: #1e5a8a; }

    .action-group { display: flex; align-items: center; gap: 6px; justify-content: flex-end; }
    .action-link { padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; border: 1px solid var(--line); color: var(--ink); background: #fff; }
    .action-link:hover { border-color: var(--brand); color: var(--brand-dark); background: var(--brand-soft); }
</style>

<div class="page-header">
    <div class="page-title-box">
        <h2>{{ __('payrolls.evaluation_title') }}</h2>
        <p>{{ __('payrolls.evaluation_subtitle') }}</p>
    </div>
</div>

<nav class="nav-tabs" aria-label="Menu Tab Payroll">
    <a href="{{ route('payrolls.index') }}" class="nav-tab">{{ __('payrolls.tab_list') }}</a>
    <a href="{{ route('payrolls.evaluation') }}" class="nav-tab is-active">{{ __('payrolls.tab_evaluation') }}</a>
    <a href="{{ route('payrolls.department-summary') }}" class="nav-tab">{{ __('payrolls.tab_department_summary') }}</a>
</nav>

<div class="info-banner">
    <strong>ℹ Panduan Evaluasi:</strong> Halaman ini menyajikan data penggajian bersama ringkasan kehadiran (hari kerja) dan lembur yang telah disetujui selama periode bersangkutan. Jika terdapat ketidaksesuaian nilai tunjangan atau potongan, lakukan penyesuaian melalui menu <strong>{{ __('payrolls.correction') }}</strong> dengan mencantumkan alasan resmi.
</div>

<div class="card filter-card">
    <form action="{{ route('payrolls.evaluation') }}" method="GET" class="filter-form">
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

        <div class="filter-group">
            <select name="department_id" class="form-control">
                <option value="">{{ __('payrolls.all_departments') }}</option>
                @foreach ($departments as $dept)
                    <option value="{{ $dept->id }}" {{ $currentDepartment == $dept->id ? 'selected' : '' }}>
                        {{ $dept->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <button type="submit" class="btn btn-primary" style="padding: 9px 14px;">{{ __('common.filter') }}</button>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-container">
        <table class="table-eval">
            <thead>
                <tr>
                    <th>{{ __('payrolls.employee') }}</th>
                    <th>{{ __('payrolls.department') }}</th>
                    <th style="text-align: center;">Ref. Hadir</th>
                    <th style="text-align: center;">Ref. Lembur</th>
                    <th>{{ __('payrolls.basic_salary') }}</th>
                    <th>{{ __('payrolls.allowances') }}</th>
                    <th>{{ __('payrolls.deductions') }}</th>
                    <th>{{ __('payrolls.net_salary') }}</th>
                    <th>{{ __('common.status') }}</th>
                    <th style="text-align: right;">{{ __('common.action') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payrolls as $payroll)
                    <tr>
                        <td>
                            <div class="emp-info">
                                <strong>{{ $payroll->employee?->name ?? 'Karyawan dihapus' }}</strong>
                                <span><code>{{ $payroll->employee?->employee_code ?? '-' }}</code></span>
                            </div>
                        </td>
                        <td>{{ $payroll->employee?->department?->name ?? '-' }}</td>
                        <td style="text-align: center;">
                            <span style="font-weight: 600; color: #0c8f82;">
                                {{ $payroll->reference_attendance_days }} Hari
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <span style="font-weight: 600; color: #2b7bb9;">
                                {{ number_format((float) $payroll->reference_overtime_hours, 1) }} Jam
                            </span>
                        </td>
                        <td>Rp {{ number_format((float) $payroll->basic_salary, 0, ',', '.') }}</td>
                        <td style="color: #0c8f82;">+Rp {{ number_format((float) $payroll->total_allowance, 0, ',', '.') }}</td>
                        <td style="color: #b92e2e;">-Rp {{ number_format((float) $payroll->total_deduction, 0, ',', '.') }}</td>
                        <td>
                            <strong style="color: var(--brand-dark); font-size: 14px;">
                                Rp {{ number_format((float) $payroll->net_salary, 0, ',', '.') }}
                            </strong>
                        </td>
                        <td>
                            <span class="badge-status {{ $payroll->status_badge_class }}">
                                {{ $payroll->status_label }}
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <div class="action-group">
                                <a href="{{ route('payrolls.show', $payroll) }}" class="action-link" title="{{ __('payrolls.detail') }}">{{ __('payrolls.detail') }}</a>
                                @if (auth()->user()->role?->name !== 'taiwan_account' && (auth()->user()->role?->name === 'super_admin' || auth()->user()->role?->permissions->contains('name', 'manage_payroll')))
                                    <a href="{{ route('payrolls.correction', $payroll) }}" class="action-link" style="color: #9a6a00;" title="{{ __('payrolls.correction') }}">{{ __('payrolls.correction') }}</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" style="text-align: center; color: var(--muted); padding: 40px;">
                            {{ __('payrolls.no_records') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
