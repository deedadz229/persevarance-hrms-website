@extends('layouts.app')

@section('title', __('reports.attendance_report'))

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
    .badge-present { background: #e6f7f2; color: #0a7b6f; }
    .badge-late { background: #feebeb; color: #b92e2e; }
    .badge-wfh { background: #eaf1f8; color: #1e5a8a; }

    .pagination-wrapper { padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: var(--muted); border-top: 1px solid var(--line); }
    .empty-state { padding: 48px 20px; text-align: center; color: var(--muted); }

    @media (max-width: 1024px) { .summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
</style>

<div class="page-header">
    <div class="page-title-box">
        <h2>{{ __('reports.attendance_report') }}</h2>
        <p>{{ __('reports.attendance_subtitle') }}</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('reports.index') }}" class="btn btn-outline">
            <span>←</span> {{ __('reports.back_to_center') }}
        </a>
        @if ($canExport)
            <div style="display: inline-flex; gap: 8px;">
                <a href="{{ route('reports.attendances.export', array_merge(request()->query(), ['format' => 'csv'])) }}" class="btn btn-outline" title="{{ __('reports.export_csv') }}">
                    <span>⤓</span> CSV
                </a>
                <a href="{{ route('reports.attendances.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}" class="btn btn-success" title="{{ __('reports.export_excel') }}">
                    <span>⤓</span> Excel
                </a>
                <a href="{{ route('reports.attendances.export', array_merge(request()->query(), ['format' => 'pdf'])) }}" class="btn btn-outline" style="border-color: #e11d48; color: #e11d48;" title="{{ __('reports.export_pdf') }}">
                    <span>⤓</span> PDF
                </a>
            </div>
        @endif
    </div>
</div>

<section class="summary-grid" aria-label="Ringkasan Laporan Absensi">
    <article class="card summary-card">
        <span>{{ __('reports.total_attendance') }}</span>
        <strong style="color: var(--ink);">{{ number_format($summary['total']) }}</strong>
        <small>{{ __('reports.total_attendance_hint') }}</small>
    </article>
    <article class="card summary-card">
        <span>{{ __('reports.on_time') }}</span>
        <strong style="color: #0c8f82;">{{ number_format($summary['hadir']) }}</strong>
        <small>{{ __('reports.on_time_hint') }}</small>
    </article>
    <article class="card summary-card">
        <span>{{ __('reports.wfh') }}</span>
        <strong style="color: #2b7bb9;">{{ number_format($summary['wfh']) }}</strong>
        <small>{{ __('reports.wfh_hint') }}</small>
    </article>
    <article class="card summary-card">
        <span>{{ __('reports.late') }}</span>
        <strong style="color: #d93838;">{{ number_format($summary['telat']) }}</strong>
        <small>{{ __('reports.late_hint') }}</small>
    </article>
</section>

<div class="card filter-card">
    <form action="{{ route('reports.attendances') }}" method="GET" class="filter-form">
        <div class="filter-group search-group">
            <input type="text" name="search" value="{{ $currentSearch }}" placeholder="{{ __('attendances.search_placeholder') }}" class="form-control">
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
                <option value="">{{ __('attendances.all_departments') }}</option>
                @foreach ($departments as $dept)
                    <option value="{{ $dept->id }}" {{ $currentDepartment == $dept->id ? 'selected' : '' }}>
                        {{ $dept->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="filter-group">
            <select name="status" class="form-control">
                <option value="">{{ __('attendances.all_status') }}</option>
                <option value="hadir" {{ $currentStatus === 'hadir' ? 'selected' : '' }}>{{ __('attendances.status_hadir') }}</option>
                <option value="telat" {{ $currentStatus === 'telat' ? 'selected' : '' }}>{{ __('attendances.status_telat') }}</option>
                <option value="wfh" {{ $currentStatus === 'wfh' ? 'selected' : '' }}>{{ __('attendances.status_wfh') }}</option>
            </select>
        </div>

        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary" style="padding: 9px 14px;">{{ __('common.filter') }}</button>
            @if ($currentSearch || $currentMonth || $currentYear || $currentDepartment || $currentStatus)
                <a href="{{ route('reports.attendances') }}" class="btn btn-outline" style="padding: 9px 12px;" title="{{ __('common.reset') }}">{{ __('common.reset') }}</a>
            @endif
        </div>
    </form>
</div>

<div class="card">
    <div class="table-container">
        <table class="table-report">
            <thead>
                <tr>
                    <th>{{ __('attendances.employee') }}</th>
                    <th>{{ __('attendances.department') }}</th>
                    <th>{{ __('attendances.date') }}</th>
                    <th>{{ __('attendances.check_in') }}</th>
                    <th>{{ __('attendances.check_out') }}</th>
                    <th>{{ __('common.status') }}</th>
                    <th>{{ __('attendances.wifi_verified') }}</th>
                    <th>{{ __('attendances.location_status') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($attendances as $row)
                    @php($st = $row->computed_status)
                    <tr>
                        <td>
                            <div class="emp-info">
                                <strong>{{ $row->employee?->name ?? __('attendances.deleted_employee') }}</strong>
                                <span><code>{{ $row->employee?->employee_code ?? '-' }}</code></span>
                            </div>
                        </td>
                        <td>{{ $row->employee?->department?->name ?? '-' }}</td>
                        <td>{{ $row->attendance_date?->format('d M Y') }}</td>
                        <td><strong>{{ $row->check_in?->format('H:i') ?? '-' }}</strong></td>
                        <td>{{ $row->check_out?->format('H:i') ?? '-' }}</td>
                        <td>
                            @if ($st === 'wfh')
                                <span class="badge-status badge-wfh">WFH</span>
                            @elseif ($st === 'telat')
                                <span class="badge-status badge-late">{{ __('attendances.late') }}</span>
                            @else
                                <span class="badge-status badge-present">{{ __('attendances.on_time') }}</span>
                            @endif
                        </td>
                        <td>
                            @if ($row->wifi_verified)
                                <span style="color: #0c8f82; font-weight: 600;">{{ __('attendances.verified') }}</span>
                            @else
                                <span style="color: var(--muted);">{{ __('attendances.not_verified') }}</span>
                            @endif
                        </td>
                        <td>
                            {{ ucfirst($row->location_status ?? 'Recorded') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="empty-state">
                            <p style="margin: 0; font-weight: 600;">{{ __('attendances.no_attendance_records') }}</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($attendances->hasPages())
        <div class="pagination-wrapper">
            <span>{{ __('attendances.showing_records', ['from' => $attendances->firstItem(), 'to' => $attendances->lastItem(), 'total' => $attendances->total()]) }}</span>
            {{ $attendances->links() }}
        </div>
    @endif
</div>
@endsection
