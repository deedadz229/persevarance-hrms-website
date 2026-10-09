@extends('layouts.app')

@section('title', 'Detail Pengajuan Cuti · ' . ($leaveRequest->employee?->name ?? 'Karyawan'))

@section('content')
<style>
    .page-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
    .page-title-box h2 { margin: 0; font-size: 22px; font-weight: 700; color: var(--ink); letter-spacing: -.02em; }
    .page-title-box p { margin: 4px 0 0; color: var(--muted); font-size: 13px; }
    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; cursor: pointer; border: 1px solid transparent; transition: all .15s ease; }
    .btn-outline { border-color: var(--line); background: var(--surface); color: var(--ink); }
    .btn-outline:hover { background: #f0f4f5; }
    .btn-approve { background: #0c8f82; color: #fff; }
    .btn-approve:hover { background: #076d65; }
    .btn-reject { background: #d93838; color: #fff; }
    .btn-reject:hover { background: #b52828; }

    .grid-details { display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(320px, .8fr); gap: 20px; }
    .section-card { padding: 24px; margin-bottom: 20px; }
    .section-card h3 { margin: 0 0 16px; font-size: 15px; font-weight: 700; color: var(--ink); border-bottom: 1px solid var(--line); padding-bottom: 10px; }

    .info-list { display: grid; grid-template-columns: 150px 1fr; row-gap: 14px; column-gap: 16px; font-size: 13px; }
    .info-label { color: var(--muted); font-weight: 500; }
    .info-value { color: var(--ink); font-weight: 600; }

    .badge-status { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; }
    .badge-pending { background: #fff4dd; color: #9a6a00; }
    .badge-approved { background: #e6f7f2; color: #0a7b6f; }
    .badge-rejected { background: #feebeb; color: #b92e2e; }

    .action-panel { padding: 20px; border-radius: 10px; background: #fafbfc; border: 1px solid var(--line); margin-top: 16px; }

    /* Modal styles */
    .modal-overlay { display: none; position: fixed; inset: 0; z-index: 1000; background: rgba(16, 40, 48, .5); backdrop-filter: blur(2px); align-items: center; justify-content: center; padding: 20px; }
    .modal-overlay.is-active { display: flex; }
    .modal-card { width: 100%; max-width: 480px; padding: 24px; border-radius: 12px; background: #fff; box-shadow: 0 10px 30px rgba(0, 0, 0, .2); }
    .modal-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
    .modal-head h3 { margin: 0; font-size: 17px; font-weight: 700; color: var(--ink); }
    .modal-close { border: 0; background: transparent; font-size: 20px; cursor: pointer; color: var(--muted); }
    .form-control { width: 100%; padding: 10px 12px; border: 1px solid var(--line); border-radius: 8px; font-size: 13px; background: #fafbfc; color: var(--ink); outline: none; }
    .form-control:focus { border-color: var(--brand); background: #fff; }

    @media (max-width: 900px) { .grid-details { grid-template-columns: 1fr; } }
</style>

<div class="page-header">
    <div class="page-title-box">
        <h2>Detail Pengajuan Cuti</h2>
        <p>Informasi permohonan, alasan, dan riwayat verifikasi persetujuan</p>
    </div>
    <div>
        <a href="{{ route('leave-requests.index') }}" class="btn btn-outline">
            <span>←</span> Kembali ke Daftar
        </a>
    </div>
</div>

<div class="grid-details">
    <div>
        <div class="card section-card">
            <h3>Informasi Karyawan Pemohon</h3>
            <div class="info-list">
                <span class="info-label">Nama Karyawan</span>
                <span class="info-value">
                    <a href="{{ $leaveRequest->employee ? route('employees.show', $leaveRequest->employee) : '#' }}" style="color: var(--brand); text-decoration: none;">
                        {{ $leaveRequest->employee?->name ?? 'Karyawan Dihapus' }}
                    </a>
                </span>

                <span class="info-label">ID / Kode Karyawan</span>
                <span class="info-value"><code>{{ $leaveRequest->employee?->employee_code ?? '-' }}</code></span>

                <span class="info-label">Departemen</span>
                <span class="info-value">{{ $leaveRequest->employee?->department?->name ?? '-' }}</span>

                <span class="info-label">Jabatan (Posisi)</span>
                <span class="info-value">{{ $leaveRequest->employee?->position?->name ?? '-' }}</span>
            </div>
        </div>

        <div class="card section-card">
            <h3>Rincian Pengajuan Cuti / Izin</h3>
            <div class="info-list">
                <span class="info-label">Jenis Cuti</span>
                <span class="info-value" style="font-size: 14px; color: var(--brand-dark);">
                    {{ $leaveRequest->leave_type }}
                </span>

                <span class="info-label">Tanggal Mulai</span>
                <span class="info-value">{{ $leaveRequest->start_date?->translatedFormat('l, d F Y') }}</span>

                <span class="info-label">Tanggal Selesai</span>
                <span class="info-value">{{ $leaveRequest->end_date?->translatedFormat('l, d F Y') }}</span>

                <span class="info-label">Durasi Cuti</span>
                <span class="info-value">{{ $leaveRequest->duration_label }}</span>

                <span class="info-label">Alasan Pengajuan</span>
                <span class="info-value" style="font-weight: normal; color: var(--ink); line-height: 1.5;">
                    {{ $leaveRequest->reason }}
                </span>

                <span class="info-label">Tanggal Diajukan</span>
                <span class="info-value" style="font-weight: normal; color: var(--muted);">
                    {{ $leaveRequest->created_at?->translatedFormat('d F Y, H:i') ?? '-' }} WIB
                </span>
            </div>
        </div>
    </div>

    <div>
        <div class="card section-card">
            <h3>Status & Riwayat Pemrosesan</h3>
            <div class="info-list">
                <span class="info-label">Status Saat Ini</span>
                <span class="info-value">
                    <span class="badge-status {{ $leaveRequest->status_badge_class }}">
                        {{ $leaveRequest->status_label }}
                    </span>
                </span>

                @if ($leaveRequest->status === 'approved')
                    <span class="info-label">Disetujui Oleh</span>
                    <span class="info-value">{{ $leaveRequest->approver?->name ?? 'Sistem / HR' }}</span>

                    <span class="info-label">Waktu Disetujui</span>
                    <span class="info-value">{{ $leaveRequest->approved_at?->translatedFormat('d F Y, H:i') ?? '-' }} WIB</span>
                @elseif ($leaveRequest->status === 'rejected')
                    <span class="info-label">Ditolak Oleh</span>
                    <span class="info-value">{{ $leaveRequest->approver?->name ?? 'Sistem / HR' }}</span>

                    <span class="info-label">Waktu Ditolak</span>
                    <span class="info-value">{{ $leaveRequest->approved_at?->translatedFormat('d F Y, H:i') ?? '-' }} WIB</span>

                    <span class="info-label" style="color: #b92e2e;">Alasan Penolakan</span>
                    <span class="info-value" style="color: #b92e2e; font-weight: normal; line-height: 1.45;">
                        {{ $leaveRequest->rejection_reason }}
                    </span>
                @else
                    <span class="info-label">Keterangan</span>
                    <span class="info-value" style="color: #9a6a00; font-weight: normal;">
                        Menunggu tinjauan dan keputusan dari pihak HR / Management.
                    </span>
                @endif
            </div>

            @if ($leaveRequest->status === 'pending' && auth()->user()->role?->name !== 'taiwan_account' && auth()->user()->role?->permissions->contains('name', 'approve_leave'))
                <div class="action-panel">
                    <p style="margin: 0 0 12px; font-size: 13px; font-weight: 600; color: var(--ink);">
                        Tindakan Persetujuan:
                    </p>
                    <div style="display: flex; gap: 10px;">
                        <form action="{{ route('leave-requests.approve', $leaveRequest) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menyetujui pengajuan cuti ini?');" style="flex: 1;">
                            @csrf
                            <button type="submit" class="btn btn-approve" style="width: 100%; justify-content: center;">
                                ✓ Setujui Pengajuan
                            </button>
                        </form>

                        <button type="button" class="btn btn-reject" style="flex: 1; justify-content: center;" onclick="openRejectModal()">
                            ✗ Tolak Pengajuan
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Reject Reason Modal --}}
@if ($leaveRequest->status === 'pending')
<div id="rejectModal" class="modal-overlay" onclick="closeRejectModalOnOverlay(event)">
    <div class="modal-card">
        <div class="modal-head">
            <h3>Tolak Pengajuan Cuti</h3>
            <button type="button" class="modal-close" onclick="closeRejectModal()">&times;</button>
        </div>
        <p style="margin: 0 0 16px; font-size: 13px; color: var(--muted);">
            Menolak permohonan <strong>{{ $leaveRequest->leave_type }}</strong> untuk <strong>{{ $leaveRequest->employee?->name }}</strong>.
        </p>

        <form method="POST" action="{{ route('leave-requests.reject', $leaveRequest) }}">
            @csrf
            <div style="margin-bottom: 18px;">
                <label for="rejection_reason" style="display: block; margin-bottom: 6px; font-size: 13px; font-weight: 600;">
                    Alasan Penolakan <span style="color: #d93838;">*</span>
                </label>
                <textarea id="rejection_reason" name="rejection_reason" rows="4" class="form-control" placeholder="Tuliskan alasan penolakan secara jelas..." required></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn btn-outline" onclick="closeRejectModal()">Batal</button>
                <button type="submit" class="btn btn-reject">Tolak Pengajuan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openRejectModal() {
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
@endif
@endsection
