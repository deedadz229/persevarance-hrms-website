@extends('layouts.app')

@section('title', 'Detail Payroll · ' . ($payroll->employee?->name ?? 'Karyawan'))

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
    .btn-warning { background: #c98804; color: #fff; }
    .btn-warning:hover { background: #a67003; }

    .grid-details { display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(320px, .8fr); gap: 20px; }
    .section-card { padding: 24px; margin-bottom: 20px; }
    .section-card h3 { margin: 0 0 16px; font-size: 15px; font-weight: 700; color: var(--ink); border-bottom: 1px solid var(--line); padding-bottom: 10px; }

    .info-list { display: grid; grid-template-columns: 150px 1fr; row-gap: 14px; column-gap: 16px; font-size: 13px; }
    .info-label { color: var(--muted); font-weight: 500; }
    .info-value { color: var(--ink); font-weight: 600; }

    .badge-status { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; }
    .badge-draft { background: #fff4dd; color: #9a6a00; }
    .badge-generated { background: #e6f7f2; color: #0a7b6f; }
    .badge-paid { background: #eaf1f8; color: #1e5a8a; }

    .table-component { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 13px; }
    .table-component th { padding: 10px 12px; background: #fafbfc; border-bottom: 1px solid var(--line); font-size: 11px; text-transform: uppercase; color: var(--muted); font-weight: 700; }
    .table-component td { padding: 11px 12px; border-bottom: 1px solid var(--line); vertical-align: middle; }

    .salary-summary-box { padding: 20px; border-radius: 10px; background: #f4fbf9; border: 1px solid #bce6d9; margin-top: 16px; }

    @media (max-width: 900px) { .grid-details { grid-template-columns: 1fr; } }
</style>

<div class="page-header">
    <div class="page-title-box">
        <h2>Detail Payroll Karyawan</h2>
        <p>Rincian data gaji pokok, pendapatan tambahan, potongan, dan status penggajian</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('payrolls.index') }}" class="btn btn-outline">
            <span>←</span> Kembali
        </a>
        <a href="{{ route('payrolls.slip', $payroll) }}" class="btn btn-primary" target="_blank">
            <span>🖨</span> Cetak Slip Gaji
        </a>
        @if (auth()->user()->role?->name !== 'taiwan_account' && (auth()->user()->role?->name === 'super_admin' || auth()->user()->role?->permissions->contains('name', 'manage_payroll')))
            <a href="{{ route('payrolls.correction', $payroll) }}" class="btn btn-warning">
                <span>✎</span> Koreksi Payroll
            </a>
        @endif
    </div>
</div>

<div class="grid-details">
    <div>
        <div class="card section-card">
            <h3>Informasi Karyawan</h3>
            <div class="info-list">
                <span class="info-label">Nama Karyawan</span>
                <span class="info-value">
                    <a href="{{ $payroll->employee ? route('employees.show', $payroll->employee) : '#' }}" style="color: var(--brand); text-decoration: none;">
                        {{ $payroll->employee?->name ?? 'Karyawan Dihapus' }}
                    </a>
                </span>

                <span class="info-label">ID / Kode Karyawan</span>
                <span class="info-value"><code>{{ $payroll->employee?->employee_code ?? '-' }}</code></span>

                <span class="info-label">Departemen</span>
                <span class="info-value">{{ $payroll->employee?->department?->name ?? '-' }}</span>

                <span class="info-label">Jabatan (Posisi)</span>
                <span class="info-value">{{ $payroll->employee?->position?->name ?? '-' }}</span>
            </div>
        </div>

        <div class="card section-card">
            <h3>Rincian Komponen Pendapatan (Income / Allowance)</h3>
            <table class="table-component">
                <thead>
                    <tr>
                        <th>Komponen</th>
                        <th>Keterangan</th>
                        <th style="text-align: right;">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Gaji Pokok (Basic Salary)</strong></td>
                        <td style="color: var(--muted);">Gaji dasar resmi</td>
                        <td style="text-align: right; font-weight: 600;">Rp {{ number_format((float) $payroll->basic_salary, 0, ',', '.') }}</td>
                    </tr>
                    @forelse ($payroll->allowances as $allowance)
                        <tr>
                            <td>{{ $allowance->name }}</td>
                            <td style="color: var(--muted);">{{ $allowance->description ?? '-' }}</td>
                            <td style="text-align: right; color: #0c8f82; font-weight: 600;">+Rp {{ number_format((float) $allowance->amount, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align: center; color: var(--muted); padding: 14px;">Tidak ada komponen tunjangan tambahan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <h3 style="margin-top: 24px;">Rincian Komponen Potongan (Deduction: BPJS, PPh21, dll)</h3>
            <table class="table-component">
                <thead>
                    <tr>
                        <th>Komponen</th>
                        <th>Keterangan</th>
                        <th style="text-align: right;">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payroll->deductions as $deduction)
                        <tr>
                            <td>{{ $deduction->name }}</td>
                            <td style="color: var(--muted);">{{ $deduction->description ?? '-' }}</td>
                            <td style="text-align: right; color: #b92e2e; font-weight: 600;">-Rp {{ number_format((float) $deduction->amount, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align: center; color: var(--muted); padding: 14px;">Tidak ada potongan pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>
        <div class="card section-card">
            <h3>Ringkasan Gaji Bersih</h3>
            <div class="info-list">
                <span class="info-label">Periode Gaji</span>
                <span class="info-value" style="color: var(--brand-dark); font-size: 14px;">
                    {{ $payroll->period_label }}
                </span>

                <span class="info-label">Status</span>
                <span class="info-value">
                    <span class="badge-status {{ $payroll->status_badge_class }}">
                        {{ $payroll->status_label }}
                    </span>
                </span>

                <span class="info-label">Waktu Generated</span>
                <span class="info-value">{{ $payroll->generated_at?->translatedFormat('d F Y, H:i') ?? '-' }}</span>

                <span class="info-label">Gaji Pokok</span>
                <span class="info-value">Rp {{ number_format((float) $payroll->basic_salary, 0, ',', '.') }}</span>

                <span class="info-label" style="color: #0c8f82;">Total Tunjangan</span>
                <span class="info-value" style="color: #0c8f82;">+Rp {{ number_format((float) $payroll->total_allowance, 0, ',', '.') }}</span>

                <span class="info-label" style="color: #b92e2e;">Total Potongan</span>
                <span class="info-value" style="color: #b92e2e;">-Rp {{ number_format((float) $payroll->total_deduction, 0, ',', '.') }}</span>
            </div>

            <div class="salary-summary-box">
                <span style="display: block; font-size: 11px; text-transform: uppercase; color: var(--muted); font-weight: 700; margin-bottom: 4px;">
                    Gaji Bersih Diterima (Net Salary)
                </span>
                <strong style="display: block; font-size: 26px; color: var(--brand-dark); letter-spacing: -.03em;">
                    Rp {{ number_format((float) $payroll->net_salary, 0, ',', '.') }}
                </strong>
                <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 4px;">
                    Formula: Gaji Pokok + Tunjangan - Potongan
                </small>
            </div>

            @if ($payroll->status === 'draft' && auth()->user()->role?->name !== 'taiwan_account' && (auth()->user()->role?->name === 'super_admin' || auth()->user()->role?->permissions->contains('name', 'manage_payroll')))
                <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--line);">
                    <form action="{{ route('payrolls.generate', $payroll) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin memfinalisasi data payroll ini menjadi Dihasilkan (Generated)?');">
                        @csrf
                        <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">
                            ✓ Finalisasi / Generate Slip Gaji
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
