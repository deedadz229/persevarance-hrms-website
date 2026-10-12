@extends('layouts.app')

@section('title', __('reports.audit_report'))

@section('content')
<style>
    .page-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 20px; flex-wrap: wrap; }
    .page-title-box h2 { margin: 0; font-size: 22px; font-weight: 700; color: var(--ink); letter-spacing: -.02em; }
    .page-title-box p { margin: 4px 0 0; color: var(--muted); font-size: 13px; }
    .header-actions { display: flex; align-items: center; gap: 10px; }

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
    .table-report td { padding: 13px 16px; border-bottom: 1px solid var(--line); font-size: 13px; vertical-align: top; }
    .table-report tbody tr:hover { background: #fafcfc; }

    .badge-action { display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; font-family: monospace; background: #f1f5f9; color: #334155; }
    .badge-created { background: #e6f7f2; color: #0a7b6f; }
    .badge-updated { background: #fef3c7; color: #92400e; }
    .badge-deleted { background: #fee2e2; color: #991b1b; }
    .badge-approved { background: #e0f2fe; color: #0369a1; }
    .badge-rejected { background: #fce7f3; color: #9d174d; }

    .val-details { font-size: 12px; margin-top: 4px; }
    .val-details summary { cursor: pointer; color: var(--brand); font-weight: 600; outline: none; }
    .val-box { background: #f8fafc; border: 1px solid var(--line); border-radius: 6px; padding: 8px 10px; margin-top: 6px; font-family: monospace; font-size: 11px; max-width: 320px; overflow-x: auto; white-space: pre-wrap; word-break: break-all; }

    .pagination-wrapper { padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: var(--muted); border-top: 1px solid var(--line); }
    .empty-state { padding: 48px 20px; text-align: center; color: var(--muted); }
</style>

<div class="page-header">
    <div class="page-title-box">
        <h2>{{ __('reports.audit_report') }}</h2>
        <p>{{ __('reports.audit_subtitle') }}</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('reports.index') }}" class="btn btn-outline">
            <span>←</span> {{ __('reports.back_to_center') }}
        </a>
        @if ($canExport)
            <div style="display: inline-flex; gap: 8px;">
                <a href="{{ route('reports.audit-logs.export', array_merge(request()->query(), ['format' => 'csv'])) }}" class="btn btn-outline" title="{{ __('reports.export_csv') }}">
                    <span>⤓</span> CSV
                </a>
                <a href="{{ route('reports.audit-logs.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}" class="btn btn-success" title="{{ __('reports.export_excel') }}">
                    <span>⤓</span> Excel
                </a>
                <a href="{{ route('reports.audit-logs.export', array_merge(request()->query(), ['format' => 'pdf'])) }}" class="btn btn-outline" style="border-color: #e11d48; color: #e11d48;" title="{{ __('reports.export_pdf') }}">
                    <span>⤓</span> PDF
                </a>
            </div>
        @endif
    </div>
</div>

<div class="card filter-card">
    <form action="{{ route('reports.audit-logs') }}" method="GET" class="filter-form">
        <div class="filter-group search-group">
            <input type="text" name="search" value="{{ $currentSearch }}" placeholder="{{ __('audit_logs.entity_type_placeholder') }}" class="form-control">
        </div>

        <div class="filter-group">
            <select name="action" class="form-control">
                <option value="">{{ __('audit_logs.all_actions') }}</option>
                @foreach ($actions as $act)
                    <option value="{{ $act }}" {{ $currentAction === $act ? 'selected' : '' }}>
                        {{ $act }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="filter-group">
            <select name="user_id" class="form-control">
                <option value="">{{ __('audit_logs.all_users') }}</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}" {{ $currentUserId == $u->id ? 'selected' : '' }}>
                        {{ $u->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="filter-group" style="max-width: 160px;">
            <input type="date" name="date" value="{{ $currentDate }}" class="form-control" title="{{ __('audit_logs.recorded_time') }}">
        </div>

        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary" style="padding: 9px 14px;">{{ __('common.filter') }}</button>
            @if ($currentSearch || $currentAction || $currentUserId || $currentDate)
                <a href="{{ route('reports.audit-logs') }}" class="btn btn-outline" style="padding: 9px 12px;">{{ __('common.reset') }}</a>
            @endif
        </div>
    </form>
</div>

<div class="card">
    <div class="table-container">
        <table class="table-report">
            <thead>
                <tr>
                    <th>{{ __('audit_logs.time') }}</th>
                    <th>{{ __('audit_logs.user') }}</th>
                    <th>{{ __('audit_logs.action') }}</th>
                    <th>{{ __('audit_logs.entity') }}</th>
                    <th>{{ __('audit_logs.activity_info') }}</th>
                    <th>{{ __('audit_logs.ip_address') }} & {{ __('audit_logs.device_browser') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($auditLogs as $row)
                    @php
                        $actClass = 'badge-action';
                        if (str_contains($row->action, 'created')) $actClass .= ' badge-created';
                        elseif (str_contains($row->action, 'updated') || str_contains($row->action, 'corrected')) $actClass .= ' badge-updated';
                        elseif (str_contains($row->action, 'deleted')) $actClass .= ' badge-deleted';
                        elseif (str_contains($row->action, 'approved')) $actClass .= ' badge-approved';
                        elseif (str_contains($row->action, 'rejected')) $actClass .= ' badge-rejected';
                    @endphp
                    <tr>
                        <td style="white-space: nowrap;">
                            <strong>{{ $row->created_at ? $row->created_at->format('d M Y') : '-' }}</strong>
                            <small style="display: block; color: var(--muted);">{{ $row->created_at ? $row->created_at->format('H:i:s') : '-' }} WIB</small>
                        </td>
                        <td>
                            <strong>{{ $row->user?->name ?? __('audit_logs.system_guest') }}</strong>
                            <small style="display: block; color: var(--muted); font-size: 11.5px;">{{ $row->user?->email ?? '-' }}</small>
                        </td>
                        <td>
                            <span class="{{ $actClass }}">{{ $row->action }}</span>
                        </td>
                        <td>
                            <span style="font-weight: 600; color: var(--ink);">
                                {{ class_basename($row->entity_type ?? 'Entity') }}
                            </span>
                            @if ($row->entity_id)
                                <code style="display: block; margin-top: 2px;">#{{ $row->entity_id }}</code>
                            @endif
                        </td>
                        <td>
                            @if ($row->new_values || $row->old_values)
                                <details class="val-details">
                                    <summary>{{ __('audit_logs.view_detail') }} Payload</summary>
                                    @if ($row->old_values)
                                        <div class="val-box">
                                            <strong style="color: #991b1b;">Old:</strong>
                                            {{ json_encode($row->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}
                                        </div>
                                    @endif
                                    @if ($row->new_values)
                                        <div class="val-box">
                                            <strong style="color: #065f46;">New:</strong>
                                            {{ json_encode($row->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}
                                        </div>
                                    @endif
                                </details>
                            @else
                                <span style="color: var(--muted); font-size: 12px;">-</span>
                            @endif
                        </td>
                        <td style="font-size: 12px;">
                            <code>{{ $row->ip_address ?? '127.0.0.1' }}</code>
                            <small style="display: block; color: var(--muted); max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $row->user_agent }}">
                                {{ $row->user_agent ?? 'Browser / API' }}
                            </small>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-state">
                            <p style="font-size: 15px; margin: 0 0 6px;">{{ __('audit_logs.no_logs') }}</p>
                            <small>{{ __('audit_logs.no_logs_hint') }}</small>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($auditLogs->hasPages())
        <div class="pagination-wrapper">
            <span>{{ __('audit_logs.showing_records', ['from' => $auditLogs->firstItem(), 'to' => $auditLogs->lastItem(), 'total' => $auditLogs->total()]) }}</span>
            {{ $auditLogs->links() }}
        </div>
    @endif
</div>
@endsection
