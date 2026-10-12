@extends('layouts.app')

@section('title', __('payrolls.correction_title') . ' · ' . ($payroll->employee?->name ?? __('payrolls.employee')))

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
    .btn-warning { background: #c98804; color: #fff; }
    .btn-warning:hover { background: #a67003; }

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

    .section-title { margin: 24px 0 12px; font-size: 15px; font-weight: 700; color: var(--ink); border-bottom: 1px solid var(--line); padding-bottom: 8px; }

    .component-row { display: grid; grid-template-columns: minmax(180px, 1.2fr) minmax(140px, 1fr) minmax(160px, 1.2fr); gap: 12px; margin-bottom: 10px; align-items: center; }

    .calc-box { margin-top: 24px; padding: 20px; border-radius: 10px; background: #fdfaf3; border: 1px solid #f6e8b8; }
    .calc-list { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; font-size: 13px; }
    .calc-item { text-align: center; }
    .calc-item span { display: block; color: var(--muted); font-size: 11px; text-transform: uppercase; font-weight: 600; margin-bottom: 4px; }
    .calc-item strong { font-size: 18px; color: var(--ink); }

    .reason-box { margin-top: 24px; padding: 18px 20px; border-radius: 10px; background: #fff8f8; border: 1px solid #f8d7da; }

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
            <h2>{{ __('payrolls.correction_title') }}</h2>
            <p>{{ __('payrolls.correction_subtitle') }}</p>
        </div>
        <div>
            <a href="{{ route('payrolls.show', $payroll) }}" class="btn btn-outline">
                <span>←</span> {{ __('common.cancel') }}
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
        <form action="{{ route('payrolls.update-correction', $payroll) }}" method="POST" id="correctionForm">
            @csrf

            <div style="background: var(--canvas); padding: 16px 20px; border-radius: 8px; margin-bottom: 24px; display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; font-size: 13px;">
                <div>
                    <span style="color: var(--muted);">{{ __('payrolls.employee') }}:</span>
                    <strong>{{ $payroll->employee?->name }} ({{ $payroll->employee?->employee_code }})</strong>
                </div>
                <div>
                    <span style="color: var(--muted);">{{ __('payrolls.department') }} / {{ __('employees.position') }}:</span>
                    <strong>{{ $payroll->employee?->department?->name }} · {{ $payroll->employee?->position?->name }}</strong>
                </div>
                <div>
                    <span style="color: var(--muted);">{{ __('payrolls.period') }}:</span>
                    <strong>{{ $payroll->period_label }}</strong>
                </div>
                <div>
                    <span style="color: var(--muted);">{{ __('common.status') }}:</span>
                    <strong>{{ $payroll->status_label }}</strong>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="basic_salary">{{ __('payrolls.basic_salary_official') }} <span class="required">*</span></label>
                <input type="number" step="0.01" min="0" id="basic_salary" name="basic_salary" value="{{ old('basic_salary', $payroll->basic_salary) }}" class="form-control @error('basic_salary') is-invalid @enderror" required oninput="recalculateTotals()">
                @error('basic_salary')
                    <span class="error-feedback">{{ $message }}</span>
                @enderror
            </div>

            <h3 class="section-title">{{ __('payrolls.allowances') }}</h3>
            <div id="allowancesContainer">
                @php
                    $allowances = old('allowances', $payroll->allowances->toArray());
                    if (empty($allowances)) {
                        $allowances = [['name' => '', 'amount' => 0, 'description' => '']];
                    }
                @endphp
                @foreach ($allowances as $index => $item)
                    <div class="component-row">
                        <input type="text" name="allowances[{{ $index }}][name]" value="{{ $item['name'] ?? '' }}" class="form-control" placeholder="{{ __('payrolls.component_name') }}">
                        <input type="number" step="0.01" min="0" name="allowances[{{ $index }}][amount]" value="{{ $item['amount'] ?? 0 }}" class="form-control allowance-input" placeholder="{{ __('payrolls.component_amount') }}" oninput="recalculateTotals()">
                        <input type="text" name="allowances[{{ $index }}][description]" value="{{ $item['description'] ?? '' }}" class="form-control" placeholder="{{ __('payrolls.description') }}">
                    </div>
                @endforeach
            </div>

            <h3 class="section-title">{{ __('payrolls.deductions') }}</h3>
            <div id="deductionsContainer">
                @php
                    $deductions = old('deductions', $payroll->deductions->toArray());
                    if (empty($deductions)) {
                        $deductions = [['name' => '', 'amount' => 0, 'description' => '']];
                    }
                @endphp
                @foreach ($deductions as $index => $item)
                    <div class="component-row">
                        <input type="text" name="deductions[{{ $index }}][name]" value="{{ $item['name'] ?? '' }}" class="form-control" placeholder="{{ __('payrolls.component_name') }}">
                        <input type="number" step="0.01" min="0" name="deductions[{{ $index }}][amount]" value="{{ $item['amount'] ?? 0 }}" class="form-control deduction-input" placeholder="{{ __('payrolls.component_amount') }}" oninput="recalculateTotals()">
                        <input type="text" name="deductions[{{ $index }}][description]" value="{{ $item['description'] ?? '' }}" class="form-control" placeholder="{{ __('payrolls.description') }}">
                    </div>
                @endforeach
            </div>

            <div class="calc-box">
                <div class="calc-list">
                    <div class="calc-item">
                        <span>{{ __('payrolls.basic_salary') }}</span>
                        <strong id="displayBasic">Rp 0</strong>
                    </div>
                    <div class="calc-item" style="color: #0c8f82;">
                        <span>+ {{ __('payrolls.total_allowances') }}</span>
                        <strong id="displayAllowance" style="color: #0c8f82;">Rp 0</strong>
                    </div>
                    <div class="calc-item" style="color: #b92e2e;">
                        <span>- {{ __('payrolls.total_deductions') }}</span>
                        <strong id="displayDeduction" style="color: #b92e2e;">Rp 0</strong>
                    </div>
                    <div class="calc-item" style="border-left: 2px solid #f6e8b8; padding-left: 16px;">
                        <span>= {{ __('payrolls.net_salary_received') }}</span>
                        <strong id="displayNet" style="color: #a67003; font-size: 22px;">Rp 0</strong>
                    </div>
                </div>
            </div>

            <div class="reason-box">
                <label class="form-label" for="correction_reason" style="color: #a33e3e;">
                    {{ __('payrolls.correction_reason') }} <span class="required">*</span>
                </label>
                <p style="font-size: 12px; color: var(--muted); margin: 2px 0 8px;">
                    Sesuai kepatuhan audit sistem, setiap penyesuaian nominal payroll wajib menyertakan alasan atau dokumen referensi yang jelas.
                </p>
                <textarea id="correction_reason" name="correction_reason" rows="3" class="form-control @error('correction_reason') is-invalid @enderror" placeholder="{{ __('payrolls.correction_reason_placeholder') }}" required>{{ old('correction_reason') }}</textarea>
                @error('correction_reason')
                    <span class="error-feedback">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-actions">
                <a href="{{ route('payrolls.show', $payroll) }}" class="btn btn-outline">{{ __('common.cancel') }}</a>
                <button type="submit" class="btn btn-warning">{{ __('payrolls.save_correction') }}</button>
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
