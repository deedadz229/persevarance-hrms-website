<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('payrolls.slip_title') }} · {{ $payroll->employee?->name ?? __('payrolls.employee') }} ({{ $payroll->period_label }})</title>
    <style>
        :root { --ink: #172b3a; --muted: #53636e; --line: #cbd5e1; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 40px 20px; font-family: Inter, ui-sans-serif, system-ui, -apple-system, sans-serif; color: var(--ink); background: #f8fafc; font-size: 13px; line-height: 1.5; }
        .slip-box { max-width: 800px; margin: 0 auto; background: #fff; padding: 40px 48px; border: 1px solid var(--line); border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,.05); }

        .company-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #0f766e; padding-bottom: 20px; margin-bottom: 24px; }
        .company-title h1 { margin: 0; font-size: 20px; font-weight: 800; color: #0f766e; text-transform: uppercase; letter-spacing: .05em; }
        .company-title p { margin: 4px 0 0; font-size: 12px; color: var(--muted); }
        .slip-badge { text-align: right; }
        .slip-badge h2 { margin: 0; font-size: 18px; font-weight: 700; color: var(--ink); }
        .slip-badge span { display: block; font-size: 12px; color: var(--muted); margin-top: 2px; }

        .meta-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 28px; background: #f8fafc; padding: 16px 20px; border-radius: 6px; }
        .meta-row { display: flex; font-size: 12.5px; }
        .meta-label { width: 130px; color: var(--muted); font-weight: 500; }
        .meta-val { font-weight: 600; color: var(--ink); }

        .tables-split { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px; }
        .component-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
        .component-table th { background: #f1f5f9; padding: 8px 10px; text-align: left; font-size: 11px; text-transform: uppercase; color: var(--muted); font-weight: 700; border-bottom: 1px solid var(--line); }
        .component-table td { padding: 9px 10px; border-bottom: 1px solid #f1f5f9; }
        .component-table tr.total-row td { background: #f8fafc; font-weight: 700; border-top: 1px solid var(--line); border-bottom: 1px solid var(--line); }

        .net-summary { display: flex; justify-content: space-between; align-items: center; background: #f0fdfa; border: 2px solid #99f6e4; padding: 18px 24px; border-radius: 6px; margin-bottom: 40px; }
        .net-summary span { font-size: 13px; font-weight: 700; color: #0f766e; text-transform: uppercase; }
        .net-summary strong { font-size: 22px; color: #0f766e; font-weight: 800; }

        .signature-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 40px; text-align: center; margin-top: 30px; }
        .sign-box { padding: 10px; }
        .sign-title { font-size: 12px; color: var(--muted); margin-bottom: 70px; }
        .sign-name { font-weight: 700; text-decoration: underline; }
        .sign-sub { font-size: 11.5px; color: var(--muted); margin-top: 2px; }

        .slip-actions { max-width: 800px; margin: 0 auto 20px; display: flex; justify-content: space-between; align-items: center; }
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; text-decoration: none; cursor: pointer; border: 1px solid transparent; }
        .btn-print { background: #0f766e; color: #fff; }
        .btn-print:hover { background: #0d9488; }
        .btn-back { background: #fff; color: var(--ink); border-color: var(--line); }
        .btn-back:hover { background: #f1f5f9; }

        @media print {
            body { background: #fff; padding: 0; font-size: 11px; }
            .slip-box { box-shadow: none; border: 0; padding: 20px; max-width: 100%; }
            .slip-actions { display: none !important; }
            .net-summary { background: #f9f9f9 !important; border-color: #ccc !important; }
        }
    </style>
</head>
<body>

<div class="slip-actions">
    <a href="{{ route('payrolls.show', $payroll) }}" class="btn btn-back">← {{ __('payrolls.back_to_detail') }}</a>
    <button type="button" class="btn btn-print" onclick="window.print()">{{ __('payrolls.print_document') }}</button>
</div>

<div class="slip-box">
    <header class="company-header">
        <div class="company-title">
            <h1>Perseverance Technology Co., Ltd.</h1>
            <p>Human Resource Management & Workforce System</p>
        </div>
        <div class="slip-badge">
            <h2>{{ __('payrolls.payslip_uppercase') }}</h2>
            <span>{{ __('payrolls.period') }}: <strong>{{ $payroll->period_label }}</strong></span>
        </div>
    </header>

    <section class="meta-grid">
        <div class="meta-row">
            <span class="meta-label">{{ __('employees.employee_name') }}</span>
            <span class="meta-val">: {{ $payroll->employee?->name ?? '-' }}</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">{{ __('employees.department') }}</span>
            <span class="meta-val">: {{ $payroll->employee?->department?->name ?? '-' }}</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">{{ __('employees.employee_code_label') }}</span>
            <span class="meta-val">: {{ $payroll->employee?->employee_code ?? '-' }}</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">{{ __('employees.position_label') }}</span>
            <span class="meta-val">: {{ $payroll->employee?->position?->name ?? '-' }}</span>
        </div>
    </section>

    <div class="tables-split">
        <div>
            <table class="component-table">
                <thead>
                    <tr>
                        <th>{{ __('payrolls.income_table') }}</th>
                        <th style="text-align: right;">{{ __('payrolls.amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ __('payrolls.basic_salary') }}</td>
                        <td style="text-align: right;">Rp {{ number_format((float) $payroll->basic_salary, 0, ',', '.') }}</td>
                    </tr>
                    @forelse ($payroll->allowances as $allowance)
                        <tr>
                            <td>{{ $allowance->name }}</td>
                            <td style="text-align: right;">Rp {{ number_format((float) $allowance->amount, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                    @endforelse
                    <tr class="total-row">
                        <td>{{ __('payrolls.total_income') }}</td>
                        <td style="text-align: right;">
                            Rp {{ number_format((float) ($payroll->basic_salary + $payroll->total_allowance), 0, ',', '.') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div>
            <table class="component-table">
                <thead>
                    <tr>
                        <th>{{ __('payrolls.deductions_table') }}</th>
                        <th style="text-align: right;">{{ __('payrolls.amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payroll->deductions as $deduction)
                        <tr>
                            <td>{{ $deduction->name }}</td>
                            <td style="text-align: right;">Rp {{ number_format((float) $deduction->amount, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" style="text-align: center; color: var(--muted); padding: 12px;">{{ __('payrolls.no_deductions') }}</td>
                        </tr>
                    @endforelse
                    <tr class="total-row">
                        <td>{{ __('payrolls.total_deductions') }}</td>
                        <td style="text-align: right;">
                            Rp {{ number_format((float) $payroll->total_deduction, 0, ',', '.') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="net-summary">
        <span>{{ __('payrolls.take_home_pay') }}</span>
        <strong>Rp {{ number_format((float) $payroll->net_salary, 0, ',', '.') }}</strong>
    </div>

    <footer class="signature-grid">
        <div class="sign-box">
            <div class="sign-title">{{ __('payrolls.recipient_sign') }}</div>
            <div class="sign-name">{{ $payroll->employee?->name ?? __('payrolls.employee') }}</div>
            <div class="sign-sub">ID: {{ $payroll->employee?->employee_code ?? '-' }}</div>
        </div>

        <div class="sign-box">
            <div class="sign-title">{{ __('payrolls.manager_sign') }}</div>
            <div class="sign-name">PTI Management</div>
            <div class="sign-sub">{{ __('payrolls.published_on', ['date' => $payroll->generated_at ? $payroll->generated_at->format('d/m/Y') : date('d/m/Y')]) }}</div>
        </div>
    </footer>
</div>

</body>
</html>
