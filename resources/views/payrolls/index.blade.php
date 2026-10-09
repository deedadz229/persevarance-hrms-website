@extends('layouts.app')

@section('title', 'Manajemen Payroll')

@section('content')
<style>
    .page-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 20px; flex-wrap: wrap; }
    .page-title-box h2 { margin: 0; font-size: 22px; font-weight: 700; color: var(--ink); letter-spacing: -.02em; }
    .page-title-box p { margin: 4px 0 0; color: var(--muted); font-size: 13px; }
    .header-actions { display: flex; align-items: center; gap: 10px; }

    .nav-tabs { display: flex; gap: 8px; border-bottom: 1px solid var(--line); margin-bottom: 20px; }
    .nav-tab { padding: 10px 16px; font-size: 13.5px; font-weight: 600; text-decoration: none; color: var(--muted); border-bottom: 2px solid transparent; transition: all .15s ease; }
    .nav-tab:hover { color: var(--ink); }
    .nav-tab.is-active { color: var(--brand); border-bottom-color: var(--brand); }

    .summary-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin-bottom: 20px; }
    .summary-card { padding: 18px 20px; }
    .summary-card span { color: var(--muted); font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
    .summary-card strong { display: block; margin-top: 8px; font-size: 24px; letter-spacing: -.03em; }
    .summary-card small { display: block; margin-top: 4px; color: #81909a; font-size: 11.5px; }

    .filter-card { padding: 18px 20px; margin-bottom: 20px; }
    .filter-form { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
    .filter-group { flex: 1; min-width: 140px; }
    .filter-group.search-group { flex: 2; min-width: 200px; }
    .form-control { width: 100%; padding: 9px 12px; border: 1px solid var(--line); border-radius: 8px; font-size: 13px; background: #fafbfc; color: var(--ink); outline: none; }
    .form-control:focus { border-color: var(--brand); background: #fff; box-shadow: 0 0 0 3px rgba(12, 143, 130, .1); }

    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; cursor: pointer; border: 1px solid transparent; transition: all .15s ease; }
    .btn-primary { background: var(--brand); color: #fff; }
    .btn-primary:hover { background: var(--brand-dark); }
    .btn-outline { border-color: var(--line); background: var(--surface); color: var(--ink); }
    .btn-outline:hover { background: #f0f4f5; }
    .btn-sm { padding: 5px 10px; font-size: 12px; border-radius: 6px; }

    .table-container { overflow-x: auto; }
    .table-payroll { width: 100%; border-collapse: collapse; text-align: left; }
    .table-payroll th { padding: 13px 16px; background: #fbfcfc; color: var(--muted); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid var(--line); white-space: nowrap; }
    .table-payroll td { padding: 14px 16px; border-bottom: 1px solid var(--line); font-size: 13px; vertical-align: middle; }
    .table-payroll tbody tr:hover { background: #fafcfc; }

    .emp-info strong { display: block; font-weight: 600; color: var(--ink); font-size: 13.5px; }
    .emp-info span { display: block; margin-top: 2px; color: var(--muted); font-size: 12px; }

    .badge-status { display: inline-flex; align-items: center; padding: 3px 9px; border-radius: 999px; font-size: 11.5px; font-weight: 600; }
    .badge-draft { background: #fff4dd; color: #9a6a00; }
    .badge-generated { background: #e6f7f2; color: #0a7b6f; }
    .badge-paid { background: #eaf1f8; color: #1e5a8a; }

    .action-group { display: flex; align-items: center; gap: 6px; justify-content: flex-end; }
    .action-link { padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; border: 1px solid var(--line); color: var(--ink); background: #fff; }
    .action-link:hover { border-color: var(--brand); color: var(--brand-dark); background: var(--brand-soft); }

    .pagination-wrapper { padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: var(--muted); border-top: 1px solid var(--line); }
    .empty-state { padding: 48px 20px; text-align: center; color: var(--muted); }

    @media (max-width: 1024px) { .summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
</style>

<div class="page-header">
    <div class="page-title-box">
        <h2>Manajemen Payroll</h2>
        <p>Pengelolaan kompensasi, evaluasi penggajian, perhitungan gaji bersih, dan rekapitulasi</p>
    </div>
    <div class="header-actions">
        @if (auth()->user()->role?->name !== 'taiwan_account' && (auth()->user()->role?->name === 'super_admin' || auth()->user()->role?->permissions->contains('name', 'manage_payroll')))
            <a href="{{ route('payrolls.create') }}" class="btn btn-primary">
                <span>+</span> Proses / Buat Payroll
            </a>
        @endif
    </div>
</div>

<nav class="nav-tabs" aria-label="Menu Tab Payroll">
    <a href="{{ route('payrolls.index') }}" class="nav-tab is-active">Daftar Payroll</a>
    <a href="{{ route('payrolls.evaluation') }}" class="nav-tab">Evaluasi Pra-Finalisasi</a>
    <a href="{{ route('payrolls.department-summary') }}" class="nav-tab">Rekap Departemen</a>
</nav>

<section class="summary-grid" aria-label="Ringkasan Payroll">
    <article class="card summary-card">
        <span>Total Payroll</span>
        <strong style="color: var(--ink);">{{ number_format($summary['total_count']) }} <small style="display:inline; font-size:14px; font-weight:normal; color:var(--muted);">Slip</small></strong>
        <small>Akumulasi slip gaji periode terpilih</small>
    </article>

    <article class="card summary-card">
        <span>Total BPJS</span>
        <strong style="color: #2b7bb9;">Rp {{ number_format($summary['total_bpjs'], 0, ',', '.') }}</strong>
        <small>Total potongan BPJS karyawan</small>
    </article>

    <article class="card summary-card">
        <span>Total PPh21</span>
        <strong style="color: #c98804;">Rp {{ number_format($summary['total_pph21'], 0, ',', '.') }}</strong>
        <small>Total potongan pajak PPh21</small>
    </article>

    <article class="card summary-card">
        <span>Total Net Salary</span>
        <strong style="color: #0c8f82;">Rp {{ number_format($summary['total_net_salary'], 0, ',', '.') }}</strong>
        <small>Total bersih yang dibayarkan</small>
    </article>
</section>

<div class="card filter-card">
    <form action="{{ route('payrolls.index') }}" method="GET" class="filter-form">
        <div class="filter-group search-group">
            <input type="text" name="search" value="{{ $currentSearch }}" placeholder="Cari nama karyawan atau ID..." class="form-control">
        </div>

        <div class="filter-group">
            <select name="month" class="form-control">
                <option value="">Semua Bulan</option>
                @foreach ($months as $num => $name)
                    <option value="{{ $num }}" {{ $currentMonth == $num ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="filter-group" style="max-width: 120px;">
            <select name="year" class="form-control">
                <option value="">Semua Tahun</option>
                @for ($y = (int) date('Y') + 1; $y >= 2024; $y--)
                    <option value="{{ $y }}" {{ $currentYear == $y ? 'selected' : '' }}>
                        {{ $y }}
                    </option>
                @endfor
            </select>
        </div>

        <div class="filter-group">
            <select name="department_id" class="form-control">
                <option value="">Semua Departemen</option>
                @foreach ($departments as $dept)
                    <option value="{{ $dept->id }}" {{ $currentDepartment == $dept->id ? 'selected' : '' }}>
                        {{ $dept->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="filter-group">
            <select name="status" class="form-control">
                <option value="">Semua Status</option>
                <option value="draft" {{ $currentStatus === 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="generated" {{ $currentStatus === 'generated' ? 'selected' : '' }}>Dihasilkan</option>
                <option value="paid" {{ $currentStatus === 'paid' ? 'selected' : '' }}>Dibayar</option>
            </select>
        </div>

        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary" style="padding: 9px 14px;">Filter</button>
            @if ($currentSearch || $currentMonth || $currentYear || $currentDepartment || $currentStatus)
                <a href="{{ route('payrolls.index') }}" class="btn btn-outline" style="padding: 9px 12px;" title="Reset Filter">Reset</a>
            @endif
        </div>
    </form>
</div>

<div class="card">
    <div class="table-container">
        <table class="table-payroll">
            <thead>
                <tr>
                    <th>Karyawan</th>
                    <th>Departemen</th>
                    <th>Periode</th>
                    <th>Gaji Pokok</th>
                    <th>Tunjangan</th>
                    <th>Potongan</th>
                    <th>Gaji Bersih</th>
                    <th>Status</th>
                    <th style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payrolls as $payroll)
                    <tr>
                        <td>
                            <div class="emp-info">
                                <strong>{{ $payroll->employee?->name ?? 'Karyawan dihapus' }}</strong>
                                <span><code>{{ $payroll->employee?->employee_code ?? '-' }}</code></span>
                            </div>
                        </td>
                        <td>{{ $payroll->employee?->department?->name ?? '-' }}</td>
                        <td><strong>{{ $payroll->period_label }}</strong></td>
                        <td>Rp {{ number_format((float) $payroll->basic_salary, 0, ',', '.') }}</td>
                        <td style="color: #0c8f82;">+Rp {{ number_format((float) $payroll->total_allowance, 0, ',', '.') }}</td>
                        <td style="color: #b92e2e;">-Rp {{ number_format((float) $payroll->total_deduction, 0, ',', '.') }}</td>
                        <td>
                            <strong style="color: var(--brand-dark); font-size: 14px;">
                                Rp {{ number_format((float) $payroll->net_salary, 0, ',', '.') }}
                            </strong>
                        </td>
                        <td>
                            <span class="badge-status {{ $payroll->status_badge_class }}">
                                {{ $payroll->status_label }}
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <div class="action-group">
                                <a href="{{ route('payrolls.show', $payroll) }}" class="action-link" title="Detail Payroll">Detail</a>
                                <a href="{{ route('payrolls.slip', $payroll) }}" class="action-link" title="Lihat Slip Gaji" target="_blank">Slip Gaji</a>
                                @if (auth()->user()->role?->name !== 'taiwan_account' && (auth()->user()->role?->name === 'super_admin' || auth()->user()->role?->permissions->contains('name', 'manage_payroll')))
                                    <a href="{{ route('payrolls.correction', $payroll) }}" class="action-link" style="color: #9a6a00;" title="Koreksi Payroll">Koreksi</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="empty-state">
                            <p style="font-size: 15px; margin: 0 0 6px;">Tidak ada data payroll yang sesuai kriteria.</p>
                            <small>Silakan ubah filter periode atau buat payroll baru.</small>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($payrolls->hasPages())
        <div class="pagination-wrapper">
            <span>Menampilkan {{ $payrolls->firstItem() }} – {{ $payrolls->lastItem() }} dari {{ $payrolls->total() }} data</span>
            {{ $payrolls->links() }}
        </div>
    @endif
</div>
@endsection
