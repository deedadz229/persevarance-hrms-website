@extends('layouts.app')

@section('title', 'Detail Pengguna: ' . $user->name)

@section('content')
<style>
    .page-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
    .page-title-box h2 { margin: 0; font-size: 22px; font-weight: 700; color: var(--ink); letter-spacing: -.02em; }
    .page-title-box p { margin: 4px 0 0; color: var(--muted); font-size: 13px; }
    .header-actions { display: flex; align-items: center; gap: 10px; }
    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; cursor: pointer; border: 1px solid transparent; transition: all .15s ease; }
    .btn-primary { background: var(--brand); color: #fff; }
    .btn-primary:hover { background: var(--brand-dark); }
    .btn-outline { border-color: var(--line); background: var(--surface); color: var(--ink); }
    .btn-outline:hover { background: #f0f4f5; }
    .profile-card { padding: 24px; margin-bottom: 24px; }
    .profile-top { display: flex; align-items: center; gap: 18px; margin-bottom: 24px; border-bottom: 1px solid var(--line); padding-bottom: 20px; }
    .profile-avatar-large { display: grid; width: 64px; height: 64px; place-items: center; border-radius: 16px; background: var(--brand-soft); color: var(--brand-dark); font-weight: 800; font-size: 26px; }
    .profile-title h3 { margin: 0; font-size: 19px; font-weight: 700; color: var(--ink); }
    .profile-title p { margin: 4px 0 0; color: var(--muted); font-size: 13px; }
    .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; font-size: 13px; }
    .info-item { background: #fafbfc; padding: 14px 16px; border-radius: 8px; border: 1px solid var(--line); }
    .info-label { font-size: 11.5px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .04em; margin-bottom: 4px; display: block; }
    .info-value { font-weight: 600; color: var(--ink); font-size: 13.5px; }
    .badge-status { display: inline-flex; align-items: center; padding: 3px 8px; border-radius: 999px; font-size: 11.5px; font-weight: 600; }
    .badge-active { background: #e6f7f2; color: #0a7b6f; }
    .badge-inactive { background: #feebeb; color: #b92e2e; }
    .table-container { overflow-x: auto; }
    .table-audit { width: 100%; border-collapse: collapse; text-align: left; }
    .table-audit th { padding: 11px 16px; background: #fbfcfc; color: var(--muted); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid var(--line); }
    .table-audit td { padding: 12px 16px; border-bottom: 1px solid var(--line); font-size: 12.5px; vertical-align: middle; }
    .table-audit tbody tr:hover { background: #fafcfc; }
</style>

<div class="page-header">
    <div class="page-title-box">
        <h2>Profil Pengguna: {{ $user->name }}</h2>
        <p>Informasi akun dan riwayat aktivitas audit pengguna</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('users.edit', $user) }}" class="btn btn-primary">Edit Pengguna</a>
        <a href="{{ route('users.index') }}" class="btn btn-outline">&larr; Kembali</a>
    </div>
</div>

<div class="card profile-card">
    <div class="profile-top">
        <div class="profile-avatar-large">{{ str($user->name)->substr(0, 1)->upper() }}</div>
        <div class="profile-title">
            <h3>{{ $user->name }}</h3>
            <p>{{ $user->email }} · <span class="badge-status {{ $user->status === 'active' ? 'badge-active' : 'badge-inactive' }}">{{ $user->status === 'active' ? 'Akun Aktif' : 'Akun Non-Aktif' }}</span></p>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">Peran Pengguna (Role)</span>
            <div class="info-value">
                <span class="status-badge" style="font-size: 12px;">{{ $user->role?->label ?? 'Tanpa Role' }}</span>
            </div>
        </div>

        <div class="info-item">
            <span class="info-label">Karyawan Terkait</span>
            <div class="info-value">
                @if ($user->employee)
                    <a href="{{ route('employees.show', $user->employee) }}" style="color: var(--brand); text-decoration: none;">
                        {{ $user->employee->name }} ({{ $user->employee->employee_code }})
                    </a>
                @else
                    <span style="color: var(--muted);">-</span>
                @endif
            </div>
        </div>

        <div class="info-item">
            <span class="info-label">Login Terakhir</span>
            <div class="info-value">
                {{ $user->last_login_at ? $user->last_login_at->format('d F Y, H:i') . ' WIB' : 'Belum pernah login' }}
            </div>
        </div>

        <div class="info-item">
            <span class="info-label">Tanggal Dibuat</span>
            <div class="info-value">
                {{ $user->created_at ? $user->created_at->format('d/m/Y H:i') : '-' }}
            </div>
        </div>

        <div class="info-item">
            <span class="info-label">Terakhir Diperbarui</span>
            <div class="info-value">
                {{ $user->updated_at ? $user->updated_at->format('d/m/Y H:i') : '-' }}
            </div>
        </div>
    </div>
</div>

<div class="card" style="padding: 22px;">
    <h3 style="margin: 0 0 16px; font-size: 15px; font-weight: 700; color: var(--ink);">Riwayat Log Audit Pengguna</h3>
    <div class="table-container">
        <table class="table-audit">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Aksi</th>
                    <th>Entitas</th>
                    <th>ID</th>
                    <th>IP Address</th>
                    <th style="text-align: right;">Detail</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recentLogs as $log)
                    <tr>
                        <td style="font-family: monospace; color: var(--muted);">{{ $log->created_at ? $log->created_at->format('d/m/Y H:i') : '-' }}</td>
                        <td><strong>{{ $log->action }}</strong></td>
                        <td><code>{{ class_basename($log->entity_type) }}</code></td>
                        <td>{{ $log->entity_id ?? '-' }}</td>
                        <td style="font-family: monospace; color: var(--muted);">{{ $log->ip_address ?? '-' }}</td>
                        <td style="text-align: right;">
                            <a href="{{ route('audit-logs.show', $log) }}" style="color: var(--brand); font-weight: 600; text-decoration: none; font-size: 12px;">Lihat &rarr;</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 24px; color: var(--muted);">
                            Belum ada riwayat audit log untuk akun ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
