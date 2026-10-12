@extends('layouts.app')

@section('title', 'Karyawan Resign')

@section('content')
<style>
    .page-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
    .page-title-box h2 { margin: 0; font-size: 22px; font-weight: 700; color: var(--ink); letter-spacing: -.02em; }
    .page-title-box p { margin: 4px 0 0; color: var(--muted); font-size: 13px; }
    .header-actions { display: flex; align-items: center; gap: 10px; }
    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; cursor: pointer; border: 1px solid transparent; transition: all .15s ease; }
    .btn-primary { background: var(--brand); color: #fff; }
    .btn-primary:hover { background: var(--brand-dark); }
    .btn-outline { border-color: var(--line); background: var(--surface); color: var(--ink); }
    .btn-outline:hover { background: #f0f4f5; }
    .filter-card { padding: 18px 20px; margin-bottom: 20px; }
    .filter-form { display: flex; gap: 14px; flex-wrap: wrap; align-items: center; }
    .filter-group { flex: 1; min-width: 180px; }
    .filter-group.search-group { flex: 2; min-width: 240px; }
    .form-control { width: 100%; padding: 9px 12px; border: 1px solid var(--line); border-radius: 8px; font-size: 13px; background: #fafbfc; color: var(--ink); outline: none; }
    .form-control:focus { border-color: var(--brand); background: #fff; box-shadow: 0 0 0 3px rgba(12, 143, 130, .1); }
    .table-container { overflow-x: auto; }
    .table-employee { width: 100%; border-collapse: collapse; text-align: left; }
    .table-employee th { padding: 13px 18px; background: #fbfcfc; color: var(--muted); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid var(--line); }
    .table-employee td { padding: 14px 18px; border-bottom: 1px solid var(--line); font-size: 13px; vertical-align: middle; }
    .table-employee tbody tr:hover { background: #fafcfc; }
    .emp-info strong { display: block; font-weight: 600; color: var(--ink); font-size: 13.5px; }
    .emp-info span { display: block; margin-top: 2px; color: var(--muted); font-size: 12px; }
    .badge-status { display: inline-flex; align-items: center; padding: 3px 8px; border-radius: 999px; font-size: 11.5px; font-weight: 600; }
    .badge-resigned { background: #feebeb; color: #b92e2e; }
    .badge-type { display: inline-block; padding: 2px 7px; border-radius: 6px; font-size: 11px; font-weight: 500; background: #f0f4f7; color: #465561; }
    .action-links { display: flex; align-items: center; gap: 8px; }
    .action-link { padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; border: 1px solid var(--line); color: var(--ink); background: #fff; }
    .action-link:hover { border-color: var(--brand); color: var(--brand-dark); background: var(--brand-soft); }
    .pagination-wrapper { padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: var(--muted); border-top: 1px solid var(--line); }
    .empty-state { padding: 48px 20px; text-align: center; color: var(--muted); }
    .info-alert { padding: 12px 16px; border-radius: 8px; background: #fdf8e6; border: 1px solid #f6e8b8; color: #7d5f0b; font-size: 13px; margin-bottom: 20px; }
</style>

<div class="page-header">
    <div class="page-title-box">
        <h2>{{ __('employees.resigned_title') }}</h2>
        <p>{{ __('employees.resigned_subtitle') }}</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('employees.index') }}" class="btn btn-outline">
            <span>←</span> {{ __('common.back') }}
        </a>
    </div>
</div>

<div class="info-alert">
    ℹ <strong>Data Historis:</strong> Data karyawan yang mengundurkan diri tetap tersimpan permanen di database untuk keperluan rekapitulasi, riwayat absensi, penggajian terdahulu, dan audit sistem.
</div>

<div class="card filter-card">
    <form action="{{ route('employees.resigned') }}" method="GET" class="filter-form">
        <div class="filter-group search-group">
            <input type="text" name="search" value="{{ $currentSearch }}" placeholder="{{ __('employees.search_placeholder') }}" class="form-control">
        </div>
        <div class="filter-group">
            <select name="department_id" class="form-control">
                <option value="">{{ __('employees.all_departments') }}</option>
                @foreach ($departments as $dept)
                    <option value="{{ $dept->id }}" {{ $currentDepartment == $dept->id ? 'selected' : '' }}>
                        {{ $dept->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary" style="padding: 9px 14px;">{{ __('common.filter') }}</button>
            @if ($currentSearch || $currentDepartment)
                <a href="{{ route('employees.resigned') }}" class="btn btn-outline" style="padding: 9px 12px;" title="{{ __('common.reset') }}">{{ __('common.reset') }}</a>
            @endif
        </div>
    </form>
</div>

<div class="card">
    <div class="table-container">
        <table class="table-employee">
            <thead>
                <tr>
                    <th>{{ __('employees.employee') }}</th>
                    <th>{{ __('employees.employee_code') }}</th>
                    <th>{{ __('employees.department') }}</th>
                    <th>{{ __('employees.position') }}</th>
                    <th>{{ __('employees.contract') }}</th>
                    <th>{{ __('employees.joined_at') }}</th>
                    <th>{{ __('employees.resigned_date') }}</th>
                    <th>{{ __('common.status') }}</th>
                    <th style="text-align: right;">{{ __('common.action') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employees as $employee)
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                @if ($employee->photo_url)
                                    <img src="{{ $employee->photo_url }}" alt="{{ $employee->name }}" style="width: 32px; height: 32px; border-radius: 8px; object-fit: cover; flex-shrink: 0; border: 1px solid var(--line);">
                                @else
                                    <div style="width: 32px; height: 32px; border-radius: 8px; background: var(--brand-soft); color: var(--brand-dark); font-weight: 700; font-size: 13px; display: grid; place-items: center; flex-shrink: 0;">
                                        {{ str($employee->name)->substr(0, 1)->upper() }}
                                    </div>
                                @endif
                                <div class="emp-info">
                                    <strong>{{ $employee->name }}</strong>
                                    <span>{{ $employee->email }}</span>
                                </div>
                            </div>
                        </td>
                        <td><code>{{ $employee->employee_code }}</code></td>
                        <td>{{ $employee->department?->name ?? '-' }}</td>
                        <td>{{ $employee->position?->name ?? '-' }}</td>
                        <td>
                            <span class="badge-type">{{ ucfirst($employee->employment_type) }}</span>
                        </td>
                        <td>{{ $employee->join_date?->format('d M Y') ?? '-' }}</td>
                        <td>
                            <strong style="color: #b92e2e;">{{ $employee->resigned_at?->format('d M Y') ?? '-' }}</strong>
                        </td>
                        <td>
                            <span class="badge-status badge-resigned">{{ __('employees.status_resigned') }}</span>
                        </td>
                        <td style="text-align: right;">
                            <div class="action-links" style="justify-content: flex-end;">
                                <a href="{{ route('employees.show', $employee) }}" class="action-link" title="{{ __('common.detail') }}">{{ __('common.detail') }}</a>
                                @if (auth()->user()->role?->name !== 'taiwan_account' && auth()->user()->role?->permissions->contains('name', 'manage_employees'))
                                    <a href="{{ route('employees.edit', $employee) }}" class="action-link" title="{{ __('common.edit') }}">{{ __('common.edit') }}</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="empty-state">
                            {{ __('employees.no_records') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($employees->hasPages())
        <div class="pagination-wrapper">
            <div>{{ __('employees.showing_records', ['from' => $employees->firstItem() ?? 0, 'to' => $employees->lastItem() ?? 0, 'total' => $employees->total()]) }}</div>
            <div>{{ $employees->links() }}</div>
        </div>
    @endif
</div>
@endsection
