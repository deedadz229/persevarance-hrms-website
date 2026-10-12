@extends('layouts.app')

@section('title', __('reports.leave_report'))

@section('content')
<style>
    .page-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 20px; flex-wrap: wrap; }
    .page-title-box h2 { margin: 0; font-size: 22px; font-weight: 700; color: var(--ink); letter-spacing: -.02em; }
    .page-title-box p { margin: 4px 0 0; color: var(--muted); font-size: 13px; }
    .header-actions { display: flex; align-items: center; gap: 10px; }

    .summary-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin-bottom: 20px; }
    .summary-card { padding: 18px 20px; }
    .summary-card span { color: var(--muted); font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
    .summary-card strong { display: block; margin-top: 8px; font-size: 26px; letter-spacing: -.03em; }
    .summary-card small { display: block; margin-top: 4px; color: #81909a; font-size: 11.5px; }

    .filter-card { padding: 18px 20px; margin-bottom: 20px; }
    .filter-form { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
    .filter-group { flex: 1; min-width: 140px; }
    .filter-group.search-group { flex: 2; min-width: 200px; }
    .form-control { width: 100%; padding: 9px 12px; border: 1px solid var(--line); border-radius: 8px; font-size: 13px; background: #fafbfc; color: var(--ink); outline: none; }
    .form-control:focus { border-color: var(--brand); background: #fff; box-shadow: 0 0 0 3px rgba(12, 143, 130, .1); }

    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; cursor: pointer; border: 1px solid transparent; transition: all .15s ease; }
    .btn-primary { background: var(--brand); color: #fff; }
    .btn-primary:hover { background: var(--brand-dark); }
    .btn-outline { border-color: var(--line); background: var(--surface); color: var(--ink); }
    .btn-outline:hover { background: #f0f4f5; }
    .btn-success { background: #0c8f82; color: #fff; }
    .btn-success:hover { background: #076d65; }

    .table-container { overflow-x: auto; }
    .table-report { width: 100%; border-collapse: collapse; text-align: left; }
    .table-report th { padding: 13px 16px; background: #fbfcfc; color: var(--muted); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid var(--line); white-space: nowrap; }
    .table-report td { padding: 13px 16px; border-bottom: 1px solid var(--line); font-size: 13px; vertical-align: middle; }
    .table-report tbody tr:hover { background: #fafcfc; }

    .emp-info strong { display: block; font-weight: 600; color: var(--ink); font-size: 13px; }
    .emp-info span { display: block; margin-top: 2px; color: var(--muted); font-size: 11.5px; }

    .badge-status { display: inline-flex; align-items: center; padding: 3px 9px; border-radius: 999px; font-size: 11.5px; font-weight: 600; }
    .badge-pending { background: #fff4dd; color: #9a6a00; }
    .badge-approved { background: #e6f7f2; color: #0a7b6f; }
    .badge-rejected { background: #feebeb; color: #b92e2e; }

    .pagination-wrapper { padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: var(--muted); border-top: 1px solid var(--line); }
    .empty-state { padding: 48px 20px; text-align: center; color: var(--muted); }

    @media (max-width: 1024px) { .summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
</style>

<div class="page-header">
    <div class="page-title-box">
        <h2>{{ __('reports.leave_report') }}</h2>
        <p>{{ __('reports.leave_subtitle') }}</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('reports.index') }}" class="btn btn-outline">
            <span>←</span> {{ __('reports.back_to_center') }}
        </a>
        @if ($canExport)
            <div style="display: inline-flex; gap: 8px;">
                <a href="{{ route('reports.leaves.export', array_merge(request()->query(), ['format' => 'csv'])) }}" class="btn btn-outline" title="{{ __('reports.export_csv') }}">
                    <span>⤓</span> CSV
                </a>
                <a href="{{ route('reports.leaves.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}" class="btn btn-success" title="{{ __('reports.export_excel') }}">
                    <span>⤓</span> Excel
                </a>
                <a href="{{ route('reports.leaves.export', array_merge(request()->query(), ['format' => 'pdf'])) }}" class="btn btn-outline" style="border-color: #e11d48; color: #e11d48;" title="{{ __('reports.export_pdf') }}">
                    <span>⤓</span> PDF
                </a>
            </div>
        @endif
    </div>
</div>

<section class="summary-grid" aria-label="Ringkasan Laporan Cuti">
    <article class="card summary-card">
        <span>{{ __('leave.total_requests') }}</span>
        <strong style="color: var(--ink);">{{ number_format($summary['total']) }}</strong>
        <small>{{ __('reports.total_leave_hint') }}</small>
    </article>
    <article class="card summary-card">
        <span>{{ __('leave.approved') }}</span>
        <strong style="color: #0c8f82;">{{ number_format($summary['approved']) }}</strong>
        <small>{{ __('reports.approved_leaves_hint') }}</small>
    </article>
    <article class="card summary-card">
        <span>{{ __('leave.pending_approval') }}</span>
        <strong style="color: #c98804;">{{ number_format($summary['pending']) }}</strong>
        <small>{{ __('reports.pending_leaves_hint') }}</small>
    </article>
    <article class="card summary-card">
        <span>{{ __('leave.rejected') }}</span>
        <strong style="color: #d93838;">{{ number_format($summary['rejected']) }}</strong>
        <small>{{ __('reports.rejected_leaves_hint') }}</small>
    </article>
</section>

<div class="card filter-card">
    <form action="{{ route('reports.leaves') }}" method="GET" class="filter-form">
        <div class="filter-group search-group">
            <input type="text" name="search" value="{{ $currentSearch }}" placeholder="{{ __('leave.search_placeholder') }}" class="form-control">
        </div>

        <div class="filter-group">
            <select name="month" class="form-control">
                <option value="">{{ __('payrolls.all_months') }}</option>
                @foreach ($months as $num => $name)
                    <option value="{{ $num }}" {{ $currentMonth == $num ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="filter-group" style="max-width: 120px;">
            <select name="year" class="form-control">
                <option value="">{{ __('payrolls.all_years') }}</option>
                @for ($y = (int) date('Y') + 1; $y >= 2024; $y--)
                    <option value="{{ $y }}" {{ $currentYear == $y ? 'selected' : '' }}>
                        {{ $y }}
                    </option>
                @endfor
            </select>
        </div>

        <div class="filter-group">
            <select name="department_id" class="form-control">
                <option value="">{{ __('leave.all_departments') }}</option>
                @foreach ($departments as $dept)
                    <option value="{{ $dept->id }}" {{ $currentDepartment == $dept->id ? 'selected' : '' }}>
                        {{ $dept->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="filter-group">
            <select name="leave_type" class="form-control">
                <option value="">{{ __('leave.all_leave_types') }}</option>
                <option value="Cuti Tahunan" {{ $currentLeaveType === 'Cuti Tahunan' ? 'selected' : '' }}>Cuti Tahunan</option>
                <option value="Izin Sakit" {{ $currentLeaveType === 'Izin Sakit' ? 'selected' : '' }}>Izin Sakit</option>
                <option value="Izin Pribadi" {{ $currentLeaveType === 'Izin Pribadi' ? 'selected' : '' }}>Izin Pribadi</option>
                <option value="Cuti Khusus" {{ $currentLeaveType === 'Cuti Khusus' ? 'selected' : '' }}>Cuti Khusus</option>
            </select>
        </div>

        <div class="filter-group">
            <select name="status" class="form-control">
                <option value="">{{ __('leave.all_status') }}</option>
                <option value="approved" {{ $currentStatus === 'approved' ? 'selected' : '' }}>{{ __('leave.status_approved') }}</option>
                <option value="pending" {{ $currentStatus === 'pending' ? 'selected' : '' }}>{{ __('leave.status_pending') }}</option>
                <option value="rejected" {{ $currentStatus === 'rejected' ? 'selected' : '' }}>{{ __('leave.status_rejected') }}</option>
            </select>
        </div>

        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary" style="padding: 9px 14px;">{{ __('common.filter') }}</button>
            @if ($currentSearch || $currentMonth || $currentYear || $currentDepartment || $currentStatus || $currentLeaveType)
                <a href="{{ route('reports.leaves') }}" class="btn btn-outline" style="padding: 9px 12px;">{{ __('common.reset') }}</a>
            @endif
        </div>
    </form>
</div>

<div class="card">
    <div class="table-container">
        <table class="table-report">
            <thead>
                <tr>
                    <th>{{ __('leave.employee') }}</th>
                    <th>{{ __('leave.department') }}</th>
                    <th>{{ __('leave.leave_type_label') }}</th>
                    <th>{{ __('leave.date_period') }}</th>
                    <th>{{ __('leave.duration') }}</th>
                    <th>{{ __('leave.reason') }}</th>
                    <th>{{ __('common.status') }}</th>
                    <th>{{ __('leave.approval_verif') ?? 'Persetujuan' }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($leaves as $row)
                    <tr>
                        <td>
                            <div class="emp-info">
                                <strong>{{ $row->employee?->name ?? __('attendances.deleted_employee') }}</strong>
                                <span><code>{{ $row->employee?->employee_code ?? '-' }}</code></span>
                            </div>
                        </td>
                        <td>{{ $row->employee?->department?->name ?? '-' }}</td>
                        <td><strong>{{ $row->leave_type }}</strong></td>
                        <td>
                            {{ $row->start_date ? $row->start_date->format('d M Y') : '-' }}
                            –
                            {{ $row->end_date ? $row->end_date->format('d M Y') : '-' }}
                        </td>
                        <td>
                            <span style="font-weight: 600; color: var(--brand-dark);">
                                {{ $row->duration_label }}
                            </span>
                        </td>
                        <td style="max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $row->reason }}">
                            {{ $row->reason }}
                        </td>
                        <td>
                            <span class="badge-status {{ $row->status_badge_class }}">
                                {{ $row->status_label }}
                            </span>
                        </td>
                        <td>
                            @if ($row->status === 'approved')
                                <span style="color: #0c8f82; font-weight: 600;">✓ {{ __('leave.approved') }}</span>
                                <small style="display: block; color: var(--muted); font-size: 11px;">oleh {{ $row->approver?->name ?? 'HR' }}</small>
                            @elseif ($row->status === 'rejected')
                                <span style="color: #d93838; font-weight: 600;">✗ {{ __('leave.rejected') }}</span>
                                <small style="display: block; color: var(--muted); font-size: 11px;">oleh {{ $row->approver?->name ?? 'HR' }}</small>
                            @else
                                <span style="color: #c98804;">{{ __('leave.status_pending') }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="empty-state">
                            <p style="font-size: 15px; margin: 0 0 6px;">{{ __('leave.no_requests') }}</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($leaves->hasPages())
        <div class="pagination-wrapper">
            <span>{{ __('leave.showing_records', ['from' => $leaves->firstItem(), 'to' => $leaves->lastItem(), 'total' => $leaves->total()]) }}</span>
            {{ $leaves->links() }}
        </div>
    @endif
</div>
@endsection
