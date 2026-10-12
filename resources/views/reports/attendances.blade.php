@extends('layouts.app')

@section('title', 'Laporan Absensi Bulanan')

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
    .badge-present { background: #e6f7f2; color: #0a7b6f; }
    .badge-late { background: #feebeb; color: #b92e2e; }
    .badge-wfh { background: #eaf1f8; color: #1e5a8a; }

    .pagination-wrapper { padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: var(--muted); border-top: 1px solid var(--line); }
    .empty-state { padding: 48px 20px; text-align: center; color: var(--muted); }

    @media (max-width: 1024px) { .summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
</style>

<div class="page-header">
    <div class="page-title-box">
        <h2>Laporan Absensi Bulanan</h2>
        <p>Rekapitulasi log kehadiran, kepatuhan jadwal, dan verifikasi perangkat karyawan PTI</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('reports.index') }}" class="btn btn-outline">
            <span>←</span> Kembali ke Pusat Laporan
        </a>
        @if ($canExport)
            <div style="display: inline-flex; gap: 8px;">
                <a href="{{ route('reports.attendances.export', array_merge(request()->query(), ['format' => 'csv'])) }}" class="btn btn-outline" title="Export format CSV">
                    <span>⤓</span> CSV
                </a>
                <a href="{{ route('reports.attendances.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}" class="btn btn-success" title="Export format Excel (.xlsx)">
                    <span>⤓</span> Excel
                </a>
                <a href="{{ route('reports.attendances.export', array_merge(request()->query(), ['format' => 'pdf'])) }}" class="btn btn-outline" style="border-color: #e11d48; color: #e11d48;" title="Export format PDF">
                    <span>⤓</span> PDF
                </a>
            </div>
        @endif
    </div>
</div>

<section class="summary-grid" aria-label="Ringkasan Laporan Absensi">
    <article class="card summary-card">
        <span>Total Kehadiran</span>
        <strong style="color: var(--ink);">{{ number_format($summary['total']) }}</strong>
        <small>Total rekam presensi terfilter</small>
    </article>
    <article class="card summary-card">
        <span>Hadir Tepat Waktu</span>
        <strong style="color: #0c8f82;">{{ number_format($summary['hadir']) }}</strong>
        <small>Check-in tepat waktu (&le; 08:30)</small>
    </article>
    <article class="card summary-card">
        <span>WFH (Remote)</span>
        <strong style="color: #2b7bb9;">{{ number_format($summary['wfh']) }}</strong>
        <small>Presensi dari lokasi kerja WFH</small>
    </article>
    <article class="card summary-card">
        <span>Terlambat</span>
        <strong style="color: #d93838;">{{ number_format($summary['telat']) }}</strong>
        <small>Check-in melebihi batas waktu toleransi</small>
    </article>
</section>

<div class="card filter-card">
    <form action="{{ route('reports.attendances') }}" method="GET" class="filter-form">
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
                <option value="hadir" {{ $currentStatus === 'hadir' ? 'selected' : '' }}>Hadir</option>
                <option value="wfh" {{ $currentStatus === 'wfh' ? 'selected' : '' }}>WFH</option>
                <option value="telat" {{ $currentStatus === 'telat' ? 'selected' : '' }}>Terlambat</option>
            </select>
        </div>

        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary" style="padding: 9px 14px;">Filter</button>
            @if ($currentSearch || $currentMonth || $currentYear || $currentDepartment || $currentStatus)
                <a href="{{ route('reports.attendances') }}" class="btn btn-outline" style="padding: 9px 12px;">Reset</a>
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
                    <th>Tanggal</th>
                    <th>Jam Masuk</th>
                    <th>Jam Keluar</th>
                    <th>Status Presensi</th>
                    <th>WiFi</th>
                    <th>Alamat IP</th>
                    <th>Lokasi GPS</th>
                    <th>Verifikasi Wajah</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($attendances as $row)
                    @php
                        $isWfh = $row->location_status === 'wfh' || str_contains(strtolower($row->notes ?? ''), 'wfh');
                        $isLate = ($row->check_in && substr($row->check_in, 0, 5) > '08:30') || $row->location_status === 'late' || str_contains(strtolower($row->notes ?? ''), 'telat');
                    @endphp
                    <tr>
                        <td>
                            <div class="emp-info">
                                <strong>{{ $row->employee?->name ?? 'Karyawan Dihapus' }}</strong>
                                <span><code>{{ $row->employee?->employee_code ?? '-' }}</code></span>
                            </div>
                        </td>
                        <td>{{ $row->employee?->department?->name ?? '-' }}</td>
                        <td>{{ $row->attendance_date ? date('d M Y', strtotime($row->attendance_date)) : '-' }}</td>
                        <td><strong>{{ $row->check_in ? substr($row->check_in, 0, 5) : '-' }}</strong></td>
                        <td><strong>{{ $row->check_out ? substr($row->check_out, 0, 5) : '-' }}</strong></td>
                        <td>
                            @if ($isWfh)
                                <span class="badge-status badge-wfh">WFH</span>
                            @elseif ($isLate)
                                <span class="badge-status badge-late">Terlambat</span>
                            @else
                                <span class="badge-status badge-present">Hadir</span>
                            @endif
                        </td>
                        <td>
                            @if ($row->wifi_verified)
                                <span style="color: #0c8f82; font-weight: 600;">✓ Terverifikasi</span>
                            @else
                                <span style="color: var(--muted);">-</span>
                            @endif
                        </td>
                        <td><code>{{ $row->ip_address ?? '-' }}</code></td>
                        <td>
                            <span style="font-size: 12px; color: {{ $row->location_status === 'invalid' ? '#d93838' : 'var(--ink)' }};">
                                {{ ucfirst($row->location_status ?? 'valid') }}
                            </span>
                        </td>
                        <td>
                            @if ($row->face_verified)
                                <span style="color: #0c8f82; font-weight: 600;">✓ Cocok</span>
                            @else
                                <span style="color: var(--muted);">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="empty-state">
                            <p style="font-size: 15px; margin: 0 0 6px;">Tidak ada data presensi yang sesuai kriteria filter.</p>
                            <small>Silakan ubah filter periode tanggal atau departemen.</small>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($attendances->hasPages())
        <div class="pagination-wrapper">
            <span>Menampilkan {{ $attendances->firstItem() }} – {{ $attendances->lastItem() }} dari {{ $attendances->total() }} data</span>
            {{ $attendances->links() }}
        </div>
    @endif
</div>
@endsection
