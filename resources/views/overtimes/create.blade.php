@extends('layouts.app')

@section('title', 'Tambah Pengajuan Lembur')

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
    .form-card { padding: 28px; max-width: 760px; margin: 0 auto; }
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

<div class="page-header" style="max-width: 760px; margin: 0 auto 24px;">
    <div class="page-title-box">
        <h2>Tambah Pengajuan Lembur</h2>
        <p>Formulir pencatatan permohonan kerja lembur resmi karyawan Perseverance Technology Indonesia</p>
    </div>
    <div>
        <a href="{{ route('overtimes.index') }}" class="btn btn-outline">
            <span>←</span> Batal
        </a>
    </div>
</div>

<div class="card form-card">
    <form action="{{ route('overtimes.store') }}" method="POST">
        @csrf

        <div class="form-grid">
            <div class="form-group full-width">
                <label class="form-label" for="employee_id">Karyawan <span class="required">*</span></label>
                <select id="employee_id" name="employee_id" class="form-control @error('employee_id') is-invalid @enderror" required>
                    <option value="">-- Pilih Karyawan --</option>
                    @foreach ($employees as $emp)
                        <option value="{{ $emp->id }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>
                            {{ $emp->name }} ({{ $emp->employee_code }}) — {{ $emp->department?->name ?? 'Tanpa Departemen' }}
                        </option>
                    @endforeach
                </select>
                @error('employee_id')
                    <div class="error-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group full-width">
                <label class="form-label" for="overtime_date">Tanggal Lembur <span class="required">*</span></label>
                <input type="date" id="overtime_date" name="overtime_date" value="{{ old('overtime_date', date('Y-m-d')) }}" class="form-control @error('overtime_date') is-invalid @enderror" required>
                @error('overtime_date')
                    <div class="error-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="start_time">Jam Mulai <span class="required">*</span></label>
                <input type="time" id="start_time" name="start_time" value="{{ old('start_time', '18:00') }}" class="form-control @error('start_time') is-invalid @enderror" required>
                @error('start_time')
                    <div class="error-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="end_time">Jam Selesai <span class="required">*</span></label>
                <input type="time" id="end_time" name="end_time" value="{{ old('end_time', '21:00') }}" class="form-control @error('end_time') is-invalid @enderror" required>
                @error('end_time')
                    <div class="error-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group full-width">
                <label class="form-label" for="reason">Uraian Tugas / Alasan Lembur <span class="required">*</span></label>
                <textarea id="reason" name="reason" rows="4" class="form-control @error('reason') is-invalid @enderror" placeholder="Jelaskan kebutuhan pekerjaan atau target yang harus diselesaikan selama jam lembur..." required>{{ old('reason') }}</textarea>
                @error('reason')
                    <div class="error-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="form-actions">
            <a href="{{ route('overtimes.index') }}" class="btn btn-outline">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan Pengajuan</button>
        </div>
    </form>
</div>
@endsection
