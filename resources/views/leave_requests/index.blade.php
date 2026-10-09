@extends('layouts.app')

@section('title', 'Pengajuan & Persetujuan Cuti')

@section('content')
<style>
    .page-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
    .page-title-box h2 { margin: 0; font-size: 22px; font-weight: 700; color: var(--ink); letter-spacing: -.02em; }
    .page-title-box p { margin: 4px 0 0; color: var(--muted); font-size: 13px; }

    .summary-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin-bottom: 20px; }
    .summary-card { padding: 18px 20px; }
    .summary-card span { color: var(--muted); font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
    .summary-card strong { display: block; margin-top: 8px; font-size: 26px; letter-spacing: -.03em; }
    .summary-card small { display: block; margin-top: 4px; color: #81909a; font-size: 11.5px; }

    .quota-banner { padding: 12px 16px; border-radius: 9px; background: #fdf8e6; border: 1px solid #f6e8b8; color: #7d5f0b; font-size: 13px; margin-bottom: 20px; }

    .filter-card { padding: 18px 20px; margin-bottom: 20px; }
    .filter-form { display: flex; gap: 14px; flex-wrap: wrap; align-items: center; }
    .filter-group { flex: 1; min-width: 150px; }
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
    .table-leave { width: 100%; border-collapse: collapse; text-align: left; }
    .table-leave th { padding: 13px 16px; background: #fbfcfc; color: var(--muted); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid var(--line); white-space: nowrap; }
    .table-leave td { padding: 14px 16px; border-bottom: 1px solid var(--line); font-size: 13px; vertical-align: middle; }
    .table-leave tbody tr:hover { background: #fafcfc; }

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
        <h2>Pengajuan & Persetujuan Cuti</h2>
        <p>Kelola permohonan cuti tahunan, izin sakit, dan cuti khusus karyawan PTI</p>
    </div>
</div>

<section class="summary-grid" aria-label="Ringkasan Pengajuan Cuti">
    <article class="card summary-card">
        <span>Total Pengajuan</span>
        <strong>{{ number_format($summary['total']) }}</strong>
        <small>Seluruh riwayat pengajuan</small>
    </article>
    <article class="card summary-card">
        <span>Menunggu Persetujuan</span>
        <strong style="color: #c98804;">{{ number_format($summary['pending']) }}</strong>
        <small>Memerlukan tindakan approval HR</small>
    </article>
    <article class="card summary-card">
        <span>Disetujui</span>
        <strong style="color: #0c8f82;">{{ number_format($summary['approved']) }}</strong>
        <small>Pengajuan cuti yang disetujui</small>
    </article>
    <article class="card summary-card">
        <span>Ditolak</span>
        <strong style="color: #d93838;">{{ number_format($summary['rejected']) }}</strong>
        <small>Pengajuan tidak memenuhi syarat</small>
    </article>
</section>

<div class="quota-banner">
    ℹ <strong>Catatan Kebijakan Kuota Cuti:</strong> Sistem saat ini merekam seluruh durasi permohonan hari cuti yang disetujui secara presisi. Pengaturan batasan baku kuota tahunan per karyawan akan dikonfigurasi setelah pedoman resmi HRD PTI disahkan.
</div>

<div class="card filter-card">
    <form action="{{ route('leave-requests.index') }}" method="GET" class="filter-form">
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
            <select name="leave_type" class="form-control">
                <option value="">Semua Jenis Cuti</option>
                @foreach ($leaveTypes as $type)
                    <option value="{{ $type }}" {{ $currentLeaveType === $type ? 'selected' : '' }}>
                        {{ $type }}
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
            <input type="date" name="date" value="{{ $currentDate }}" class="form-control" title="Tanggal Cuti">
        </div>
        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary" style="padding: 9px 14px;">Filter</button>
            @if ($currentSearch || $currentDepartment || $currentLeaveType || $currentStatus || $currentDate)
                <a href="{{ route('leave-requests.index') }}" class="btn btn-outline" style="padding: 9px 12px;" title="Reset Filter">Reset</a>
            @endif
        </div>
    </form>
</div>

<div class="card">
    <div class="table-container">
        <table class="table-leave">
            <thead>
                <tr>
                    <th>Karyawan</th>
                    <th>Departemen</th>
                    <th>Jenis Cuti / Izin</th>
                    <th>Periode Tanggal</th>
                    <th>Durasi</th>
                    <th>Status</th>
                    <th style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($leaveRequests as $leave)
                    <tr>
                        <td>
                            <div class="emp-info">
                                <strong>{{ $leave->employee?->name ?? 'Karyawan dihapus' }}</strong>
                                <span><code>{{ $leave->employee?->employee_code ?? '-' }}</code></span>
                            </div>
                        </td>
                        <td>{{ $leave->employee?->department?->name ?? '-' }}</td>
                        <td>
                            <strong>{{ $leave->leave_type }}</strong>
                        </td>
                        <td>
                            {{ $leave->start_date?->format('d M Y') }} – {{ $leave->end_date?->format('d M Y') }}
                        </td>
                        <td>
                            <span style="font-weight: 600;">{{ $leave->duration_label }}</span>
                        </td>
                        <td>
                            <span class="badge-status {{ $leave->status_badge_class }}">
                                {{ $leave->status_label }}
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <div class="action-group">
                                <a href="{{ route('leave-requests.show', $leave) }}" class="action-link" title="Detail Pengajuan">Detail</a>

                                @if ($leave->status === 'pending' && auth()->user()->role?->name !== 'taiwan_account' && auth()->user()->role?->permissions->contains('name', 'approve_leave'))
                                    <form action="{{ route('leave-requests.approve', $leave) }}" method="POST" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin menyetujui pengajuan cuti ini?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-approve" title="Setujui Cuti">✓ Approve</button>
                                    </form>

                                    <button type="button" class="btn btn-sm btn-reject" onclick="openRejectModal('{{ route('leave-requests.reject', $leave) }}', '{{ $leave->employee?->name }}', '{{ $leave->leave_type }}')" title="Tolak Cuti">
                                        ✗ Reject
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty-state">
                            Belum ada permohonan cuti atau izin yang sesuai dengan filter pencarian.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($leaveRequests->hasPages())
        <div class="pagination-wrapper">
            <div>Menampilkan {{ $leaveRequests->firstItem() ?? 0 }} - {{ $leaveRequests->lastItem() ?? 0 }} dari {{ $leaveRequests->total() }} pengajuan</div>
            <div>{{ $leaveRequests->links() }}</div>
        </div>
    @endif
</div>

{{-- Reject Reason Modal --}}
<div id="rejectModal" class="modal-overlay" onclick="closeRejectModalOnOverlay(event)">
    <div class="modal-card">
        <div class="modal-head">
            <h3>Tolak Pengajuan Cuti</h3>
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
                <textarea id="rejection_reason" name="rejection_reason" rows="4" class="form-control" placeholder="Contoh: Jadwal bertabrakan dengan deployment sistem atau kuota shift telah terisi penuh." required></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn btn-outline" onclick="closeRejectModal()">Batal</button>
                <button type="submit" class="btn btn-reject">Tolak Pengajuan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openRejectModal(actionUrl, employeeName, leaveType) {
        document.getElementById('rejectForm').action = actionUrl;
        document.getElementById('rejectModalDesc').innerText = 'Menolak pengajuan ' + leaveType + ' untuk ' + employeeName + '.';
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
