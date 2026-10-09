@extends('layouts.app')

@section('title', 'Proses / Buat Payroll Baru')

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
    .btn-sm { padding: 5px 10px; font-size: 12px; border-radius: 6px; }

    .form-container { max-width: 900px; margin: 0 auto; }
    .form-card { padding: 28px; margin-bottom: 24px; }
    .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group.full-width { grid-column: span 2; }
    .form-label { font-size: 13px; font-weight: 600; color: var(--ink); }
    .form-label .required { color: #d93838; }
    .form-control { width: 100%; padding: 10px 14px; border: 1px solid var(--line); border-radius: 8px; font-size: 13.5px; background: #fafbfc; color: var(--ink); outline: none; }
    .form-control:focus { border-color: var(--brand); background: #fff; box-shadow: 0 0 0 3px rgba(12, 143, 130, .1); }
    .form-control.is-invalid { border-color: #d93838; background: #fff8f8; }
    .error-feedback { color: #d93838; font-size: 12px; margin-top: 2px; }

    .section-title { margin: 20px 0 12px; font-size: 15px; font-weight: 700; color: var(--ink); border-bottom: 1px solid var(--line); padding-bottom: 8px; }

    .component-row { display: grid; grid-template-columns: minmax(180px, 1.2fr) minmax(140px, 1fr) minmax(160px, 1.2fr); gap: 12px; margin-bottom: 10px; align-items: center; }

    .calc-box { margin-top: 24px; padding: 20px; border-radius: 10px; background: #f4fbf9; border: 1px solid #bce6d9; }
    .calc-list { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; font-size: 13px; }
    .calc-item { text-align: center; }
    .calc-item span { display: block; color: var(--muted); font-size: 11px; text-transform: uppercase; font-weight: 600; margin-bottom: 4px; }
    .calc-item strong { font-size: 18px; color: var(--ink); }

    .form-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 28px; padding-top: 20px; border-top: 1px solid var(--line); }

    @media (max-width: 768px) {
        .form-grid { grid-template-columns: 1fr; }
        .form-group.full-width { grid-column: span 1; }
        .component-row { grid-template-columns: 1fr; gap: 8px; margin-bottom: 16px; border-bottom: 1px dashed var(--line); padding-bottom: 12px; }
    }
</style>

<div class="form-container">
    <div class="page-header">
        <div class="page-title-box">
            <h2>Proses / Buat Payroll Baru</h2>
            <p>Pencatatan data kompensasi, komponen tunjangan, dan potongan karyawan</p>
        </div>
        <div>
            <a href="{{ route('payrolls.index') }}" class="btn btn-outline">
                <span>←</span> Batal
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="flash flash-error" style="margin-bottom: 20px;">
            <strong>Perhatian:</strong>
            <ul style="margin: 6px 0 0 16px; padding: 0;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card form-card">
        <form action="{{ route('payrolls.store') }}" method="POST" id="payrollForm">
            @csrf

            <h3 class="section-title" style="margin-top: 0;">Informasi Karyawan & Periode</h3>
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
                        <span class="error-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="period_month">Bulan Periode <span class="required">*</span></label>
                    <select id="period_month" name="period_month" class="form-control" required>
                        @foreach ($months as $num => $name)
                            <option value="{{ $num }}" {{ old('period_month', $currentMonth) == $num ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="period_year">Tahun Periode <span class="required">*</span></label>
                    <select id="period_year" name="period_year" class="form-control" required>
                        @for ($y = (int) date('Y') + 1; $y >= 2024; $y--)
                            <option value="{{ $y }}" {{ old('period_year', $currentYear) == $y ? 'selected' : '' }}>
                                {{ $y }}
                            </option>
                        @endfor
                    </select>
                </div>

                <div class="form-group full-width">
                    <label class="form-label" for="basic_salary">Gaji Pokok (Basic Salary) <span class="required">*</span></label>
                    <input type="number" step="0.01" min="0" id="basic_salary" name="basic_salary" value="{{ old('basic_salary', 0) }}" class="form-control @error('basic_salary') is-invalid @enderror" placeholder="Contoh: 7500000" required oninput="recalculateTotals()">
                    @error('basic_salary')
                        <span class="error-feedback">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <h3 class="section-title">Komponen Tunjangan (Income / Allowance)</h3>
            <p style="font-size: 12.5px; color: var(--muted); margin: 0 0 14px;">Masukkan rincian tunjangan resmi (misal: transport, makan, jabatan, bonus kinerja):</p>

            <div id="allowancesContainer">
                <div class="component-row">
                    <input type="text" name="allowances[0][name]" value="{{ old('allowances.0.name', 'Tunjangan Operasional') }}" class="form-control" placeholder="Nama Tunjangan">
                    <input type="number" step="0.01" min="0" name="allowances[0][amount]" value="{{ old('allowances.0.amount', 0) }}" class="form-control allowance-input" placeholder="Jumlah (Rp)" oninput="recalculateTotals()">
                    <input type="text" name="allowances[0][description]" value="{{ old('allowances.0.description', 'Tunjangan bulanan') }}" class="form-control" placeholder="Keterangan (opsional)">
                </div>
                <div class="component-row">
                    <input type="text" name="allowances[1][name]" value="{{ old('allowances.1.name', 'Tunjangan Kehadiran / Makan') }}" class="form-control" placeholder="Nama Tunjangan">
                    <input type="number" step="0.01" min="0" name="allowances[1][amount]" value="{{ old('allowances.1.amount', 0) }}" class="form-control allowance-input" placeholder="Jumlah (Rp)" oninput="recalculateTotals()">
                    <input type="text" name="allowances[1][description]" value="{{ old('allowances.1.description') }}" class="form-control" placeholder="Keterangan (opsional)">
                </div>
            </div>

            <h3 class="section-title">Komponen Potongan (Deduction: BPJS, PPh21, dll)</h3>
            <p style="font-size: 12.5px; color: var(--muted); margin: 0 0 14px;">Masukkan potongan resmi karyawan secara transparan (tanpa membuat formula asumsi):</p>

            <div id="deductionsContainer">
                <div class="component-row">
                    <input type="text" name="deductions[0][name]" value="{{ old('deductions.0.name', 'BPJS Kesehatan & Ketenagakerjaan') }}" class="form-control" placeholder="Nama Potongan">
                    <input type="number" step="0.01" min="0" name="deductions[0][amount]" value="{{ old('deductions.0.amount', 0) }}" class="form-control deduction-input" placeholder="Jumlah (Rp)" oninput="recalculateTotals()">
                    <input type="text" name="deductions[0][description]" value="{{ old('deductions.0.description', 'Iuran BPJS Karyawan') }}" class="form-control" placeholder="Keterangan (opsional)">
                </div>
                <div class="component-row">
                    <input type="text" name="deductions[1][name]" value="{{ old('deductions.1.name', 'PPh21') }}" class="form-control" placeholder="Nama Potongan">
                    <input type="number" step="0.01" min="0" name="deductions[1][amount]" value="{{ old('deductions.1.amount', 0) }}" class="form-control deduction-input" placeholder="Jumlah (Rp)" oninput="recalculateTotals()">
                    <input type="text" name="deductions[1][description]" value="{{ old('deductions.1.description', 'Pajak Penghasilan Pasal 21') }}" class="form-control" placeholder="Keterangan (opsional)">
                </div>
                <div class="component-row">
                    <input type="text" name="deductions[2][name]" value="{{ old('deductions.2.name', 'Potongan Lain-lain') }}" class="form-control" placeholder="Nama Potongan">
                    <input type="number" step="0.01" min="0" name="deductions[2][amount]" value="{{ old('deductions.2.amount', 0) }}" class="form-control deduction-input" placeholder="Jumlah (Rp)" oninput="recalculateTotals()">
                    <input type="text" name="deductions[2][description]" value="{{ old('deductions.2.description') }}" class="form-control" placeholder="Keterangan (opsional)">
                </div>
            </div>

            <div class="calc-box">
                <div class="calc-list">
                    <div class="calc-item">
                        <span>Gaji Pokok</span>
                        <strong id="displayBasic">Rp 0</strong>
                    </div>
                    <div class="calc-item" style="color: #0c8f82;">
                        <span>+ Total Tunjangan</span>
                        <strong id="displayAllowance" style="color: #0c8f82;">Rp 0</strong>
                    </div>
                    <div class="calc-item" style="color: #b92e2e;">
                        <span>- Total Potongan</span>
                        <strong id="displayDeduction" style="color: #b92e2e;">Rp 0</strong>
                    </div>
                    <div class="calc-item" style="border-left: 2px solid #bce6d9; padding-left: 16px;">
                        <span>= Estimasi Gaji Bersih (Net)</span>
                        <strong id="displayNet" style="color: var(--brand-dark); font-size: 22px;">Rp 0</strong>
                    </div>
                </div>
            </div>

            <div class="form-grid" style="margin-top: 20px;">
                <div class="form-group full-width">
                    <label class="form-label" for="status">Status Awal</label>
                    <select id="status" name="status" class="form-control">
                        <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>Draft (Dapat dievaluasi / diedit sebelum rilis)</option>
                        <option value="generated" {{ old('status') === 'generated' ? 'selected' : '' }}>Dihasilkan / Generated (Siap dicetak slip gaji)</option>
                    </select>
                </div>
            </div>

            <div class="form-actions">
                <a href="{{ route('payrolls.index') }}" class="btn btn-outline">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan & Proses Payroll</button>
            </div>
        </form>
    </div>
</div>

<script>
    function formatRupiah(amount) {
        return 'Rp ' + Number(amount).toLocaleString('id-ID');
    }

    function recalculateTotals() {
        const basic = parseFloat(document.getElementById('basic_salary').value) || 0;

        let totalAllowance = 0;
        document.querySelectorAll('.allowance-input').forEach(function(el) {
            totalAllowance += parseFloat(el.value) || 0;
        });

        let totalDeduction = 0;
        document.querySelectorAll('.deduction-input').forEach(function(el) {
            totalDeduction += parseFloat(el.value) || 0;
        });

        const net = basic + totalAllowance - totalDeduction;

        document.getElementById('displayBasic').innerText = formatRupiah(basic);
        document.getElementById('displayAllowance').innerText = '+ ' + formatRupiah(totalAllowance);
        document.getElementById('displayDeduction').innerText = '- ' + formatRupiah(totalDeduction);
        document.getElementById('displayNet').innerText = formatRupiah(net);
    }

    document.addEventListener('DOMContentLoaded', recalculateTotals);
</script>
@endsection
