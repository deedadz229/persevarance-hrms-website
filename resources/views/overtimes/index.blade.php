@extends('layouts.app')

@section('title', 'Pengajuan & Monitoring Lembur')

@section('content')
<style>
    .page-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
    .page-title-box h2 { margin: 0; font-size: 22px; font-weight: 700; color: var(--ink); letter-spacing: -.02em; }
    .page-title-box p { margin: 4px 0 0; color: var(--muted); font-size: 13px; }
    .header-actions { display: flex; align-items: center; gap: 10px; }

    .summary-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin-bottom: 20px; }
    .summary-card { padding: 18px 20px; }
    .summary-card span { color: var(--muted); font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
    .summary-card strong { display: block; margin-top: 8px; font-size: 26px; letter-spacing: -.03em; }
    .summary-card small { display: block; margin-top: 4px; color: #81909a; font-size: 11.5px; }

    .payroll-banner { padding: 12px 16px; border-radius: 9px; background: #fdf8e6; border: 1px solid #f6e8b8; color: #7d5f0b; font-size: 13px; margin-bottom: 20px; }

    .filter-card { padding: 18px 20px; margin-bottom: 20px; }
    .filter-form { display: flex; gap: 14px; flex-wrap: wrap; align-items: center; }
    .filter-group { flex: 1; min-width: 160px; }
    .filter-group.search-group { flex: 2; min-width: 220px; }
    .form-control { width: 100%; padding: 9px 12px; border: 1px solid var(--line); border-radius: 8px; font-size: 13px; background: #fafbfc; color: var(--ink); outline: none; }
    .form-control:focus { border-color: var(--brand); background: #fff; box-shadow: 0 0 0 3px rgba(12, 143, 130, .1); }

    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; cursor: pointer; border: 1px solid transparent; transition: all .15s ease; }
    .btn-primary { background: var(--brand); color: #fff; }
    .btn-primary:hover { background: var(--brand-dark); }
    .btn-outline { border-color: var(--line); background: var(--surface); color: var(--ink); }
    .btn-outline:hover { background: #f0f4f5; }
    .btn-sm { padding: 5px 10px; font-size: 12px; border-radius: 6px; }
    .btn-approve { background: #0c8f82; color: #fff; }
    .btn-approve:hover { background: #076d65; }
    .btn-reject { background: #d93838; color: #fff; }
    .btn-reject:hover { background: #b52828; }

    .table-container { overflow-x: auto; }
    .table-overtime { width: 100%; border-collapse: collapse; text-align: left; }
    .table-overtime th { padding: 13px 16px; background: #fbfcfc; color: var(--muted); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid var(--line); white-space: nowrap; }
    .table-overtime td { padding: 14px 16px; border-bottom: 1px solid var(--line); font-size: 13px; vertical-align: middle; }
    .table-overtime tbody tr:hover { background: #fafcfc; }

    .emp-info strong { display: block; font-weight: 600; color: var(--ink); font-size: 13.5px; }
    .emp-info span { display: block; margin-top: 2px; color: var(--muted); font-size: 12px; }

    .badge-status { display: inline-flex; align-items: center; padding: 3px 9px; border-radius: 999px; font-size: 11.5px; font-weight: 600; }
    .badge-pending { background: #fff4dd; color: #9a6a00; }
    .badge-approved { background: #e6f7f2; color: #0a7b6f; }
    .badge-rejected { background: #feebeb; color: #b92e2e; }

    .action-group { display: flex; align-items: center; gap: 6px; justify-content: flex-end; }
    .action-link { padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; border: 1px solid var(--line); color: var(--ink); background: #fff; }
    .action-link:hover { border-color: var(--brand); color: var(--brand-dark); background: var(--brand-soft); }

    .pagination-wrapper { padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: var(--muted); border-top: 1px solid var(--line); }
    .empty-state { padding: 48px 20px; text-align: center; color: var(--muted); }

    /* Modal styles */
    .modal-overlay { display: none; position: fixed; inset: 0; z-index: 1000; background: rgba(16, 40, 48, .5); backdrop-filter: blur(2px); align-items: center; justify-content: center; padding: 20px; }
    .modal-overlay.is-active { display: flex; }
    .modal-card { width: 100%; max-width: 480px; padding: 24px; border-radius: 12px; background: #fff; box-shadow: 0 10px 30px rgba(0, 0, 0, .2); }
    .modal-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
    .modal-head h3 { margin: 0; font-size: 17px; font-weight: 700; color: var(--ink); }
    .modal-close { border: 0; background: transparent; font-size: 20px; cursor: pointer; color: var(--muted); }

    @media (max-width: 1024px) { .summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
</style>

<div class="page-header">
    <div class="page-title-box">
        <h2>Pengajuan & Monitoring Lembur</h2>
        <p>Kelola permohonan kerja lembur, kepatuhan durasi, dan verifikasi persetujuan jam lembur</p>
    </div>
    <div class="header-actions">
        @if (auth()->user()->role?->name !== 'taiwan_account' && auth()->user()->role?->permissions->contains('name', 'manage_overtime'))
            <a href="{{ route('overtimes.create') }}" class="btn btn-primary">
                <span>+</span> Tambah Pengajuan
            </a>
        @endif
    </div>
</div>

<section class="summary-grid" aria-label="Ringkasan Lembur">
    <article class="card summary-card">
        <span>Total Jam Lembur</span>
        <strong style="color: #0c8f82;">{{ number_format($summary['total_hours'], 1) }} Jam</strong>
        <small>Akumulasi lembur yang telah disetujui</small>
    </article>
    <article class="card summary-card">
        <span>Disetujui</span>
        <strong style="color: #0c8f82;">{{ number_format($summary['approved']) }}</strong>
        <small>Lembur telah tervalidasi</small>
    </article>
    <article class="card summary-card">
        <span>Menunggu Persetujuan</span>
        <strong style="color: #c98804;">{{ number_format($summary['pending']) }}</strong>
        <small>Memerlukan tindakan approval HR</small>
    </article>
    <article class="card summary-card">
        <span>Biaya Lembur</span>
        <strong style="font-size: 19px; color: var(--muted); margin-top: 14px;">Modul Payroll</strong>
        <small>Dihitung saat proses penggajian</small>
    </article>
</section>

<div class="payroll-banner">
    ℹ <strong>Integrasi Upah Lembur:</strong> Durasi jam lembur yang disetujui akan diakumulasikan dan dihitung otomatis menjadi komponen upah lembur karyawan pada Modul Payroll PTI.
</div>

<div class="card filter-card">
    <form action="{{ route('overtimes.index') }}" method="GET" class="filter-form">
        <div class="filter-group search-group">
            <input type="text" name="search" value="{{ $currentSearch }}" placeholder="Cari nama karyawan atau ID..." class="form-control">
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
                <option value="pending" {{ $currentStatus === 'pending' ? 'selected' : '' }}>Menunggu Persetujuan</option>
                <option value="approved" {{ $currentStatus === 'approved' ? 'selected' : '' }}>Disetujui</option>
                <option value="rejected" {{ $currentStatus === 'rejected' ? 'selected' : '' }}>Ditolak</option>
            </select>
        </div>
        <div class="filter-group" style="max-width: 160px;">
            <input type="date" name="date" value="{{ $currentDate }}" class="form-control" title="Tanggal Lembur">
        </div>
        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary" style="padding: 9px 14px;">Filter</button>
            @if ($currentSearch || $currentDepartment || $currentStatus || $currentDate)
                <a href="{{ route('overtimes.index') }}" class="btn btn-outline" style="padding: 9px 12px;" title="Reset Filter">Reset</a>
            @endif
        </div>
    </form>
</div>

<div class="card">
    <div class="table-container">
        <table class="table-overtime">
            <thead>
                <tr>
                    <th>Karyawan</th>
                    <th>Departemen</th>
                    <th>Tanggal Lembur</th>
                    <th>Waktu (Mulai – Selesai)</th>
                    <th>Durasi</th>
                    <th>Alasan</th>
                    <th>Status</th>
                    <th style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($overtimes as $overtime)
                    <tr>
                        <td>
                            <div class="emp-info">
                                <strong>{{ $overtime->employee?->name ?? 'Karyawan dihapus' }}</strong>
                                <span><code>{{ $overtime->employee?->employee_code ?? '-' }}</code></span>
                            </div>
                        </td>
                        <td>{{ $overtime->employee?->department?->name ?? '-' }}</td>
                        <td>{{ $overtime->overtime_date?->format('d M Y') ?? '-' }}</td>
                        <td>
                            <strong>
                                {{ is_string($overtime->start_time) ? $overtime->start_time : $overtime->start_time?->format('H:i') }}
                                –
                                {{ is_string($overtime->end_time) ? $overtime->end_time : $overtime->end_time?->format('H:i') }}
                            </strong>
                        </td>
                        <td>
                            <span style="font-weight: 700; color: var(--brand-dark);">
                                {{ $overtime->duration_label }}
                            </span>
                        </td>
                        <td style="max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $overtime->reason }}">
                            {{ $overtime->reason }}
                        </td>
                        <td>
                            <span class="badge-status {{ $overtime->status_badge_class }}">
                                {{ $overtime->status_label }}
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <div class="action-group">
                                <a href="{{ route('overtimes.show', $overtime) }}" class="action-link" title="Detail Lembur">Detail</a>

                                @if ($overtime->status === 'pending' && auth()->user()->role?->name !== 'taiwan_account' && auth()->user()->role?->permissions->contains('name', 'approve_overtime'))
                                    <form action="{{ route('overtimes.approve', $overtime) }}" method="POST" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin menyetujui lembur ini?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-approve" title="Setujui Lembur">✓ Approve</button>
                                    </form>

                                    <button type="button" class="btn btn-sm btn-reject" onclick="openRejectModal('{{ route('overtimes.reject', $overtime) }}', '{{ $overtime->employee?->name }}', '{{ $overtime->duration_label }}')" title="Tolak Lembur">
                                        ✗ Reject
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="empty-state">
                            Belum ada permohonan lembur yang sesuai dengan kriteria filter pencarian.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($overtimes->hasPages())
        <div class="pagination-wrapper">
            <div>Menampilkan {{ $overtimes->firstItem() ?? 0 }} - {{ $overtimes->lastItem() ?? 0 }} dari {{ $overtimes->total() }} data lembur</div>
            <div>{{ $overtimes->links() }}</div>
        </div>
    @endif
</div>

{{-- Reject Reason Modal --}}
<div id="rejectModal" class="modal-overlay" onclick="closeRejectModalOnOverlay(event)">
    <div class="modal-card">
        <div class="modal-head">
            <h3>Tolak Pengajuan Lembur</h3>
            <button type="button" class="modal-close" onclick="closeRejectModal()">&times;</button>
        </div>
        <p style="margin: 0 0 16px; font-size: 13px; color: var(--muted);" id="rejectModalDesc">
            Mohon cantumkan alasan penolakan secara jelas.
        </p>

        <form id="rejectForm" method="POST" action="">
            @csrf
            <div style="margin-bottom: 18px;">
                <label for="rejection_reason" style="display: block; margin-bottom: 6px; font-size: 13px; font-weight: 600;">
                    Alasan Penolakan <span style="color: #d93838;">*</span>
                </label>
                <textarea id="rejection_reason" name="rejection_reason" rows="4" class="form-control" placeholder="Contoh: Pekerjaan di luar instruksi tertulis atau alokasi anggaran lembur telah penuh." required></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn btn-outline" onclick="closeRejectModal()">Batal</button>
                <button type="submit" class="btn btn-reject">Tolak Lembur</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openRejectModal(actionUrl, employeeName, duration) {
        document.getElementById('rejectForm').action = actionUrl;
        document.getElementById('rejectModalDesc').innerText = 'Menolak permohonan lembur (' + duration + ') untuk ' + employeeName + '.';
        document.getElementById('rejection_reason').value = '';
        document.getElementById('rejectModal').classList.add('is-active');
        document.getElementById('rejection_reason').focus();
    }

    function closeRejectModal() {
        document.getElementById('rejectModal').classList.remove('is-active');
    }

    function closeRejectModalOnOverlay(event) {
        if (event.target === document.getElementById('rejectModal')) {
            closeRejectModal();
        }
    }
</script>
@endsection
