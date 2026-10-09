@extends('layouts.app')

@section('title', 'Detail Role: ' . $role->label)

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
    .role-grid { display: grid; grid-template-columns: 320px 1fr; gap: 24px; }
    @media (max-width: 900px) { .role-grid { grid-template-columns: 1fr; } }
    .info-card { padding: 22px; }
    .info-card h3 { margin: 0 0 16px; font-size: 16px; font-weight: 700; color: var(--ink); border-bottom: 1px solid var(--line); padding-bottom: 10px; }
    .meta-row { margin-bottom: 14px; font-size: 13px; }
    .meta-row strong { display: block; color: var(--muted); font-size: 11.5px; text-transform: uppercase; margin-bottom: 3px; }
    .meta-row span { color: var(--ink); font-weight: 600; }
    .perm-group { margin-bottom: 20px; }
    .perm-group-title { font-size: 13.5px; font-weight: 700; color: var(--brand-dark); margin-bottom: 8px; display: flex; align-items: center; gap: 8px; }
    .perm-tags { display: flex; flex-wrap: wrap; gap: 8px; }
    .perm-tag { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 500; background: #e8f7f4; color: #076d65; border: 1px solid #c2ece5; }
    .user-list { list-style: none; margin: 0; padding: 0; }
    .user-item { display: flex; align-items: center; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--line); font-size: 12.5px; }
    .user-item:last-child { border-bottom: none; }
</style>

<div class="page-header">
    <div class="page-title-box">
        <h2>Peran: {{ $role->label }}</h2>
        <p>Detail izin dan daftar pengguna yang memiliki peran ini</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('roles.edit', $role) }}" class="btn btn-primary">Atur Izin Role</a>
        <a href="{{ route('roles.index') }}" class="btn btn-outline">&larr; Kembali</a>
    </div>
</div>

<div class="role-grid">
    <div>
        <div class="card info-card" style="margin-bottom: 20px;">
            <h3>Informasi Peran</h3>
            <div class="meta-row">
                <strong>Label Tampilan</strong>
                <span>{{ $role->label }}</span>
            </div>
            <div class="meta-row">
                <strong>Nama Slug Database</strong>
                <code>{{ $role->name }}</code>
            </div>
            <div class="meta-row">
                <strong>Deskripsi</strong>
                <span style="font-weight: normal; color: var(--muted);">{{ $role->description ?? 'Tidak ada deskripsi' }}</span>
            </div>
            <div class="meta-row">
                <strong>Total Izin Aktif</strong>
                <span>{{ $role->permissions->count() }} Izin</span>
            </div>
        </div>

        <div class="card info-card">
            <h3>Pengguna dengan Peran Ini ({{ $role->users->count() }})</h3>
            <ul class="user-list">
                @forelse ($role->users as $u)
                    <li class="user-item">
                        <div>
                            <strong>{{ $u->name }}</strong>
                            <div style="font-size: 11px; color: var(--muted);">{{ $u->email }}</div>
                        </div>
                        <span class="status-badge" style="font-size: 11px;">{{ $u->status }}</span>
                    </li>
                @empty
                    <li style="color: var(--muted); font-style: italic; padding: 10px 0;">Belum ada pengguna.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="card info-card">
        <h3>Daftar Izin Terpasang (Permissions)</h3>
        
        @if ($groupedPermissions->isEmpty())
            <div style="padding: 24px; text-align: center; color: var(--muted); font-style: italic;">
                Peran ini belum memiliki hak akses permission.
            </div>
        @else
            @foreach ($groupedPermissions as $groupName => $perms)
                <div class="perm-group">
                    <div class="perm-group-title">
                        <span>●</span> {{ $groupName }} ({{ $perms->count() }})
                    </div>
                    <div class="perm-tags">
                        @foreach ($perms as $p)
                            <div class="perm-tag" title="{{ $p->description }}">
                                <span>✓</span>
                                <code>{{ $p->name }}</code>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>
@endsection
