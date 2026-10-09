@extends('layouts.app')

@section('title', 'Edit Karyawan · ' . $employee->name)

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
    .form-card { padding: 28px; max-width: 860px; margin: 0 auto; }
    .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group.full-width { grid-column: span 2; }
    .form-label { font-size: 13px; font-weight: 600; color: var(--ink); }
    .form-label .required { color: #d93838; }
    .form-control { width: 100%; padding: 10px 14px; border: 1px solid var(--line); border-radius: 8px; font-size: 13.5px; background: #fafbfc; color: var(--ink); outline: none; }
    .form-control:focus { border-color: var(--brand); background: #fff; box-shadow: 0 0 0 3px rgba(12, 143, 130, .1); }
    .form-control.is-invalid { border-color: #d93838; background: #fff8f8; }
    .error-feedback { color: #d93838; font-size: 12px; margin-top: 2px; }
    .form-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 28px; padding-top: 20px; border-top: 1px solid var(--line); }
    @media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } .form-group.full-width { grid-column: span 1; } }
</style>

<div class="page-header" style="max-width: 860px; margin: 0 auto 24px;">
    <div class="page-title-box">
        <h2>Edit Data Karyawan</h2>
        <p>Perbarui informasi administrasi, penempatan, atau status kepegawaian {{ $employee->name }}</p>
    </div>
    <div>
        <a href="{{ route('employees.show', $employee) }}" class="btn btn-outline">
            <span>←</span> Batal
        </a>
    </div>
</div>

<div class="card form-card">
    <form action="{{ route('employees.update', $employee) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="form-grid">
            <div class="form-group">
                <label class="form-label" for="employee_code">ID / Kode Karyawan</label>
                <input type="text" id="employee_code" name="employee_code" value="{{ $employee->employee_code }}" class="form-control" readonly style="background: #eef2f5; cursor: not-allowed; color: var(--ink); font-weight: 600;" title="ID Karyawan bersifat permanen dan tidak dapat diubah">
                <span style="font-size: 11.5px; color: var(--muted); margin-top: 4px; display: block;">ID Karyawan bersifat permanen dan tidak dapat diubah.</span>
            </div>

            <div class="form-group">
                <label class="form-label" for="name">Nama Lengkap <span class="required">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $employee->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                @error('name')
                    <div class="error-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Alamat Email <span class="required">*</span></label>
                <input type="email" id="email" name="email" value="{{ old('email', $employee->email) }}" class="form-control @error('email') is-invalid @enderror" required>
                @error('email')
                    <div class="error-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="phone">Nomor Telepon</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone', $employee->phone) }}" class="form-control @error('phone') is-invalid @enderror" placeholder="08xxxxxxxxxx">
                @error('phone')
                    <div class="error-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="department_id">Departemen <span class="required">*</span></label>
                <select id="department_id" name="department_id" class="form-control @error('department_id') is-invalid @enderror" required onchange="filterPositions()">
                    <option value="">-- Pilih Departemen --</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" {{ old('department_id', $employee->department_id) == $department->id ? 'selected' : '' }}>
                            {{ $department->name }}
                        </option>
                    @endforeach
                </select>
                @error('department_id')
                    <div class="error-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="position_id">Jabatan / Posisi <span class="required">*</span></label>
                <select id="position_id" name="position_id" class="form-control @error('position_id') is-invalid @enderror" required>
                    <option value="">-- Pilih Jabatan --</option>
                    @foreach ($positions as $position)
                        <option value="{{ $position->id }}" data-department="{{ $position->department_id }}" {{ old('position_id', $employee->position_id) == $position->id ? 'selected' : '' }}>
                            {{ $position->name }}
                        </option>
                    @endforeach
                </select>
                @error('position_id')
                    <div class="error-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="join_date">Tanggal Bergabung <span class="required">*</span></label>
                <input type="date" id="join_date" name="join_date" value="{{ old('join_date', $employee->join_date?->format('Y-m-d')) }}" class="form-control @error('join_date') is-invalid @enderror" required>
                @error('join_date')
                    <div class="error-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="employment_type">Tipe Kontrak <span class="required">*</span></label>
                <select id="employment_type" name="employment_type" class="form-control @error('employment_type') is-invalid @enderror" required>
                    <option value="permanent" {{ old('employment_type', $employee->employment_type) == 'permanent' ? 'selected' : '' }}>Permanent (Tetap)</option>
                    <option value="contract" {{ old('employment_type', $employee->employment_type) == 'contract' ? 'selected' : '' }}>Contract (Kontrak)</option>
                    <option value="internship" {{ old('employment_type', $employee->employment_type) == 'internship' ? 'selected' : '' }}>Internship (Magang)</option>
                </select>
                @error('employment_type')
                    <div class="error-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="status">Status Karyawan <span class="required">*</span></label>
                <select id="status" name="status" class="form-control @error('status') is-invalid @enderror" required onchange="toggleResignedAt()">
                    <option value="active" {{ old('status', $employee->status) == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $employee->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="resigned" {{ old('status', $employee->status) == 'resigned' ? 'selected' : '' }}>Resigned</option>
                </select>
                @error('status')
                    <div class="error-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group" id="resigned_at_container" style="{{ old('status', $employee->status) === 'resigned' ? '' : 'display: none;' }}">
                <label class="form-label" for="resigned_at">Tanggal Resign <span class="required">*</span></label>
                <input type="date" id="resigned_at" name="resigned_at" value="{{ old('resigned_at', $employee->resigned_at?->format('Y-m-d')) }}" class="form-control @error('resigned_at') is-invalid @enderror">
                @error('resigned_at')
                    <div class="error-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group full-width">
                <label class="form-label" for="special_status">Catatan / Special Status</label>
                <input type="text" id="special_status" name="special_status" value="{{ old('special_status', $employee->special_status) }}" class="form-control @error('special_status') is-invalid @enderror" placeholder="Opsional (misal: Promosi, Mutasi, dll.)">
                @error('special_status')
                    <div class="error-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Bagian: Profile Photo -->
            <div class="form-group full-width" style="margin-top: 14px; padding-top: 18px; border-top: 1px dashed var(--line);">
                <label class="form-label" for="photo">Foto Profil Karyawan</label>
                @if ($employee->photo_url)
                    <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 12px; background: #f8fafb; padding: 12px; border-radius: 8px; border: 1px solid var(--line);">
                        <img src="{{ $employee->photo_url }}" alt="{{ $employee->name }}" style="width: 54px; height: 54px; border-radius: 12px; object-fit: cover; border: 1px solid var(--line);">
                        <div>
                            <div style="font-size: 13px; font-weight: 600; color: var(--ink);">Foto Profil Saat Ini (Versi {{ $employee->photo_version }})</div>
                            <label style="display: flex; align-items: center; gap: 6px; margin-top: 4px; font-size: 12px; color: #d93838; cursor: pointer;">
                                <input type="checkbox" name="delete_photo" value="1" {{ old('delete_photo') ? 'checked' : '' }} style="accent-color: #d93838;">
                                Hapus foto profil ini
                            </label>
                        </div>
                    </div>
                @endif
                <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp,image/jpg" class="form-control @error('photo') is-invalid @enderror">
                <span style="font-size: 11.5px; color: var(--muted); margin-top: 4px; display: block;">Format: JPG, PNG, WEBP. Maksimal ukuran: 2 MB. Unggah file baru untuk mengganti foto saat ini.</span>
                @error('photo')
                    <div class="error-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Bagian: User Account -->
            <div class="form-group full-width" style="margin-top: 14px; padding-top: 18px; border-top: 1px dashed var(--line);">
                @if ($employee->user)
                    <div style="background: #f8fafb; border: 1px solid var(--line); border-radius: 8px; padding: 18px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                            <span style="font-size: 14px; font-weight: 700; color: var(--ink);">Akun Pengguna Terhubung</span>
                            <span style="font-size: 11.5px; font-weight: 600; background: #e6f7f2; color: #0a7b6f; padding: 3px 9px; border-radius: 999px;">
                                Role: {{ $employee->user->role?->name ?? 'employee' }}
                            </span>
                        </div>

                        <div style="margin-bottom: 16px; padding: 10px 14px; background: #e6f7f2; border: 1px solid #b2e6db; border-radius: 6px; font-size: 12.5px; color: #0a7b6f;">
                            <strong>Email Akun Pengguna:</strong> <span id="synced_user_email">{{ $employee->user->email }}</span>
                            <span style="display: block; font-size: 11.5px; color: var(--muted); margin-top: 3px;">
                                Email akun login otomatis tersinkronisasi 1:1 mengikuti email profil karyawan di atas.
                            </span>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label" for="account_password">Password Baru <span style="font-weight: normal; color: var(--muted);">(Opsional)</span></label>
                                <input type="password" id="account_password" name="account_password" class="form-control @error('account_password') is-invalid @enderror" placeholder="Kosongkan jika tidak diubah">
                                <span style="font-size: 11.5px; color: var(--muted); margin-top: 4px; display: block;">Biarkan kosong untuk mempertahankan password lama.</span>
                                @error('account_password')
                                    <div class="error-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="account_password_confirmation">Konfirmasi Password Baru</label>
                                <input type="password" id="account_password_confirmation" name="account_password_confirmation" class="form-control" placeholder="Ulangi jika mengubah password">
                            </div>
                        </div>
                    </div>
                @else
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                        <input type="checkbox" id="create_account" name="create_account" value="1" {{ old('create_account') ? 'checked' : '' }} onchange="toggleAccountFields()" style="width: 17px; height: 17px; accent-color: var(--brand); cursor: pointer;">
                        <label for="create_account" style="font-size: 14px; font-weight: 700; color: var(--ink); cursor: pointer; margin: 0;">
                            Buat Akun Pengguna untuk Karyawan Ini (User Portal / Mobile)
                        </label>
                    </div>
                    <p style="margin: 0 0 14px; font-size: 12px; color: var(--muted);">
                        Karyawan ini belum memiliki akun login. Centang untuk membuatkan akun dengan <strong>Role: Employee</strong>.
                    </p>

                    <div id="account_fields_container" style="{{ old('create_account') ? '' : 'display: none;' }} background: #f8fafb; border: 1px solid var(--line); border-radius: 8px; padding: 18px; margin-top: 8px;">
                        <div style="margin-bottom: 16px; padding: 10px 14px; background: #e6f7f2; border: 1px solid #b2e6db; border-radius: 6px; font-size: 12.5px; color: #0a7b6f;">
                            <strong>Email Akun Pengguna:</strong> Mengikuti email karyawan di atas (<span id="preview_account_email">{{ old('email', $employee->email) }}</span>). Role akun: <strong>Employee</strong>.
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label" for="account_password">Password Akun <span class="required">*</span></label>
                                <input type="password" id="account_password" name="account_password" class="form-control @error('account_password') is-invalid @enderror" placeholder="Minimal 8 karakter">
                                @error('account_password')
                                    <div class="error-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="account_password_confirmation">Konfirmasi Password <span class="required">*</span></label>
                                <input type="password" id="account_password_confirmation" name="account_password_confirmation" class="form-control" placeholder="Ketik ulang password">
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="form-actions">
            <a href="{{ route('employees.show', $employee) }}" class="btn btn-outline">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </div>
    </form>
</div>

<script>
    function filterPositions() {
        const deptId = document.getElementById('department_id').value;
        const positionSelect = document.getElementById('position_id');
        const options = positionSelect.querySelectorAll('option');

        options.forEach(opt => {
            if (!opt.value) return;
            const optDept = opt.getAttribute('data-department');
            if (!deptId || optDept === deptId) {
                opt.style.display = '';
            } else {
                opt.style.display = 'none';
                if (opt.selected) {
                    positionSelect.value = '';
                }
            }
        });
    }

    function toggleResignedAt() {
        const status = document.getElementById('status').value;
        const container = document.getElementById('resigned_at_container');
        if (status === 'resigned') {
            container.style.display = '';
            const input = document.getElementById('resigned_at');
            if (!input.value) {
                input.value = new Date().toISOString().split('T')[0];
            }
        } else {
            container.style.display = 'none';
        }
    }

    function toggleAccountFields() {
        const checkbox = document.getElementById('create_account');
        if (!checkbox) return;
        const container = document.getElementById('account_fields_container');
        if (container) {
            container.style.display = checkbox.checked ? '' : 'none';
        }
    }

    function syncEmailDisplay() {
        const empEmail = document.getElementById('email').value;
        const synced = document.getElementById('synced_user_email');
        if (synced) {
            synced.textContent = empEmail || '—';
        }
        const preview = document.getElementById('preview_account_email');
        if (preview) {
            preview.textContent = empEmail || 'sesuai email karyawan';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        filterPositions();
        toggleAccountFields();

        const emailInput = document.getElementById('email');
        if (emailInput) {
            emailInput.addEventListener('input', syncEmailDisplay);
        }
    });
</script>
@endsection
