@extends('layouts.app')

@section('title', 'Detail Karyawan · ' . $employee->name)

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
    .profile-card { padding: 28px; margin-bottom: 24px; display: flex; gap: 24px; align-items: flex-start; }
    .profile-avatar { width: 72px; height: 72px; border-radius: 16px; background: var(--brand-soft); color: var(--brand-dark); font-size: 28px; font-weight: 800; display: grid; place-items: center; flex-shrink: 0; }
    .profile-info { flex: 1; }
    .profile-info h3 { margin: 0; font-size: 20px; font-weight: 700; color: var(--ink); }
    .profile-meta { display: flex; align-items: center; gap: 12px; margin-top: 6px; flex-wrap: wrap; font-size: 13px; color: var(--muted); }
    .badge-status { display: inline-flex; align-items: center; padding: 3px 8px; border-radius: 999px; font-size: 11.5px; font-weight: 600; }
    .badge-active { background: #e6f7f2; color: #0a7b6f; }
    .badge-inactive { background: #f1f3f5; color: #5f6c77; }
    .badge-resigned { background: #feebeb; color: #b92e2e; }
    .grid-details { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; }
    .detail-card { padding: 22px 24px; }
    .detail-card h4 { margin: 0 0 16px; font-size: 14px; font-weight: 700; color: var(--ink); border-bottom: 1px solid var(--line); padding-bottom: 10px; }
    .info-list { display: grid; grid-template-columns: 140px 1fr; row-gap: 12px; column-gap: 12px; font-size: 13px; }
    .info-label { color: var(--muted); font-weight: 500; }
    .info-value { color: var(--ink); font-weight: 600; }
    @media (max-width: 900px) { .grid-details { grid-template-columns: 1fr; } }
</style>

<div class="page-header">
    <div class="page-title-box">
        <h2>Detail Karyawan</h2>
        <p>Informasi profil dan administrasi kepegawaian Perseverance Technology Indonesia</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('employees.index') }}" class="btn btn-outline">
            <span>←</span> Kembali
        </a>
        @if (auth()->user()->role?->name !== 'taiwan_account' && auth()->user()->role?->permissions->contains('name', 'manage_employees'))
            <a href="{{ route('employees.edit', $employee) }}" class="btn btn-primary">
                <span>✎</span> Edit Karyawan
            </a>
        @endif
    </div>
</div>

<div class="card profile-card">
    @if ($employee->photo_url)
        <img src="{{ $employee->photo_url }}" alt="{{ $employee->name }}" class="profile-avatar" style="object-fit: cover; border: 2px solid var(--line);">
    @else
        <div class="profile-avatar">
            {{ str($employee->name)->substr(0, 1)->upper() }}
        </div>
    @endif
    <div class="profile-info">
        <h3>{{ $employee->name }}</h3>
        <div class="profile-meta">
            <span>ID: <code>{{ $employee->employee_code }}</code></span>
            <span>•</span>
            <span>{{ $employee->department?->name ?? 'Belum ada departemen' }}</span>
            <span>•</span>
            <span>{{ $employee->position?->name ?? 'Belum ada jabatan' }}</span>
            <span>•</span>
            @if ($employee->status === 'active')
                <span class="badge-status badge-active">Active</span>
            @elseif ($employee->status === 'inactive')
                <span class="badge-status badge-inactive">Inactive</span>
            @elseif ($employee->status === 'resigned')
                <span class="badge-status badge-resigned">Resigned</span>
            @endif
        </div>
    </div>
</div>

<div class="grid-details">
    <div class="card detail-card">
        <h4>Informasi Dasar & Kontak</h4>
        <div class="info-list">
            <span class="info-label">ID Karyawan</span>
            <span class="info-value"><code>{{ $employee->employee_code }}</code></span>

            <span class="info-label">Nama Lengkap</span>
            <span class="info-value">{{ $employee->name }}</span>

            <span class="info-label">Email Perusahaan</span>
            <span class="info-value">{{ $employee->email }}</span>

            <span class="info-label">Nomor Telepon</span>
            <span class="info-value">{{ $employee->phone ?? '-' }}</span>

            <span class="info-label">Status Akun</span>
            <span class="info-value">
                @if ($employee->user)
                    <span style="color: #0a7b6f; font-weight: 600;">Terdaftar</span> ({{ $employee->user->email }})
                @else
                    <span style="color: var(--muted); font-weight: normal;">Belum memiliki akun</span>
                @endif
            </span>
        </div>
    </div>

    <div class="card detail-card">
        <h4>Struktur & Status Kepegawaian</h4>
        <div class="info-list">
            <span class="info-label">Departemen</span>
            <span class="info-value">{{ $employee->department?->name ?? '-' }}</span>

            <span class="info-label">Jabatan (Posisi)</span>
            <span class="info-value">{{ $employee->position?->name ?? '-' }}</span>

            <span class="info-label">Tipe Kontrak</span>
            <span class="info-value">{{ ucfirst($employee->employment_type) }}</span>

            <span class="info-label">Status Kerja</span>
            <span class="info-value">
                @if ($employee->status === 'active')
                    <span class="badge-status badge-active">Active</span>
                @elseif ($employee->status === 'inactive')
                    <span class="badge-status badge-inactive">Inactive</span>
                @elseif ($employee->status === 'resigned')
                    <span class="badge-status badge-resigned">Resigned</span>
                @endif
            </span>

            <span class="info-label">Special Status</span>
            <span class="info-value">{{ $employee->special_status ?? '-' }}</span>

            <span class="info-label">Tanggal Bergabung</span>
            <span class="info-value">{{ $employee->join_date?->translatedFormat('d F Y') ?? '-' }}</span>

            @if ($employee->status === 'resigned')
                <span class="info-label" style="color: #b92e2e;">Tanggal Resign</span>
                <span class="info-value" style="color: #b92e2e;">
                    {{ $employee->resigned_at?->translatedFormat('d F Y') ?? '-' }}
                </span>
            @endif
        </div>
    </div>
</div>
@endsection
