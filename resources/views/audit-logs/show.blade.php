@extends('layouts.app')

@section('title', 'Detail Audit Log')

@section('content')
<style>
    .page-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
    .page-title-box h2 { margin: 0; font-size: 22px; font-weight: 700; color: var(--ink); letter-spacing: -.02em; }
    .page-title-box p { margin: 4px 0 0; color: var(--muted); font-size: 13px; }
    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; cursor: pointer; border: 1px solid transparent; transition: all .15s ease; }
    .btn-outline { border-color: var(--line); background: var(--surface); color: var(--ink); }
    .btn-outline:hover { background: #f0f4f5; }
    .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }
    @media (max-width: 900px) { .detail-grid { grid-template-columns: 1fr; } }
    .card-section { padding: 22px; }
    .card-section h3 { margin: 0 0 16px; font-size: 15px; font-weight: 700; color: var(--ink); border-bottom: 1px solid var(--line); padding-bottom: 10px; }
    .info-list { display: grid; grid-template-columns: 140px 1fr; row-gap: 12px; column-gap: 16px; font-size: 13px; }
    .info-label { color: var(--muted); font-weight: 600; }
    .info-val { color: var(--ink); word-break: break-all; }
    .badge-action { display: inline-flex; align-items: center; padding: 3px 8px; border-radius: 999px; font-size: 12px; font-weight: 600; }
    .badge-blue { background: #e8f4fd; color: #1971c2; }
    .badge-green { background: #e6f7f2; color: #0a7b6f; }
    .badge-red { background: #feebeb; color: #b92e2e; }
    .badge-orange { background: #fff4e6; color: #d9480f; }
    .badge-purple { background: #f3f0ff; color: #6741d9; }
    .badge-gray { background: #f1f3f5; color: #495057; }
    .values-table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 12.5px; }
    .values-table th { padding: 8px 12px; background: #fafbfc; color: var(--muted); font-weight: 600; text-align: left; border-bottom: 1px solid var(--line); }
    .values-table td { padding: 8px 12px; border-bottom: 1px solid var(--line); vertical-align: top; }
    .val-key { font-weight: 600; color: var(--ink); font-family: ui-monospace, monospace; width: 35%; }
    .val-content { word-break: break-word; color: #2b3a4a; }
    .empty-note { padding: 20px; text-align: center; color: var(--muted); font-style: italic; background: #fafbfc; border-radius: 8px; }
</style>

<div class="page-header">
    <div class="page-title-box">
        <h2>Detail Audit Log #{{ $auditLog->id }}</h2>
        <p>Rekam riwayat perubahan aktivitas dan data pada sistem</p>
    </div>
    <div>
        <a href="{{ route('audit-logs.index') }}" class="btn btn-outline">
            &larr; Kembali ke Daftar Log
        </a>
    </div>
</div>

<div class="card card-section" style="margin-bottom: 24px;">
    <h3>Informasi Aktivitas</h3>
    <div class="info-list">
        <span class="info-label">Waktu Pencatatan</span>
        <span class="info-val">
            <strong>{{ $auditLog->created_at ? $auditLog->created_at->format('d F Y, H:i:s') . ' WIB' : '-' }}</strong>
        </span>

        <span class="info-label">Pengguna (User)</span>
        <span class="info-val">
            @if ($auditLog->user)
                <strong>{{ $auditLog->user->name }}</strong> ({{ $auditLog->user->email }})
                <span class="status-badge" style="margin-left: 8px; font-size: 11px;">{{ $auditLog->user->role?->label ?? 'Role' }}</span>
            @else
                <span style="color: var(--muted); font-style: italic;">Sistem / Eksternal</span>
            @endif
        </span>

        <span class="info-label">Aksi (Action)</span>
        <span class="info-val">
            @php
                $badgeClass = match(true) {
                    str_contains($auditLog->action, 'login') => 'badge-blue',
                    str_contains($auditLog->action, 'logout') => 'badge-gray',
                    str_contains($auditLog->action, 'created') || str_contains($auditLog->action, 'activated') => 'badge-green',
                    str_contains($auditLog->action, 'approved') => 'badge-green',
                    str_contains($auditLog->action, 'rejected') || str_contains($auditLog->action, 'deactivated') => 'badge-red',
                    str_contains($auditLog->action, 'updated') || str_contains($auditLog->action, 'correction') => 'badge-orange',
                    str_contains($auditLog->action, 'export') => 'badge-purple',
                    default => 'badge-gray'
                };
            @endphp
            <span class="badge-action {{ $badgeClass }}">
                {{ $auditLog->action }}
            </span>
        </span>

        <span class="info-label">Tipe Entitas</span>
        <span class="info-val">
            <code>{{ $auditLog->entity_type ?? 'Tidak spesifik' }}</code>
        </span>

        <span class="info-label">ID Entitas</span>
        <span class="info-val">
            <strong>{{ $auditLog->entity_id ?? '-' }}</strong>
        </span>

        <span class="info-label">Alamat IP</span>
        <span class="info-val" style="font-family: ui-monospace, monospace;">
            {{ $auditLog->ip_address ?? 'Tidak tercatat' }}
        </span>

        <span class="info-label">User Agent</span>
        <span class="info-val" style="color: var(--muted); font-size: 12px;">
            {{ $auditLog->user_agent ?? 'Tidak tercatat' }}
        </span>
    </div>
</div>

<div class="detail-grid">
    <div class="card card-section">
        <h3 style="color: #a33e3e;">Nilai Lama (Old Values)</h3>
        @if (!empty($auditLog->old_values) && is_array($auditLog->old_values))
            <table class="values-table">
                <thead>
                    <tr>
                        <th>Kolom / Field</th>
                        <th>Nilai Sebelum Perubahan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($auditLog->old_values as $key => $val)
                        <tr>
                            <td class="val-key">{{ $key }}</td>
                            <td class="val-content">
                                @if (is_array($val) || is_object($val))
                                    <pre style="margin: 0; font-size: 11px; background: #fafbfc; padding: 6px; border-radius: 4px;">{{ json_encode($val, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                @elseif (is_null($val))
                                    <span style="color: var(--muted); font-style: italic;">null</span>
                                @elseif (is_bool($val))
                                    <span>{{ $val ? 'true' : 'false' }}</span>
                                @else
                                    <span>{{ (string) $val }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty-note">Tidak ada data nilai lama</div>
        @endif
    </div>

    <div class="card card-section">
        <h3 style="color: #0a7b6f;">Nilai Baru (New Values)</h3>
        @if (!empty($auditLog->new_values) && is_array($auditLog->new_values))
            <table class="values-table">
                <thead>
                    <tr>
                        <th>Kolom / Field</th>
                        <th>Nilai Setelah Perubahan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($auditLog->new_values as $key => $val)
                        <tr>
                            <td class="val-key">{{ $key }}</td>
                            <td class="val-content">
                                @if (is_array($val) || is_object($val))
                                    <pre style="margin: 0; font-size: 11px; background: #fafbfc; padding: 6px; border-radius: 4px;">{{ json_encode($val, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                @elseif (is_null($val))
                                    <span style="color: var(--muted); font-style: italic;">null</span>
                                @elseif (is_bool($val))
                                    <span>{{ $val ? 'true' : 'false' }}</span>
                                @else
                                    <span>{{ (string) $val }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty-note">Tidak ada data nilai baru</div>
        @endif
    </div>
</div>
@endsection
