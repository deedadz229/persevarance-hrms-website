@extends('layouts.app')

@section('title', __('attendances.title'))

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
    .btn-export { background: #1b8a5a; color: #fff; }
    .btn-export:hover { background: #136a44; }

    .summary-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin-bottom: 24px; }
    .summary-card { padding: 18px 20px; }
    .summary-card span { color: var(--muted); font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
    .summary-card strong { display: block; margin-top: 8px; font-size: 26px; letter-spacing: -.03em; }
    .summary-card small { display: block; margin-top: 4px; color: #81909a; font-size: 11.5px; }

    .filter-card { padding: 18px 20px; margin-bottom: 20px; }
    .filter-form { display: flex; gap: 14px; flex-wrap: wrap; align-items: center; }
    .filter-group { flex: 1; min-width: 160px; }
    .filter-group.search-group { flex: 2; min-width: 220px; }
    .form-control { width: 100%; padding: 9px 12px; border: 1px solid var(--line); border-radius: 8px; font-size: 13px; background: #fafbfc; color: var(--ink); outline: none; }
    .form-control:focus { border-color: var(--brand); background: #fff; box-shadow: 0 0 0 3px rgba(12, 143, 130, .1); }

    .table-container { overflow-x: auto; }
    .table-attendance { width: 100%; border-collapse: collapse; text-align: left; }
    .table-attendance th { padding: 13px 16px; background: #fbfcfc; color: var(--muted); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid var(--line); white-space: nowrap; }
    .table-attendance td { padding: 14px 16px; border-bottom: 1px solid var(--line); font-size: 13px; vertical-align: middle; }
    .table-attendance tbody tr:hover { background: #fafcfc; }

    .emp-info strong { display: block; font-weight: 600; color: var(--ink); font-size: 13.5px; }
    .emp-info span { display: block; margin-top: 2px; color: var(--muted); font-size: 12px; }

    .badge-status { display: inline-flex; align-items: center; padding: 3px 9px; border-radius: 999px; font-size: 11.5px; font-weight: 600; }
    .badge-hadir { background: #e6f7f2; color: #0a7b6f; }
    .badge-telat { background: #fff4dd; color: #9a6a00; }
    .badge-wfh { background: #e8f0fe; color: #1a73e8; }
    .badge-absen { background: #feebeb; color: #b92e2e; }

    .badge-pill { display: inline-flex; align-items: center; padding: 2px 7px; border-radius: 6px; font-size: 11px; font-weight: 600; }
    .badge-verified { background: #e6f7f2; color: #0a7b6f; }
    .badge-unverified { background: #f1f3f5; color: #707e8a; }
    .badge-failed { background: #feebeb; color: #b92e2e; }

    .gps-coords { font-size: 11.5px; font-family: monospace; color: #495763; }
    .action-link { padding: 5px 11px; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; border: 1px solid var(--line); color: var(--ink); background: #fff; }
    .action-link:hover { border-color: var(--brand); color: var(--brand-dark); background: var(--brand-soft); }
    .pagination-wrapper { padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: var(--muted); border-top: 1px solid var(--line); }
    .empty-state { padding: 48px 20px; text-align: center; color: var(--muted); }

    @media (max-width: 1024px) { .summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
</style>

<div class="page-header">
    <div class="page-title-box">
        <h2>{{ __('attendances.title') }}</h2>
        <p>{{ __('attendances.subtitle') }}</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('attendances.export', request()->query()) }}" class="btn btn-export" title="{{ __('attendances.export_csv') }}">
            <span>↓</span> {{ __('attendances.export_csv') }}
        </a>
    </div>
</div>

<section class="summary-grid" aria-label="{{ __('attendances.summary_aria') }}">
    <article class="card summary-card">
        <span>{{ __('attendances.on_time') }}</span>
        <strong style="color: #0c8f82;">{{ number_format($summary['hadir']) }}</strong>
        <small>{{ __('attendances.on_time_hint', ['date' => \Carbon\Carbon::parse($summary['date'])->format('d M Y')]) }}</small>
    </article>
    <article class="card summary-card">
        <span>{{ __('attendances.wfh') }}</span>
        <strong style="color: #1a73e8;">{{ number_format($summary['wfh']) }}</strong>
        <small>{{ __('attendances.wfh_hint') }}</small>
    </article>
    <article class="card summary-card">
        <span>{{ __('attendances.late') }}</span>
        <strong style="color: #c98804;">{{ number_format($summary['telat']) }}</strong>
        <small>{{ __('attendances.late_hint') }}</small>
    </article>
    <article class="card summary-card">
        <span>{{ __('attendances.absent') }}</span>
        <strong style="color: #d93838;">{{ number_format($summary['absen']) }}</strong>
        <small>{{ __('attendances.absent_hint') }}</small>
    </article>
</section>

<div class="card filter-card">
    <form action="{{ route('attendances.index') }}" method="GET" class="filter-form">
        <div class="filter-group">
            <input type="date" name="date" value="{{ $currentDate }}" class="form-control" title="{{ __('attendances.date_title') }}">
        </div>
        <div class="filter-group search-group">
            <input type="text" name="search" value="{{ $currentSearch }}" placeholder="{{ __('attendances.search_placeholder') }}" class="form-control">
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
                <option value="" {{ empty($currentStatus) ? 'selected' : '' }}>{{ __('attendances.all_status') }}</option>
                <option value="hadir" {{ $currentStatus === 'hadir' ? 'selected' : '' }}>{{ __('attendances.status_hadir') }}</option>
                <option value="wfh" {{ $currentStatus === 'wfh' ? 'selected' : '' }}>{{ __('attendances.status_wfh') }}</option>
                <option value="telat" {{ $currentStatus === 'telat' ? 'selected' : '' }}>{{ __('attendances.status_telat') }}</option>
                <option value="absen" {{ $currentStatus === 'absen' ? 'selected' : '' }}>{{ __('attendances.status_absen') }}</option>
            </select>
        </div>
        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary" style="padding: 9px 14px;">{{ __('common.filter') }}</button>
            @if ($currentDate !== now()->toDateString() || $currentSearch || $currentDepartment || $currentStatus)
                <a href="{{ route('attendances.index') }}" class="btn btn-outline" style="padding: 9px 12px;" title="{{ __('common.reset') }}">{{ __('common.reset') }}</a>
            @endif
        </div>
    </form>
</div>

<div class="card">
    <div class="table-container">
        @if ($currentStatus === 'absen')
            {{-- Special table for absent employees --}}
            <table class="table-attendance">
                <thead>
                    <tr>
                        <th>{{ __('attendances.employee') }}</th>
                        <th>{{ __('attendances.employee_id') }}</th>
                        <th>{{ __('attendances.department') }}</th>
                        <th>{{ __('attendances.position') }}</th>
                        <th>{{ __('attendances.recap_date') }}</th>
                        <th>{{ __('common.status') }}</th>
                        <th>{{ __('attendances.notes') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($absentEmployees as $employee)
                        <tr>
                            <td>
                                <div class="emp-info">
                                    <strong>{{ $employee->name }}</strong>
                                    <span>{{ $employee->email }}</span>
                                </div>
                            </td>
                            <td><code>{{ $employee->employee_code }}</code></td>
                            <td>{{ $employee->department?->name ?? '-' }}</td>
                            <td>{{ $employee->position?->name ?? '-' }}</td>
                            <td>{{ \Carbon\Carbon::parse($currentDate ?: now())->format('d M Y') }}</td>
                            <td><span class="badge-status badge-absen">Absen</span></td>
                            <td style="color: var(--muted); font-size: 12px;">{{ __('attendances.no_check_in_note') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty-state">
                                {{ __('attendances.no_absent_employees') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if ($absentEmployees->hasPages())
                <div class="pagination-wrapper">
                    <div>{{ __('attendances.showing_absent', ['from' => $absentEmployees->firstItem() ?? 0, 'to' => $absentEmployees->lastItem() ?? 0, 'total' => $absentEmployees->total()]) }}</div>
                    <div>{{ $absentEmployees->links() }}</div>
                </div>
            @endif
        @else
            {{-- Standard table for attendances --}}
            <table class="table-attendance">
                <thead>
                    <tr>
                        <th>{{ __('attendances.employee') }}</th>
                        <th>{{ __('attendances.department') }}</th>
                        <th>{{ __('attendances.date') }}</th>
                        <th>{{ __('attendances.check_in') }}</th>
                        <th>{{ __('attendances.check_out') }}</th>
                        <th>{{ __('common.status') }}</th>
                        <th>{{ __('attendances.wifi_verified') }}</th>
                        <th>{{ __('attendances.wifi_ip') }}</th>
                        <th>{{ __('attendances.photo_proof') }}</th>
                        <th>{{ __('attendances.gps_coords') }}</th>
                        <th style="text-align: right;">{{ __('common.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attendances as $attendance)
                        @php
                            $computedStatus = $attendance->computed_status;
                        @endphp
                        <tr>
                            <td>
                                <div class="emp-info">
                                    <strong>{{ $attendance->employee?->name ?? __('attendances.deleted_employee') }}</strong>
                                    <span><code>{{ $attendance->employee?->employee_code ?? '-' }}</code></span>
                                </div>
                            </td>
                            <td>{{ $attendance->employee?->department?->name ?? '-' }}</td>
                            <td>{{ $attendance->attendance_date?->format('d M Y') ?? '-' }}</td>
                            <td>
                                <strong>{{ $attendance->check_in?->format('H:i') ?? '-' }}</strong>
                            </td>
                            <td>
                                {{ $attendance->check_out?->format('H:i') ?? '-' }}
                            </td>
                            <td>
                                @if ($computedStatus === 'wfh')
                                    <span class="badge-status badge-wfh">WFH</span>
                                @elseif ($computedStatus === 'telat')
                                    <span class="badge-status badge-telat">Telat</span>
                                @else
                                    <span class="badge-status badge-hadir">Hadir</span>
                                @endif
                            </td>
                            <td>
                                @if ($attendance->wifi_verified)
                                    <span class="badge-pill badge-verified">{{ __('attendances.verified') }}</span>
                                @else
                                    <span class="badge-pill badge-unverified">{{ __('attendances.not_verified') }}</span>
                                @endif
                            </td>
                            <td>
                                <span style="font-family: monospace; font-size: 12px; color: var(--muted);">
                                    {{ $attendance->ip_address ?? '-' }}
                                </span>
                            </td>
                            <td>
                                @if ($attendance->photo_path)
                                    <span title="{{ __('attendances.photo_available') }}" style="color: #0c8f82; font-weight: 600; font-size: 12px;">{{ __('attendances.photo_available') }}</span>
                                @else
                                    <span style="color: #8c9ba5; font-size: 12px;">-</span>
                                @endif
                            </td>
                            <td>
                                @if ($attendance->latitude && $attendance->longitude)
                                    <div class="gps-coords">
                                        {{ number_format($attendance->latitude, 4) }}, {{ number_format($attendance->longitude, 4) }}
                                    </div>
                                    <span class="badge-pill {{ strtolower($attendance->location_status) === 'verified' ? 'badge-verified' : 'badge-unverified' }}" style="margin-top: 2px;">
                                        {{ ucfirst($attendance->location_status ?? 'Recorded') }}
                                    </span>
                                @else
                                    <span style="color: var(--muted); font-size: 12px;">-</span>
                                @endif
                            </td>
                            <td style="text-align: right;">
                                <a href="{{ route('attendances.show', $attendance) }}" class="action-link" title="{{ __('common.detail') }}">{{ __('common.detail') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="empty-state">
                                {{ __('attendances.no_attendance_records') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if ($attendances->hasPages())
                <div class="pagination-wrapper">
                    <div>{{ __('attendances.showing_records', ['from' => $attendances->firstItem() ?? 0, 'to' => $attendances->lastItem() ?? 0, 'total' => $attendances->total()]) }}</div>
                    <div>{{ $attendances->links() }}</div>
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
