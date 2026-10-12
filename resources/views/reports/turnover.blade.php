@extends('layouts.app')

@section('title', 'Laporan Turnover & Status Karyawan')

@section('content')
<style>
    .page-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 20px; flex-wrap: wrap; }
    .page-title-box h2 { margin: 0; font-size: 22px; font-weight: 700; color: var(--ink); letter-spacing: -.02em; }
    .page-title-box p { margin: 4px 0 0; color: var(--muted); font-size: 13px; }
    .header-actions { display: flex; align-items: center; gap: 10px; }

    .summary-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin-bottom: 20px; }
    .summary-card { padding: 18px 20px; }
    .summary-card span { color: var(--muted); font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
    .summary-card strong { display: block; margin-top: 8px; font-size: 26px; letter-spacing: -.03em; }
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
    .btn-success { background: #0c8f82; color: #fff; }
    .btn-success:hover { background: #076d65; }

    .table-container { overflow-x: auto; }
    .table-report { width: 100%; border-collapse: collapse; text-align: left; }
    .table-report th { padding: 13px 16px; background: #fbfcfc; color: var(--muted); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid var(--line); white-space: nowrap; }
    .table-report td { padding: 13px 16px; border-bottom: 1px solid var(--line); font-size: 13px; vertical-align: middle; }
    .table-report tbody tr:hover { background: #fafcfc; }

    .emp-info strong { display: block; font-weight: 600; color: var(--ink); font-size: 13px; }
    .emp-info span { display: block; margin-top: 2px; color: var(--muted); font-size: 11.5px; }

    .badge-status { display: inline-flex; align-items: center; padding: 3px 9px; border-radius: 999px; font-size: 11.5px; font-weight: 600; }
    .badge-active { background: #e6f7f2; color: #0a7b6f; }
    .badge-inactive { background: #fff4dd; color: #9a6a00; }
    .badge-resigned { background: #feebeb; color: #b92e2e; }

    .pagination-wrapper { padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: var(--muted); border-top: 1px solid var(--line); }
    .empty-state { padding: 48px 20px; text-align: center; color: var(--muted); }

    @media (max-width: 1024px) { .summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
</style>

<div class="page-header">
    <div class="page-title-box">
        <h2>Laporan Turnover & Status Karyawan</h2>
        <p>Rekapitulasi status kepegawaian, komposisi hubungan kerja, masa kerja, dan catatan terminasi</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('reports.index') }}" class="btn btn-outline">
            <span>←</span> Kembali ke Pusat Laporan
        </a>
        @if ($canExport)
            <div style="display: inline-flex; gap: 8px;">
                <a href="{{ route('reports.turnover.export', array_merge(request()->query(), ['format' => 'csv'])) }}" class="btn btn-outline" title="Export format CSV">
                    <span>⤓</span> CSV
                </a>
                <a href="{{ route('reports.turnover.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}" class="btn btn-success" title="Export format Excel (.xlsx)">
                    <span>⤓</span> Excel
                </a>
                <a href="{{ route('reports.turnover.export', array_merge(request()->query(), ['format' => 'pdf'])) }}" class="btn btn-outline" style="border-color: #e11d48; color: #e11d48;" title="Export format PDF">
                    <span>⤓</span> PDF
                </a>
            </div>
        @endif
    </div>
</div>

<section class="summary-grid" aria-label="Ringkasan Laporan Turnover">
    <article class="card summary-card">
        <span>Total Karyawan</span>
        <strong style="color: var(--ink);">{{ number_format($summary['total']) }} <small style="display:inline; font-size:13px; font-weight:normal; color:var(--muted);">Orang</small></strong>
        <small>Total karyawan tercatat di sistem</small>
    </article>
    <article class="card summary-card">
        <span>Karyawan Aktif</span>
        <strong style="color: #0c8f82;">{{ number_format($summary['active']) }} <small style="display:inline; font-size:13px; font-weight:normal; color:var(--muted);">Orang</small></strong>
        <small>Berstatus aktif bekerja</small>
    </article>
    <article class="card summary-card">
        <span>Non-Aktif / Cuti Panjang</span>
        <strong style="color: #c98804;">{{ number_format($summary['inactive']) }} <small style="display:inline; font-size:13px; font-weight:normal; color:var(--muted);">Orang</small></strong>
        <small>Status penangguhan atau non-aktif</small>
    </article>
    <article class="card summary-card">
        <span>Karyawan Resign</span>
        <strong style="color: #d93838;">{{ number_format($summary['resigned']) }} <small style="display:inline; font-size:13px; font-weight:normal; color:var(--muted);">Orang</small></strong>
        <small>Telah mengakhiri hubungan kerja</small>
    </article>
</section>

<div class="card filter-card">
    <form action="{{ route('reports.turnover') }}" method="GET" class="filter-form">
        <div class="filter-group search-group">
            <input type="text" name="search" value="{{ $currentSearch }}" placeholder="Cari nama karyawan atau ID..." class="form-control">
        </div>

        <div class="filter-group" style="max-width: 140px;">
            <select name="year" class="form-control">
                <option value="">Semua Tahun</option>
                @for ($y = (int) date('Y') + 1; $y >= 2020; $y--)
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
            <select name="employment_type" class="form-control">
                <option value="">Semua Tipe Kontrak</option>
                <option value="permanent" {{ $currentEmploymentType === 'permanent' ? 'selected' : '' }}>Permanent (Tetap)</option>
                <option value="contract" {{ $currentEmploymentType === 'contract' ? 'selected' : '' }}>Contract (Kontrak)</option>
                <option value="internship" {{ $currentEmploymentType === 'internship' ? 'selected' : '' }}>Magang / Internship</option>
            </select>
        </div>

        <div class="filter-group">
            <select name="status" class="form-control">
                <option value="">Semua Status</option>
                <option value="active" {{ $currentStatus === 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="inactive" {{ $currentStatus === 'inactive' ? 'selected' : '' }}>Non-Aktif</option>
                <option value="resigned" {{ $currentStatus === 'resigned' ? 'selected' : '' }}>Resign</option>
            </select>
        </div>

        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary" style="padding: 9px 14px;">Filter</button>
            @if ($currentSearch || $currentYear || $currentDepartment || $currentEmploymentType || $currentStatus)
                <a href="{{ route('reports.turnover') }}" class="btn btn-outline" style="padding: 9px 12px;">Reset</a>
            @endif
        </div>
    </form>
</div>

<div class="card">
    <div class="table-container">
        <table class="table-report">
            <thead>
                <tr>
                    <th>Karyawan</th>
                    <th>Departemen</th>
                    <th>Jabatan</th>
                    <th>Tipe Hubungan Kerja</th>
                    <th>Tanggal Bergabung</th>
                    <th>Status</th>
                    <th>Tanggal Resign</th>
                    <th>Status Khusus</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employees as $row)
                    @php
                        $badgeClass = match ($row->status) {
                            'active' => 'badge-active',
                            'inactive' => 'badge-inactive',
                            'resigned' => 'badge-resigned',
                            default => 'badge-inactive',
                        };
                        $statusLabel = match ($row->status) {
                            'active' => 'Aktif',
                            'inactive' => 'Non-Aktif',
                            'resigned' => 'Resign',
                            default => ucfirst($row->status),
                        };
                    @endphp
                    <tr>
                        <td>
                            <div class="emp-info">
                                <strong>{{ $row->name }}</strong>
                                <span><code>{{ $row->employee_code }}</code></span>
                            </div>
                        </td>
                        <td>{{ $row->department?->name ?? '-' }}</td>
                        <td>{{ $row->position?->name ?? '-' }}</td>
                        <td>
                            <span style="font-weight: 500;">
                                {{ ucfirst($row->employment_type ?? 'Permanent') }}
                            </span>
                        </td>
                        <td>{{ $row->join_date ? $row->join_date->format('d M Y') : '-' }}</td>
                        <td>
                            <span class="badge-status {{ $badgeClass }}">
                                {{ $statusLabel }}
                            </span>
                        </td>
                        <td>
                            @if ($row->resigned_at)
                                <span style="color: #d93838; font-weight: 600;">
                                    {{ $row->resigned_at->format('d M Y') }}
                                </span>
                            @else
                                <span style="color: var(--muted);">-</span>
                            @endif
                        </td>
                        <td>
                            @if ($row->special_status)
                                <span style="font-size: 12px; color: var(--brand-dark); font-weight: 600;">
                                    {{ $row->special_status }}
                                </span>
                            @else
                                <span style="color: var(--muted);">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="empty-state">
                            <p style="font-size: 15px; margin: 0 0 6px;">Tidak ada data karyawan yang sesuai kriteria.</p>
                            <small>Silakan ubah filter pencarian atau departemen.</small>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($employees->hasPages())
        <div class="pagination-wrapper">
            <span>Menampilkan {{ $employees->firstItem() }} – {{ $employees->lastItem() }} dari {{ $employees->total() }} data</span>
            {{ $employees->links() }}
        </div>
    @endif
</div>
@endsection
