@extends('layouts.app')

@section('title', 'Manajemen Pengguna')

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
    .filter-form { display: flex; gap: 14px; flex-wrap: wrap; align-items: flex-end; }
    .filter-group { flex: 1; min-width: 180px; }
    .filter-group.search-group { flex: 2; min-width: 240px; }
    .filter-group label { display: block; font-size: 12px; font-weight: 600; color: var(--muted); margin-bottom: 6px; }
    .form-control { width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 8px; font-size: 13px; background: #fafbfc; color: var(--ink); outline: none; }
    .form-control:focus { border-color: var(--brand); background: #fff; box-shadow: 0 0 0 3px rgba(12, 143, 130, .1); }
    .table-container { overflow-x: auto; }
    .table-users { width: 100%; border-collapse: collapse; text-align: left; }
    .table-users th { padding: 13px 18px; background: #fbfcfc; color: var(--muted); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid var(--line); }
    .table-users td { padding: 14px 18px; border-bottom: 1px solid var(--line); font-size: 13px; vertical-align: middle; }
    .table-users tbody tr:hover { background: #fafcfc; }
    .user-info { display: flex; align-items: center; gap: 12px; }
    .user-avatar { display: grid; width: 34px; height: 34px; place-items: center; border-radius: 50%; background: var(--brand-soft); color: var(--brand-dark); font-weight: 700; font-size: 13px; flex-shrink: 0; }
    .user-meta strong { display: block; font-weight: 600; color: var(--ink); }
    .user-meta span { display: block; margin-top: 1px; color: var(--muted); font-size: 12px; }
    .badge-status { display: inline-flex; align-items: center; padding: 3px 8px; border-radius: 999px; font-size: 11.5px; font-weight: 600; }
    .badge-active { background: #e6f7f2; color: #0a7b6f; }
    .badge-inactive { background: #feebeb; color: #b92e2e; }
    .badge-role { display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 11.5px; font-weight: 600; background: #f0f4f7; color: #2d3748; }
    .role-super { background: #f3f0ff; color: #6741d9; border: 1px solid #d0bfff; }
    .role-hrd { background: #e8f4fd; color: #1971c2; border: 1px solid #a5d8ff; }
    .role-taiwan { background: #fff4e6; color: #d9480f; border: 1px solid #ffd8a8; }
    .action-links { display: flex; align-items: center; gap: 6px; }
    .action-link { padding: 5px 9px; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; border: 1px solid var(--line); color: var(--ink); background: #fff; cursor: pointer; transition: all .15s ease; }
    .action-link:hover { border-color: var(--brand); color: var(--brand-dark); background: var(--brand-soft); }
    .btn-toggle-deactivate { color: #b92e2e; border-color: #f3caca; }
    .btn-toggle-deactivate:hover { background: #feebeb; border-color: #b92e2e; color: #9c1414; }
    .btn-toggle-activate { color: #0a7b6f; border-color: #bce6d9; }
    .btn-toggle-activate:hover { background: #e6f7f2; border-color: #0a7b6f; color: #06544c; }
    .pagination-wrapper { padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: var(--muted); border-top: 1px solid var(--line); }
    .empty-state { padding: 48px 20px; text-align: center; color: var(--muted); }
</style>

<div class="page-header">
    <div class="page-title-box">
        <h2>Manajemen Pengguna</h2>
        <p>Kelola akun pengguna, peranan (role), hubungan karyawan, dan status akses website HRMS</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('users.create') }}" class="btn btn-primary">
            <span>+</span> Tambah Pengguna
        </a>
    </div>
</div>

<div class="card filter-card">
    <form action="{{ route('users.index') }}" method="GET" class="filter-form">
        <div class="filter-group search-group">
            <label for="search">Cari Nama / Email</label>
            <input type="text" name="search" id="search" class="form-control" placeholder="Ketik nama atau alamat email pengguna..." value="{{ request('search') }}">
        </div>

        <div class="filter-group">
            <label for="role_id">Peran (Role)</label>
            <select name="role_id" id="role_id" class="form-control">
                <option value="">-- Semua Role --</option>
                @foreach ($roles as $r)
                    <option value="{{ $r->id }}" {{ request('role_id') == $r->id ? 'selected' : '' }}>
                        {{ $r->label }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="filter-group" style="max-width: 170px;">
            <label for="status">Status Akun</label>
            <select name="status" id="status" class="form-control">
                <option value="">-- Semua Status --</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Non-Aktif</option>
            </select>
        </div>

        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="{{ route('users.index') }}" class="btn btn-outline">Reset</a>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-container">
        <table class="table-users">
            <thead>
                <tr>
                    <th>Pengguna</th>
                    <th>Role</th>
                    <th>Karyawan Terkait</th>
                    <th>Status</th>
                    <th>Login Terakhir</th>
                    <th style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $u)
                    @php
                        $roleClass = match($u->role?->name) {
                            'super_admin' => 'role-super',
                            'manager_hrd' => 'role-hrd',
                            'taiwan_account' => 'role-taiwan',
                            default => ''
                        };
                    @endphp
                    <tr>
                        <td>
                            <div class="user-info">
                                <div class="user-avatar">{{ str($u->name)->substr(0, 1)->upper() }}</div>
                                <div class="user-meta">
                                    <strong>{{ $u->name }}</strong>
                                    <span>{{ $u->email }}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge-role {{ $roleClass }}">
                                {{ $u->role?->label ?? 'Tanpa Role' }}
                            </span>
                        </td>
                        <td>
                            @if ($u->employee)
                                <strong>{{ $u->employee->name }}</strong>
                                <span style="display: block; font-size: 11px; color: var(--muted);">{{ $u->employee->employee_code }}</span>
                            @else
                                <span style="color: var(--muted);">-</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge-status {{ $u->status === 'active' ? 'badge-active' : 'badge-inactive' }}">
                                {{ $u->status === 'active' ? 'Aktif' : 'Non-Aktif' }}
                            </span>
                        </td>
                        <td style="color: var(--muted); font-size: 12.5px;">
                            {{ $u->last_login_at ? $u->last_login_at->format('d/m/Y H:i') : 'Belum pernah login' }}
                        </td>
                        <td style="text-align: right;">
                            <div class="action-links" style="justify-content: flex-end;">
                                <a href="{{ route('users.show', $u) }}" class="action-link" title="Detail Pengguna">Detail</a>
                                <a href="{{ route('users.edit', $u) }}" class="action-link" title="Edit Pengguna">Edit</a>
                                
                                @if ($u->id !== auth()->id())
                                    <form method="POST" action="{{ route('users.toggle-status', $u) }}" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin {{ $u->status === 'active' ? 'menonaktifkan' : 'mengaktifkan' }} akun {{ $u->name }}?');">
                                        @csrf
                                        <button type="submit" class="action-link {{ $u->status === 'active' ? 'btn-toggle-deactivate' : 'btn-toggle-activate' }}">
                                            {{ $u->status === 'active' ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <div style="font-size: 32px; margin-bottom: 8px;">♚</div>
                                <strong>Tidak ada pengguna ditemukan</strong>
                                <p style="margin-top: 4px; font-size: 12px;">Coba sesuaikan kata kunci atau filter pencarian Anda.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($users->hasPages())
        <div class="pagination-wrapper">
            <span>Menampilkan {{ $users->firstItem() ?? 0 }} - {{ $users->lastItem() ?? 0 }} dari {{ $users->total() }} pengguna</span>
            {{ $users->links() }}
        </div>
    @endif
</div>
@endsection
