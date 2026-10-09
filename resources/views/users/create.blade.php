@extends('layouts.app')

@section('title', 'Tambah Pengguna Baru')

@section('content')
<style>
    .page-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
    .page-title-box h2 { margin: 0; font-size: 22px; font-weight: 700; color: var(--ink); letter-spacing: -.02em; }
    .page-title-box p { margin: 4px 0 0; color: var(--muted); font-size: 13px; }
    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; cursor: pointer; border: 1px solid transparent; transition: all .15s ease; }
    .btn-primary { background: var(--brand); color: #fff; }
    .btn-primary:hover { background: var(--brand-dark); }
    .btn-outline { border-color: var(--line); background: var(--surface); color: var(--ink); }
    .btn-outline:hover { background: #f0f4f5; }
    .form-card { padding: 28px; max-width: 820px; }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    @media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } }
    .form-group { margin-bottom: 18px; }
    .form-group.full-width { grid-column: 1 / -1; }
    .form-label { display: block; font-size: 13px; font-weight: 600; color: var(--ink); margin-bottom: 7px; }
    .form-label .required { color: #b92e2e; }
    .form-control { width: 100%; padding: 10px 14px; border: 1px solid var(--line); border-radius: 8px; font-size: 13.5px; background: #fafbfc; color: var(--ink); outline: none; }
    .form-control:focus { border-color: var(--brand); background: #fff; box-shadow: 0 0 0 3px rgba(12, 143, 130, .1); }
    .form-control.is-invalid { border-color: #f3caca; background: #fffdfd; }
    .form-hint { font-size: 11.5px; color: var(--muted); margin-top: 5px; }
    .form-error { font-size: 12px; color: #b92e2e; margin-top: 5px; font-weight: 500; }
    .form-actions { display: flex; align-items: center; gap: 12px; margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--line); }
</style>

<div class="page-header">
    <div class="page-title-box">
        <h2>Tambah Pengguna Baru</h2>
        <p>Daftarkan akun baru untuk staf manajemen, Super Admin, atau peninjau</p>
    </div>
    <div>
        <a href="{{ route('users.index') }}" class="btn btn-outline">&larr; Kembali ke Daftar Pengguna</a>
    </div>
</div>

<div class="card form-card">
    <form action="{{ route('users.store') }}" method="POST">
        @csrf

        <div class="form-grid">
            <div class="form-group full-width">
                <label for="name" class="form-label">Nama Lengkap <span class="required">*</span></label>
                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" placeholder="Contoh: Budi Santoso" value="{{ old('name') }}" required>
                @error('name')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group full-width">
                <label for="email" class="form-label">Alamat Email <span class="required">*</span></label>
                <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" placeholder="Contoh: budi@pti.local" value="{{ old('email') }}" required>
                <div class="form-hint" id="email_hint">Digunakan sebagai username saat login ke sistem</div>
                @error('email')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="role_id" class="form-label">Peran Pengguna (Role) <span class="required">*</span></label>
                <select name="role_id" id="role_id" class="form-control @error('role_id') is-invalid @enderror" required>
                    <option value="">-- Pilih Role --</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                            {{ $role->label }}
                        </option>
                    @endforeach
                </select>
                @error('role_id')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="status" class="form-label">Status Akun <span class="required">*</span></label>
                <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                    <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Aktif (Dapat Login)</option>
                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Non-Aktif (Blokir Akses)</option>
                </select>
                @error('status')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group full-width">
                <label for="employee_id" class="form-label">Karyawan Terkait (Opsional)</label>
                <select name="employee_id" id="employee_id" class="form-control @error('employee_id') is-invalid @enderror">
                    <option value="">-- Tidak Terhubung dengan Profil Karyawan --</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" data-email="{{ $employee->email }}" data-name="{{ $employee->name }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                            {{ $employee->employee_code }} - {{ $employee->name }} ({{ $employee->department?->name ?? 'Tanpa Departemen' }})
                        </option>
                    @endforeach
                </select>
                <div class="form-hint">Tautkan akun ini dengan rekaman data master karyawan yang bersangkutan</div>
                @error('employee_id')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password <span class="required">*</span></label>
                <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" placeholder="Minimal 8 karakter" required>
                @error('password')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation" class="form-label">Konfirmasi Password <span class="required">*</span></label>
                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="Ulangi password di atas" required>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan Pengguna</button>
            <a href="{{ route('users.index') }}" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const empSelect = document.getElementById('employee_id');
        const emailInput = document.getElementById('email');
        const nameInput = document.getElementById('name');
        const emailHint = document.getElementById('email_hint');

        function handleEmployeeChange() {
            const selectedOpt = empSelect.options[empSelect.selectedIndex];
            const empEmail = selectedOpt ? selectedOpt.getAttribute('data-email') : null;
            const empName = selectedOpt ? selectedOpt.getAttribute('data-name') : null;

            if (empEmail) {
                emailInput.value = empEmail;
                emailInput.readOnly = true;
                if (!nameInput.value && empName) {
                    nameInput.value = empName;
                }
                if (emailHint) {
                    emailHint.textContent = 'Email dikunci otomatis sesuai email profil karyawan (' + empEmail + ').';
                    emailHint.style.color = '#0a7b6f';
                }
            } else {
                emailInput.readOnly = false;
                if (emailHint) {
                    emailHint.textContent = 'Digunakan sebagai username saat login ke sistem';
                    emailHint.style.color = 'var(--muted)';
                }
            }
        }

        empSelect.addEventListener('change', handleEmployeeChange);
        if (empSelect.value) {
            handleEmployeeChange();
        }
    });
</script>
@endsection
