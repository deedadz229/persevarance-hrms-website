@extends('layouts.app')

@section('title', __('reports.center_title'))

@section('content')
<style>
    .page-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
    .page-title-box h2 { margin: 0; font-size: 22px; font-weight: 700; color: var(--ink); letter-spacing: -.02em; }
    .page-title-box p { margin: 4px 0 0; color: var(--muted); font-size: 13px; }

    .reports-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 20px; }
    .report-card { padding: 24px; display: flex; flex-direction: column; justify-content: space-between; transition: transform .15s ease, box-shadow .15s ease; border: 1px solid var(--line); }
    .report-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(17, 39, 46, .08); }

    .report-icon { width: 44px; height: 44px; border-radius: 10px; display: grid; place-items: center; font-size: 20px; margin-bottom: 16px; font-weight: bold; }
    .icon-attendance { background: #e8f7f4; color: #0c8f82; }
    .icon-leave { background: #eaf1f8; color: #2b7bb9; }
    .icon-overtime { background: #fdf5e6; color: #c98804; }
    .icon-payroll { background: #f0fdf4; color: #15803d; }
    .icon-turnover { background: #f5f3ff; color: #7c3aed; }
    .icon-audit { background: #fff1f2; color: #e11d48; }

    .report-title { margin: 0 0 8px; font-size: 16px; font-weight: 700; color: var(--ink); }
    .report-desc { margin: 0 0 20px; font-size: 13px; color: var(--muted); line-height: 1.5; flex: 1; }

    .card-actions { display: flex; gap: 10px; align-items: center; border-top: 1px solid var(--line); padding-top: 16px; }
    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 8px; font-size: 12.5px; font-weight: 600; text-decoration: none; cursor: pointer; border: 1px solid transparent; transition: all .15s ease; }
    .btn-primary { background: var(--brand); color: #fff; flex: 1; justify-content: center; }
    .btn-primary:hover { background: var(--brand-dark); }
    .btn-outline { border-color: var(--line); background: var(--surface); color: var(--ink); }
    .btn-outline:hover { background: #f0f4f5; }
</style>

<div class="page-header">
    <div class="page-title-box">
        <h2>{{ __('reports.center_title') }}</h2>
        <p>{{ __('reports.center_subtitle') }}</p>
    </div>
</div>

<div class="reports-grid">
    {{-- 1. Laporan Absensi --}}
    <article class="card report-card">
        <div>
            <div class="report-icon icon-attendance">◷</div>
            <h3 class="report-title">{{ __('reports.attendance_report') }}</h3>
            <p class="report-desc">
                {{ __('reports.attendance_desc') }}
            </p>
        </div>
        <div class="card-actions">
            <a href="{{ route('reports.attendances') }}" class="btn btn-primary">{{ __('reports.view_report') }}</a>
            @if ($canExport)
                <div style="display: flex; gap: 4px;">
                    <a href="{{ route('reports.attendances.export', ['format' => 'csv']) }}" class="btn btn-outline" style="padding: 8px 9px; font-size: 11.5px;" title="Export CSV">CSV</a>
                    <a href="{{ route('reports.attendances.export', ['format' => 'xlsx']) }}" class="btn btn-outline" style="padding: 8px 9px; font-size: 11.5px; color: #0c8f82; border-color: #0c8f82;" title="Export Excel">Excel</a>
                    <a href="{{ route('reports.attendances.export', ['format' => 'pdf']) }}" class="btn btn-outline" style="padding: 8px 9px; font-size: 11.5px; color: #e11d48; border-color: #e11d48;" title="Export PDF">PDF</a>
                </div>
            @endif
        </div>
    </article>

    {{-- 2. Laporan Cuti & Izin --}}
    <article class="card report-card">
        <div>
            <div class="report-icon icon-leave">◫</div>
            <h3 class="report-title">{{ __('reports.leave_report') }}</h3>
            <p class="report-desc">
                {{ __('reports.leave_desc') }}
            </p>
        </div>
        <div class="card-actions">
            <a href="{{ route('reports.leaves') }}" class="btn btn-primary">{{ __('reports.view_report') }}</a>
            @if ($canExport)
                <div style="display: flex; gap: 4px;">
                    <a href="{{ route('reports.leaves.export', ['format' => 'csv']) }}" class="btn btn-outline" style="padding: 8px 9px; font-size: 11.5px;" title="Export CSV">CSV</a>
                    <a href="{{ route('reports.leaves.export', ['format' => 'xlsx']) }}" class="btn btn-outline" style="padding: 8px 9px; font-size: 11.5px; color: #0c8f82; border-color: #0c8f82;" title="Export Excel">Excel</a>
                    <a href="{{ route('reports.leaves.export', ['format' => 'pdf']) }}" class="btn btn-outline" style="padding: 8px 9px; font-size: 11.5px; color: #e11d48; border-color: #e11d48;" title="Export PDF">PDF</a>
                </div>
            @endif
        </div>
    </article>

    {{-- 3. Laporan Lembur --}}
    <article class="card report-card">
        <div>
            <div class="report-icon icon-overtime">◴</div>
            <h3 class="report-title">{{ __('reports.overtime_report') }}</h3>
            <p class="report-desc">
                {{ __('reports.overtime_desc') }}
            </p>
        </div>
        <div class="card-actions">
            <a href="{{ route('reports.overtimes') }}" class="btn btn-primary">{{ __('reports.view_report') }}</a>
            @if ($canExport)
                <div style="display: flex; gap: 4px;">
                    <a href="{{ route('reports.overtimes.export', ['format' => 'csv']) }}" class="btn btn-outline" style="padding: 8px 9px; font-size: 11.5px;" title="Export CSV">CSV</a>
                    <a href="{{ route('reports.overtimes.export', ['format' => 'xlsx']) }}" class="btn btn-outline" style="padding: 8px 9px; font-size: 11.5px; color: #0c8f82; border-color: #0c8f82;" title="Export Excel">Excel</a>
                    <a href="{{ route('reports.overtimes.export', ['format' => 'pdf']) }}" class="btn btn-outline" style="padding: 8px 9px; font-size: 11.5px; color: #e11d48; border-color: #e11d48;" title="Export PDF">PDF</a>
                </div>
            @endif
        </div>
    </article>

    {{-- 4. Laporan Penggajian --}}
    <article class="card report-card">
        <div>
            <div class="report-icon icon-payroll">¤</div>
            <h3 class="report-title">{{ __('reports.payroll_report') }}</h3>
            <p class="report-desc">
                {{ __('reports.payroll_desc') }}
            </p>
        </div>
        <div class="card-actions">
            <a href="{{ route('reports.payrolls') }}" class="btn btn-primary">{{ __('reports.view_report') }}</a>
            @if ($canExport)
                <div style="display: flex; gap: 4px;">
                    <a href="{{ route('reports.payrolls.export', ['format' => 'csv']) }}" class="btn btn-outline" style="padding: 8px 9px; font-size: 11.5px;" title="Export CSV">CSV</a>
                    <a href="{{ route('reports.payrolls.export', ['format' => 'xlsx']) }}" class="btn btn-outline" style="padding: 8px 9px; font-size: 11.5px; color: #0c8f82; border-color: #0c8f82;" title="Export Excel">Excel</a>
                    <a href="{{ route('reports.payrolls.export', ['format' => 'pdf']) }}" class="btn btn-outline" style="padding: 8px 9px; font-size: 11.5px; color: #e11d48; border-color: #e11d48;" title="Export PDF">PDF</a>
                </div>
            @endif
        </div>
    </article>

    {{-- 5. Laporan Turnover Karyawan --}}
    <article class="card report-card">
        <div>
            <div class="report-icon icon-turnover">♙</div>
            <h3 class="report-title">{{ __('reports.turnover_report') }}</h3>
            <p class="report-desc">
                {{ __('reports.turnover_desc') }}
            </p>
        </div>
        <div class="card-actions">
            <a href="{{ route('reports.turnover') }}" class="btn btn-primary">{{ __('reports.view_report') }}</a>
            @if ($canExport)
                <div style="display: flex; gap: 4px;">
                    <a href="{{ route('reports.turnover.export', ['format' => 'csv']) }}" class="btn btn-outline" style="padding: 8px 9px; font-size: 11.5px;" title="Export CSV">CSV</a>
                    <a href="{{ route('reports.turnover.export', ['format' => 'xlsx']) }}" class="btn btn-outline" style="padding: 8px 9px; font-size: 11.5px; color: #0c8f82; border-color: #0c8f82;" title="Export Excel">Excel</a>
                    <a href="{{ route('reports.turnover.export', ['format' => 'pdf']) }}" class="btn btn-outline" style="padding: 8px 9px; font-size: 11.5px; color: #e11d48; border-color: #e11d48;" title="Export PDF">PDF</a>
                </div>
            @endif
        </div>
    </article>

    {{-- 6. Laporan Audit Trail (Protected) --}}
    @if ($canViewAudit)
        <article class="card report-card">
            <div>
                <div class="report-icon icon-audit">◉</div>
                <h3 class="report-title">{{ __('reports.audit_report') }}</h3>
                <p class="report-desc">
                    {{ __('reports.audit_desc') }}
                </p>
            </div>
            <div class="card-actions">
                <a href="{{ route('reports.audit-logs') }}" class="btn btn-primary">{{ __('reports.view_report') }}</a>
                @if ($canExport)
                    <div style="display: flex; gap: 4px;">
                        <a href="{{ route('reports.audit-logs.export', ['format' => 'csv']) }}" class="btn btn-outline" style="padding: 8px 9px; font-size: 11.5px;" title="Export CSV">CSV</a>
                        <a href="{{ route('reports.audit-logs.export', ['format' => 'xlsx']) }}" class="btn btn-outline" style="padding: 8px 9px; font-size: 11.5px; color: #0c8f82; border-color: #0c8f82;" title="Export Excel">Excel</a>
                        <a href="{{ route('reports.audit-logs.export', ['format' => 'pdf']) }}" class="btn btn-outline" style="padding: 8px 9px; font-size: 11.5px; color: #e11d48; border-color: #e11d48;" title="Export PDF">PDF</a>
                    </div>
                @endif
            </div>
        </article>
    @endif
</div>
@endsection
