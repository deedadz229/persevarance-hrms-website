@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <style>
        .dashboard-intro { display: flex; align-items: end; justify-content: space-between; gap: 20px; margin-bottom: 24px; }
        .dashboard-intro h2 { margin: 0; font-size: 24px; letter-spacing: -.02em; }
        .dashboard-intro p { margin: 7px 0 0; color: var(--muted); }
        .metric-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin-bottom: 24px; }
        .metric-card { padding: 19px; }
        .metric-card span { color: var(--muted); font-size: 12px; }
        .metric-card strong { display: block; margin-top: 10px; font-size: 25px; letter-spacing: -.03em; }
        .metric-card small { display: block; margin-top: 5px; color: #81909a; font-size: 11px; }
        .dashboard-grid { display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(330px, .8fr); gap: 18px; }
        .panel { overflow: hidden; }
        .panel-head { display: flex; align-items: center; justify-content: space-between; padding: 18px 20px; border-bottom: 1px solid var(--line); }
        .panel-head h3 { margin: 0; font-size: 14px; }
        .panel-head span { color: var(--muted); font-size: 12px; }
        .activity-list { margin: 0; padding: 0; list-style: none; }
        .activity-list li { display: flex; gap: 11px; padding: 14px 20px; border-bottom: 1px solid var(--line); }
        .activity-list li:last-child { border-bottom: 0; }
        .activity-mark { display: grid; width: 28px; height: 28px; flex: 0 0 28px; place-items: center; border-radius: 8px; background: var(--brand-soft); color: var(--brand-dark); font-size: 12px; font-weight: 800; }
        .activity-copy { min-width: 0; }
        .activity-copy strong, .activity-copy span { display: block; }
        .activity-copy strong { font-size: 13px; }
        .activity-copy span { margin-top: 3px; color: var(--muted); font-size: 12px; }
        .empty-state { padding: 34px 20px; color: var(--muted); text-align: center; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { padding: 11px 20px; background: #fbfcfc; color: var(--muted); font-size: 11px; font-weight: 700; text-align: left; text-transform: uppercase; }
        .data-table td { padding: 13px 20px; border-top: 1px solid var(--line); font-size: 13px; }
        .table-status { border-radius: 999px; padding: 3px 8px; background: #fff4dd; color: #9a6a00; font-size: 11px; font-weight: 700; }
        .read-only-note { margin-top: 20px; padding: 13px 15px; border: 1px solid #bce6d9; border-radius: 10px; background: #effbf7; color: #187058; font-size: 13px; }
        @media (max-width: 1180px) { .metric-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .dashboard-grid { grid-template-columns: 1fr; } }
    </style>

    <div class="dashboard-intro">
        <div>
            <span class="status-badge">{{ $user->role?->label ?? 'Akun aktif' }}</span>
            <h2 style="margin-top: 12px;">Selamat datang, {{ $user->name }}</h2>
            <p>Ringkasan informasi HR berdasarkan akses akun Anda.</p>
        </div>
        <p style="margin: 0;">{{ now()->translatedFormat('l, d F Y') }}</p>
    </div>

    @if ($dashboardRole === 'manager_hrd')
        <section class="metric-grid" aria-label="Ringkasan HR">
            <article class="metric-card card"><span>Total Karyawan</span><strong>{{ number_format($dashboard['total_employees']) }}</strong><small>Data karyawan aktif dan historis</small></article>
            <article class="metric-card card"><span>Hadir Hari Ini</span><strong>{{ number_format($dashboard['present_today']) }}</strong><small>Check-in pada {{ now()->format('d M Y') }}</small></article>
            <article class="metric-card card"><span>Pengajuan Cuti</span><strong>{{ number_format($dashboard['pending_leaves']) }}</strong><small>Menunggu persetujuan</small></article>
            <article class="metric-card card"><span>Total Gaji</span><strong>Rp {{ number_format((float) $dashboard['total_payroll'], 0, ',', '.') }}</strong><small>Periode {{ now()->translatedFormat('F Y') }}</small></article>
        </section>

        <section class="dashboard-grid">
            <article class="panel card">
                <div class="panel-head"><h3>Aktivitas Terbaru</h3><span>5 aktivitas terakhir</span></div>
                @forelse ($dashboard['recent_activities'] as $activity)
                    <div class="activity-list"><li><span class="activity-mark">A</span><div class="activity-copy"><strong>{{ str($activity->action)->replace('_', ' ')->title() }}</strong><span>{{ $activity->user?->name ?? 'Sistem' }} · {{ $activity->created_at?->format('d M Y, H:i') ?? '-' }}</span></div></li></div>
                @empty
                    <p class="empty-state">Belum ada aktivitas yang tercatat.</p>
                @endforelse
            </article>
            <article class="panel card">
                <div class="panel-head"><h3>Cuti Menunggu Persetujuan</h3><span>{{ $dashboard['pending_leaves'] }} data</span></div>
                @forelse ($dashboard['pending_leave_requests'] as $leave)
                    <div class="activity-list"><li><span class="activity-mark">C</span><div class="activity-copy"><strong>{{ $leave->employee?->name ?? 'Karyawan tidak ditemukan' }}</strong><span>{{ $leave->leave_type }} · {{ $leave->start_date?->format('d M') }}–{{ $leave->end_date?->format('d M Y') }}</span></div></li></div>
                @empty
                    <p class="empty-state">Tidak ada cuti yang menunggu persetujuan.</p>
                @endforelse
            </article>
        </section>

        <section class="panel card" style="margin-top: 18px;">
            <div class="panel-head"><h3>Kehadiran Hari Ini</h3><span>{{ now()->translatedFormat('d F Y') }}</span></div>
            @if ($dashboard['today_attendances']->isNotEmpty())
                <table class="data-table"><thead><tr><th>Karyawan</th><th>Check-in</th><th>Status Lokasi</th></tr></thead><tbody>
                    @foreach ($dashboard['today_attendances'] as $attendance)
                        <tr><td>{{ $attendance->employee?->name ?? 'Karyawan tidak ditemukan' }}</td><td>{{ $attendance->check_in?->format('H:i') ?? '-' }}</td><td><span class="table-status">{{ $attendance->location_status ?? 'Tercatat' }}</span></td></tr>
                    @endforeach
                </tbody></table>
            @else
                <p class="empty-state">Belum ada kehadiran yang tercatat hari ini.</p>
            @endif
        </section>
    @elseif ($dashboardRole === 'super_admin')
        <section class="metric-grid" aria-label="Ringkasan sistem">
            <article class="metric-card card"><span>Total User</span><strong>{{ number_format($dashboard['total_users']) }}</strong><small>Akun terdaftar di sistem</small></article>
            <article class="metric-card card"><span>Total Karyawan</span><strong>{{ number_format($dashboard['total_employees']) }}</strong><small>Data karyawan tersimpan</small></article>
            <article class="metric-card card"><span>Aktivitas Sistem</span><strong>{{ number_format($dashboard['total_audit_logs']) }}</strong><small>Audit log yang tersedia</small></article>
            <article class="metric-card card"><span>Status Sistem</span><strong style="color: var(--brand-dark);">Aktif</strong><small>Database siap digunakan</small></article>
        </section>
        <section class="panel card" style="max-width: 880px;">
            <div class="panel-head"><h3>Aktivitas Sistem Terbaru</h3><span>Audit & aktivitas pengguna</span></div>
            @forelse ($dashboard['recent_activities'] as $activity)
                <div class="activity-list"><li><span class="activity-mark">A</span><div class="activity-copy"><strong>{{ str($activity->action)->replace('_', ' ')->title() }}</strong><span>{{ $activity->user?->name ?? 'Sistem' }} · {{ $activity->created_at?->format('d M Y, H:i') ?? '-' }}</span></div></li></div>
            @empty
                <p class="empty-state">Belum ada aktivitas sistem yang tercatat.</p>
            @endforelse
        </section>
    @elseif ($dashboardRole === 'taiwan_account')
        <section class="metric-grid" aria-label="Ringkasan monitoring">
            <article class="metric-card card"><span>Total Employee</span><strong>{{ number_format($dashboard['total_employees']) }}</strong><small>Data karyawan untuk monitoring</small></article>
            <article class="metric-card card"><span>Kehadiran Hari Ini</span><strong>{{ number_format($dashboard['present_today']) }}</strong><small>Check-in pada {{ now()->format('d M Y') }}</small></article>
            <article class="metric-card card"><span>Cuti Menunggu Proses</span><strong>{{ number_format($dashboard['pending_leaves']) }}</strong><small>Ringkasan pengajuan cuti</small></article>
            <article class="metric-card card"><span>Ringkasan Payroll</span><strong>{{ $dashboard['payroll_summary'] !== null ? 'Rp '.number_format((float) $dashboard['payroll_summary'], 0, ',', '.') : '-' }}</strong><small>Periode {{ now()->translatedFormat('F Y') }}</small></article>
        </section>
        <p class="read-only-note">Dashboard monitoring ini bersifat read-only. Tidak ada aksi perubahan data tersedia untuk Taiwan Account.</p>
    @else
        <section class="card" style="padding: 24px;"><p style="margin: 0; color: var(--muted);">Ringkasan dashboard belum tersedia untuk role ini.</p></section>
    @endif
@endsection
