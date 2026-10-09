@extends('layouts.app')

@section('title', 'Tambah Department')

@section('content')
<style>
    .page-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
    .page-title-box h2 { margin: 0; font-size: 22px; font-weight: 700; color: var(--ink); letter-spacing: -.02em; }
    .page-title-box p { margin: 4px 0 0; color: var(--muted); font-size: 13px; }
    .form-card { max-width: 680px; padding: 28px 32px; }
    .form-group { margin-bottom: 20px; }
    .form-label { display: block; margin-bottom: 7px; font-size: 13px; font-weight: 600; color: var(--ink); }
    .required { color: #dc2626; margin-left: 2px; }
    .form-control { width: 100%; padding: 10px 14px; border: 1px solid var(--line); border-radius: 8px; font-size: 13px; background: #fafbfc; color: var(--ink); outline: none; transition: all .15s ease; }
    .form-control:focus { border-color: var(--brand); background: #fff; box-shadow: 0 0 0 3px rgba(12, 143, 130, .1); }
    .form-control.is-invalid { border-color: #dc2626; background: #fff8f8; }
    .error-feedback { color: #dc2626; font-size: 12px; margin-top: 5px; }
    .form-actions { display: flex; align-items: center; justify-content: flex-end; gap: 12px; margin-top: 28px; padding-top: 20px; border-top: 1px solid var(--line); }
    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; cursor: pointer; border: 1px solid transparent; transition: all .15s ease; }
    .btn-primary { background: var(--brand); color: #fff; }
    .btn-primary:hover { background: var(--brand-dark); }
    .btn-outline { border-color: var(--line); background: var(--surface); color: var(--ink); }
    .btn-outline:hover { background: #f0f4f5; }
</style>

<div class="page-header">
    <div class="page-title-box">
        <h2>Tambah Department Baru</h2>
        <p>Lengkapi formulir berikut untuk mendaftarkan departemen baru ke dalam sistem</p>
    </div>
    <div>
        <a href="{{ route('departments.index') }}" class="btn btn-outline">
            <span>←</span> Batal
        </a>
    </div>
</div>

<div class="card form-card">
    <form action="{{ route('departments.store') }}" method="POST">
        @csrf

        <div class="form-group">
            <label class="form-label" for="name">Nama Department <span class="required">*</span></label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" placeholder="Contoh: Human Resources, Information Technology" required autofocus>
            @error('name')
                <div class="error-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="description">Deskripsi Department</label>
            <textarea id="description" name="description" rows="3" class="form-control @error('description') is-invalid @enderror" placeholder="Penjelasan singkat peran dan tanggung jawab departemen (opsional)">{{ old('description') }}</textarea>
            @error('description')
                <div class="error-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="status">Status Operasional <span class="required">*</span></label>
            <select id="status" name="status" class="form-control @error('status') is-invalid @enderror" required>
                <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active (Aktif)</option>
                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive (Tidak Aktif)</option>
            </select>
            @error('status')
                <div class="error-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-actions">
            <a href="{{ route('departments.index') }}" class="btn btn-outline">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan Department</button>
        </div>
    </form>
</div>
@endsection
