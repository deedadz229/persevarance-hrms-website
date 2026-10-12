@extends('layouts.app')

@section('title', __('departments.detail_department', ['name' => $department->name]))

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
    .detail-grid { display: grid; grid-template-columns: 340px 1fr; gap: 24px; }
    @media (max-width: 960px) { .detail-grid { grid-template-columns: 1fr; } }
    .meta-card { padding: 24px; }
    .meta-title { font-size: 15px; font-weight: 700; color: var(--ink); margin-bottom: 18px; padding-bottom: 12px; border-bottom: 1px solid var(--line); }
    .meta-item { margin-bottom: 16px; }
    .meta-label { font-size: 11.5px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .04em; margin-bottom: 4px; }
    .meta-value { font-size: 13.5px; color: var(--ink); font-weight: 500; }
    .badge-status { display: inline-flex; align-items: center; padding: 3px 8px; border-radius: 999px; font-size: 11.5px; font-weight: 600; }
    .badge-active { background: #e6f7f2; color: #0a7b6f; }
    .badge-inactive { background: #f1f3f5; color: #5f6c77; }
    .employees-card { padding: 0; }
    .table-container { overflow-x: auto; }
    .table-dept-emp { width: 100%; border-collapse: collapse; text-align: left; }
    .table-dept-emp th { padding: 13px 18px; background: #fbfcfc; color: var(--muted); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid var(--line); }
    .table-dept-emp td { padding: 13px 18px; border-bottom: 1px solid var(--line); font-size: 13px; vertical-align: middle; }
    .table-dept-emp tbody tr:hover { background: #fafcfc; }
    .emp-info strong { display: block; font-weight: 600; color: var(--ink); font-size: 13px; }
    .emp-info span { display: block; margin-top: 2px; color: var(--muted); font-size: 11.5px; }
    .pagination-wrapper { padding: 14px 18px; display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: var(--muted); border-top: 1px solid var(--line); }
    .empty-state { padding: 40px 20px; text-align: center; color: var(--muted); }
</style>

<div class="page-header">
    <div class="page-title-box">
        <h2>{{ __('departments.detail_department', ['name' => $department->name]) }}</h2>
        <p>{{ __('departments.detail_department_subtitle') }}</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('departments.index') }}" class="btn btn-outline">
            <span>←</span> {{ __('common.back') }}
        </a>
        @if (auth()->user()->role?->name === 'super_admin')
            <a href="{{ route('departments.edit', $department) }}" class="btn btn-primary">
                <span>✎</span> {{ __('departments.edit_department') }}
            </a>
        @endif
    </div>
</div>

<div class="detail-grid">
    <div class="card meta-card">
        <div class="meta-title">{{ __('departments.department_info') }}</div>

        <div class="meta-item">
            <div class="meta-label">{{ __('departments.department_name') }}</div>
            <div class="meta-value" style="font-size: 16px; font-weight: 700; color: var(--brand-dark);">
                {{ $department->name }}
            </div>
        </div>

        <div class="meta-item">
            <div class="meta-label">{{ __('departments.operational_status') }}</div>
            <div class="meta-value">
                @if ($department->status === 'active')
                    <span class="badge-status badge-active">{{ __('departments.active_val') }}</span>
                @else
                    <span class="badge-status badge-inactive">{{ __('departments.inactive_val') }}</span>
                @endif
            </div>
        </div>

        <div class="meta-item">
            <div class="meta-label">{{ __('departments.total_employees_registered') }}</div>
            <div class="meta-value" style="font-weight: 700;">
                👥 {{ $department->employees_count }} {{ __('departments.people') }}
            </div>
        </div>

        <div class="meta-item">
            <div class="meta-label">{{ __('departments.description') }}</div>
            <div class="meta-value" style="line-height: 1.5; color: #4b5563;">
                {{ $department->description ?: __('departments.no_description') }}
            </div>
        </div>

        <div class="meta-item" style="margin-bottom: 0;">
            <div class="meta-label">{{ __('departments.created_at') }}</div>
            <div class="meta-value" style="font-size: 12.5px; color: var(--muted);">
                {{ $department->created_at ? $department->created_at->format('d M Y, H:i') : '-' }}
            </div>
        </div>
    </div>

    <div class="card employees-card">
        <div style="padding: 20px 22px 14px; border-bottom: 1px solid var(--line); display: flex; align-items: center; justify-content: space-between;">
            <h3 style="margin: 0; font-size: 15px; font-weight: 700; color: var(--ink);">
                {{ __('departments.employees_in_department', ['count' => $department->employees_count]) }}
            </h3>
        </div>

        <div class="table-container">
            <table class="table-dept-emp">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>{{ __('employees.employee') }}</th>
                        <th>{{ __('employees.employee_code_label') }}</th>
                        <th>{{ __('employees.position') }}</th>
                        <th>{{ __('common.status') }}</th>
                        <th style="text-align: right;">{{ __('common.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $employee)
                        <tr>
                            <td>{{ $employees->firstItem() ? $employees->firstItem() + $loop->index : $loop->iteration }}</td>
                            <td>
                                <div class="emp-info">
                                    <strong>{{ $employee->name }}</strong>
                                    <span>{{ $employee->email }}</span>
                                </div>
                            </td>
                            <td><code>{{ $employee->employee_code }}</code></td>
                            <td>{{ $employee->position?->name ?? '-' }}</td>
                            <td>
                                @if ($employee->status === 'active')
                                    <span class="badge-status badge-active">Active</span>
                                @elseif ($employee->status === 'inactive')
                                    <span class="badge-status badge-inactive">Inactive</span>
                                @elseif ($employee->status === 'resigned')
                                    <span class="badge-status" style="background: #fee2e2; color: #991b1b;">Resigned</span>
                                @else
                                    <span class="badge-status">{{ ucfirst($employee->status) }}</span>
                                @endif
                            </td>
                            <td style="text-align: right;">
                                <a href="{{ route('employees.show', $employee) }}" class="btn btn-outline" style="padding: 4px 10px; font-size: 11.5px;">{{ __('common.detail') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <p style="margin: 0; font-weight: 600;">{{ __('departments.no_employees_in_dept') }}</p>
                                    <span style="font-size: 12px; margin-top: 4px; display: block;">{{ __('departments.no_employees_hint') }}</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($employees->hasPages())
            <div class="pagination-wrapper">
                <div>
                    {{ __('departments.showing_employees', ['from' => $employees->firstItem(), 'to' => $employees->lastItem(), 'total' => $employees->total()]) }}
                </div>
                <div>
                    {{ $employees->links() }}
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
