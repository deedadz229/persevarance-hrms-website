@extends('layouts.app')

@section('title', 'Audit Trail')

@section('content')
<style>
    .page-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
    .page-title-box h2 { margin: 0; font-size: 22px; font-weight: 700; color: var(--ink); letter-spacing: -.02em; }
    .page-title-box p { margin: 4px 0 0; color: var(--muted); font-size: 13px; }
    .filter-card { padding: 18px 20px; margin-bottom: 20px; }
    .filter-form { display: flex; gap: 14px; flex-wrap: wrap; align-items: flex-end; }
    .filter-group { flex: 1; min-width: 170px; }
    .filter-group label { display: block; font-size: 12px; font-weight: 600; color: var(--muted); margin-bottom: 6px; }
    .form-control { width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 8px; font-size: 13px; background: #fafbfc; color: var(--ink); outline: none; }
    .form-control:focus { border-color: var(--brand); background: #fff; box-shadow: 0 0 0 3px rgba(12, 143, 130, .1); }
    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; cursor: pointer; border: 1px solid transparent; transition: all .15s ease; }
    .btn-primary { background: var(--brand); color: #fff; }
    .btn-primary:hover { background: var(--brand-dark); }
    .btn-outline { border-color: var(--line); background: var(--surface); color: var(--ink); }
    .btn-outline:hover { background: #f0f4f5; }
    .table-container { overflow-x: auto; }
    .table-audit { width: 100%; border-collapse: collapse; text-align: left; }
    .table-audit th { padding: 13px 18px; background: #fbfcfc; color: var(--muted); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid var(--line); }
    .table-audit td { padding: 14px 18px; border-bottom: 1px solid var(--line); font-size: 13px; vertical-align: middle; }
    .table-audit tbody tr:hover { background: #fafcfc; }
    .badge-action { display: inline-flex; align-items: center; padding: 3px 8px; border-radius: 999px; font-size: 11.5px; font-weight: 600; }
    .badge-blue { background: #e8f4fd; color: #1971c2; }
    .badge-green { background: #e6f7f2; color: #0a7b6f; }
    .badge-red { background: #feebeb; color: #b92e2e; }
    .badge-orange { background: #fff4e6; color: #d9480f; }
    .badge-purple { background: #f3f0ff; color: #6741d9; }
    .badge-gray { background: #f1f3f5; color: #495057; }
    .action-link { padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; border: 1px solid var(--line); color: var(--ink); background: #fff; display: inline-flex; align-items: center; gap: 4px; }
    .action-link:hover { border-color: var(--brand); color: var(--brand-dark); background: var(--brand-soft); }
    .pagination-wrapper { padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: var(--muted); border-top: 1px solid var(--line); }
    .empty-state { padding: 48px 20px; text-align: center; color: var(--muted); }
    .mono-cell { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 12px; }
    .ua-cell { max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
</style>

<div class="page-header">
    <div class="page-title-box">
        <h2>Audit Trail Sistem</h2>
        <p>Rekam jejak seluruh aktivitas penting, perubahan data, dan otentikasi pengguna pada website HRMS</p>
    </div>
</div>

<div class="card filter-card">
    <form action="{{ route('audit-logs.index') }}" method="GET" class="filter-form">
        <div class="filter-group">
            <label for="user_id">Pengguna</label>
            <select name="user_id" id="user_id" class="form-control">
                <option value="">-- Semua Pengguna --</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                        {{ $u->name }} ({{ $u->email }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="filter-group">
            <label for="action">Aksi</label>
            <select name="action" id="action" class="form-control">
                <option value="">-- Semua Aksi --</option>
                @foreach ($availableActions as $act)
                    <option value="{{ $act }}" {{ request('action') == $act ? 'selected' : '' }}>
                        {{ $act }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="filter-group">
            <label for="entity_type">Tipe Entitas</label>
            <input type="text" name="entity_type" id="entity_type" class="form-control" placeholder="Contoh: User, Employee" value="{{ request('entity_type') }}">
        </div>

        <div class="filter-group" style="max-width: 150px;">
            <label for="date_from">Dari Tanggal</label>
            <input type="date" name="date_from" id="date_from" class="form-control" value="{{ request('date_from') }}">
        </div>

        <div class="filter-group" style="max-width: 150px;">
            <label for="date_to">Sampai Tanggal</label>
            <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request('date_to') }}">
        </div>

        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="{{ route('audit-logs.index') }}" class="btn btn-outline">Reset</a>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-container">
        <table class="table-audit">
            <thead>
                <tr>
                    <th>Waktu (WIB)</th>
                    <th>Pengguna</th>
                    <th>Aksi</th>
                    <th>Entitas</th>
                    <th>ID</th>
                    <th>IP Address</th>
                    <th>Perangkat / Browser</th>
                    <th style="text-align: right;">Detail</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($auditLogs as $log)
                    @php
                        $badgeClass = match(true) {
                            str_contains($log->action, 'login') => 'badge-blue',
                            str_contains($log->action, 'logout') => 'badge-gray',
                            str_contains($log->action, 'created') || str_contains($log->action, 'activated') => 'badge-green',
                            str_contains($log->action, 'approved') => 'badge-green',
                            str_contains($log->action, 'rejected') || str_contains($log->action, 'deactivated') => 'badge-red',
                            str_contains($log->action, 'updated') || str_contains($log->action, 'correction') => 'badge-orange',
                            str_contains($log->action, 'export') => 'badge-purple',
                            default => 'badge-gray'
                        };
                        $shortEntity = $log->entity_type ? class_basename($log->entity_type) : '-';
                    @endphp
                    <tr>
                        <td class="mono-cell" style="white-space: nowrap; color: var(--muted);">
                            {{ $log->created_at ? $log->created_at->format('d/m/Y H:i:s') : '-' }}
                        </td>
                        <td>
                            @if ($log->user)
                                <strong>{{ $log->user->name }}</strong>
                                <span style="display: block; font-size: 11px; color: var(--muted);">{{ $log->user->email }}</span>
                            @else
                                <span style="color: var(--muted); font-style: italic;">Sistem / Tamu</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge-action {{ $badgeClass }}">
                                {{ $log->action }}
                            </span>
                        </td>
                        <td>
                            <code style="font-size: 12px; background: #f0f4f7; padding: 2px 6px; border-radius: 4px;">{{ $shortEntity }}</code>
                        </td>
                        <td class="mono-cell">
                            {{ $log->entity_id ?? '-' }}
                        </td>
                        <td class="mono-cell" style="color: var(--muted);">
                            {{ $log->ip_address ?? '-' }}
                        </td>
                        <td class="ua-cell" title="{{ $log->user_agent }}">
                            <span style="font-size: 12px; color: var(--muted);">{{ $log->user_agent ?? '-' }}</span>
                        </td>
                        <td style="text-align: right;">
                            <a href="{{ route('audit-logs.show', $log) }}" class="action-link">
                                <span>Lihat</span> &rarr;
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <div style="font-size: 32px; margin-bottom: 8px;">◉</div>
                                <strong>Tidak ada log audit ditemukan</strong>
                                <p style="margin-top: 4px; font-size: 12px;">Coba sesuaikan filter pencarian Anda.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($auditLogs->hasPages())
        <div class="pagination-wrapper">
            <span>Menampilkan {{ $auditLogs->firstItem() ?? 0 }} - {{ $auditLogs->lastItem() ?? 0 }} dari {{ $auditLogs->total() }} log audit</span>
            {{ $auditLogs->links() }}
        </div>
    @endif
</div>
@endsection
