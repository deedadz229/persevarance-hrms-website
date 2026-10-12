@extends('layouts.app')

@section('title', 'Pengaturan Sistem & Akun')

@section('content')
<style>
    .page-header { margin-bottom: 24px; }
    .page-title-box h2 { margin: 0; font-size: 22px; font-weight: 700; color: var(--ink); letter-spacing: -.02em; }
    .page-title-box p { margin: 4px 0 0; color: var(--muted); font-size: 13px; }
    .settings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
    @media (max-width: 960px) { .settings-grid { grid-template-columns: 1fr; } }
    .settings-card { padding: 26px; }
    .settings-card h3 { margin: 0 0 8px; font-size: 16px; font-weight: 700; color: var(--ink); }
    .settings-card p.subtitle { margin: 0 0 20px; font-size: 12.5px; color: var(--muted); border-bottom: 1px solid var(--line); padding-bottom: 12px; }
    .form-group { margin-bottom: 16px; }
    .form-label { display: block; font-size: 13px; font-weight: 600; color: var(--ink); margin-bottom: 6px; }
    .form-label .required { color: #b92e2e; }
    .form-control { width: 100%; padding: 10px 14px; border: 1px solid var(--line); border-radius: 8px; font-size: 13px; background: #fafbfc; color: var(--ink); outline: none; }
    .form-control:focus { border-color: var(--brand); background: #fff; box-shadow: 0 0 0 3px rgba(12, 143, 130, .1); }
    .form-control.is-invalid { border-color: #f3caca; background: #fffdfd; }
    .form-control:disabled, .form-control[readonly] { background: #f1f3f5; color: var(--muted); cursor: not-allowed; }
    .form-error { font-size: 12px; color: #b92e2e; margin-top: 5px; font-weight: 500; }
    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; cursor: pointer; border: 1px solid transparent; transition: all .15s ease; }
    .btn-primary { background: var(--brand); color: #fff; }
    .btn-primary:hover { background: var(--brand-dark); }
    .app-info-list { display: grid; grid-template-columns: 140px 1fr; row-gap: 12px; column-gap: 16px; font-size: 13px; }
    .info-label { color: var(--muted); font-weight: 600; }
    .info-value { color: var(--ink); font-weight: 500; }
</style>

<div class="page-header">
    <div class="page-title-box">
        <h2>Pengaturan Akun & Informasi Sistem</h2>
        <p>Kelola preferensi akun pribadi, kata sandi, dan tinjau spesifikasi sistem HRMS</p>
    </div>
</div>

<div class="settings-grid">
    <!-- Profil Pengguna -->
    <div class="card settings-card">
        <h3>Profil Pengguna</h3>
        <p class="subtitle">Perbarui data identitas diri dan alamat email akun Anda</p>

        <form action="{{ route('settings.profile') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="name" class="form-label">Nama Lengkap <span class="required">*</span></label>
                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                @error('name')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Alamat Email <span class="required">*</span></label>
                <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                @error('email')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Peran Pengguna (Role)</label>
                <input type="text" class="form-control" value="{{ $user->role?->label ?? 'Tanpa Role' }}" readonly>
                <div style="font-size: 11.5px; color: var(--muted); margin-top: 4px;">Peran akun ditetapkan secara terpusat oleh Super Admin.</div>
            </div>

            @if ($user->employee)
                <div class="form-group">
                    <label class="form-label">Profil Karyawan Terhubung</label>
                    <input type="text" class="form-control" value="{{ $user->employee->employee_code }} - {{ $user->employee->name }}" readonly>
                </div>
            @endif

            <div style="margin-top: 20px;">
                <button type="submit" class="btn btn-primary">Simpan Profil</button>
            </div>
        </form>
    </div>

    <!-- Ubah Kata Sandi -->
    <div class="card settings-card">
        <h3>Ubah Kata Sandi</h3>
        <p class="subtitle">Tingkatkan keamanan akun dengan memperbarui kata sandi secara berkala</p>

        <form action="{{ route('settings.password') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="current_password" class="form-label">Kata Sandi Saat Ini <span class="required">*</span></label>
                <input type="password" name="current_password" id="current_password" class="form-control @error('current_password') is-invalid @enderror" placeholder="Masukkan kata sandi lama Anda" required>
                @error('current_password')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Kata Sandi Baru <span class="required">*</span></label>
                <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" placeholder="Minimal 8 karakter" required>
                @error('password')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation" class="form-label">Konfirmasi Kata Sandi Baru <span class="required">*</span></label>
                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="Ulangi kata sandi baru" required>
            </div>

            <div style="margin-top: 20px;">
                <button type="submit" class="btn btn-primary">Perbarui Kata Sandi</button>
            </div>
        </form>
    </div>

    <!-- Preferensi Bahasa -->
    <div class="card settings-card" style="grid-column: 1 / -1;">
        <h3>{{ __('settings.language_preference') }}</h3>
        <p class="subtitle">{{ __('settings.language_preference_subtitle') }}</p>

        <div style="display: flex; gap: 14px; flex-wrap: wrap;">
            <a href="{{ route('locale.switch', 'id') }}" 
               class="btn {{ app()->getLocale() === 'id' ? 'btn-primary' : 'btn-outline' }}" 
               style="padding: 10px 18px; border: 1px solid {{ app()->getLocale() === 'id' ? 'transparent' : 'var(--line)' }};">
                <span style="font-size: 16px;">🇮🇩</span>
                <strong>Bahasa Indonesia</strong>
                @if (app()->getLocale() === 'id')
                    <span style="font-size: 11px; background: rgba(255,255,255,0.25); padding: 1px 6px; border-radius: 4px; margin-left: 6px;">Aktif</span>
                @endif
            </a>
            <a href="{{ route('locale.switch', 'en') }}" 
               class="btn {{ app()->getLocale() === 'en' ? 'btn-primary' : 'btn-outline' }}" 
               style="padding: 10px 18px; border: 1px solid {{ app()->getLocale() === 'en' ? 'transparent' : 'var(--line)' }};">
                <span style="font-size: 16px;">🇬🇧</span>
                <strong>English (UK / US)</strong>
                @if (app()->getLocale() === 'en')
                    <span style="font-size: 11px; background: rgba(255,255,255,0.25); padding: 1px 6px; border-radius: 4px; margin-left: 6px;">Active</span>
                @endif
            </a>
        </div>
    </div>

    <!-- Informasi Aplikasi -->
    <div class="card settings-card" style="grid-column: 1 / -1;">
        <h3>{{ __('settings.app_info_title') }}</h3>
        <p class="subtitle">{{ __('settings.app_info_subtitle') }}</p>

        <div class="app-info-list">
            <span class="info-label">{{ __('settings.app_name') }}</span>
            <span class="info-value"><strong>{{ $appInfo['name'] }}</strong></span>

            <span class="info-label">{{ __('settings.company') }}</span>
            <span class="info-value">{{ $appInfo['company'] }}</span>

            <span class="info-label">{{ __('settings.system_version') }}</span>
            <span class="info-value"><span class="status-badge" style="font-size: 11px;">v{{ $appInfo['version'] }}</span></span>

            <span class="info-label">{{ __('settings.framework') }}</span>
            <span class="info-value">{{ $appInfo['framework'] }}</span>

            <span class="info-label">{{ __('settings.php_version') }}</span>
            <span class="info-value"><code>PHP {{ $appInfo['php_version'] }}</code></span>

            <span class="info-label">{{ __('settings.timezone') }}</span>
            <span class="info-value">{{ $appInfo['timezone'] }}</span>

            <span class="info-label">{{ __('settings.environment') }}</span>
            <span class="info-value" style="text-transform: capitalize;">{{ $appInfo['environment'] }}</span>
        </div>
    </div>
</div>
@endsection
