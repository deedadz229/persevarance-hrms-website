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
            <span class="status-badge">{{ $user->role?->label ?? __('dashboard.active_account') }}</span>
            <h2 style="margin-top: 12px;">{{ __('dashboard.greeting', ['name' => $user->name]) }}</h2>
            <p>{{ __('dashboard.subtitle') }}</p>
        </div>
        <p style="margin: 0;">{{ now()->translatedFormat('l, d F Y') }}</p>
    </div>

    @if ($dashboardRole === 'manager_hrd')
        <section class="metric-grid" aria-label="Ringkasan HR">
            <article class="metric-card card"><span>{{ __('dashboard.total_employees') }}</span><strong>{{ number_format($dashboard['total_employees']) }}</strong><small>{{ __('dashboard.employee_data_desc') }}</small></article>
            <article class="metric-card card"><span>{{ __('dashboard.present_today') }}</span><strong>{{ number_format($dashboard['present_today']) }}</strong><small>{{ __('dashboard.check_in_at', ['date' => now()->format('d M Y')]) }}</small></article>
            <article class="metric-card card"><span>{{ __('dashboard.pending_leaves') }}</span><strong>{{ number_format($dashboard['pending_leaves']) }}</strong><small>{{ __('dashboard.waiting_approval') }}</small></article>
            <article class="metric-card card"><span>{{ __('dashboard.total_payroll') }}</span><strong>Rp {{ number_format((float) $dashboard['total_payroll'], 0, ',', '.') }}</strong><small>{{ __('dashboard.period', ['period' => now()->translatedFormat('F Y')]) }}</small></article>
        </section>

        <section class="dashboard-grid">
            <article class="panel card">
                <div class="panel-head"><h3>{{ __('dashboard.recent_activities') }}</h3><span>{{ __('dashboard.last_5_activities') }}</span></div>
                @forelse ($dashboard['recent_activities'] as $activity)
                    <div class="activity-list"><li><span class="activity-mark">A</span><div class="activity-copy"><strong>{{ str($activity->action)->replace('_', ' ')->title() }}</strong><span>{{ $activity->user?->name ?? __('dashboard.system') }} · {{ $activity->created_at?->format('d M Y, H:i') ?? '-' }}</span></div></li></div>
                @empty
                    <p class="empty-state">{{ __('dashboard.no_activities') }}</p>
                @endforelse
            </article>
            <article class="panel card">
                <div class="panel-head"><h3>{{ __('dashboard.leaves_waiting_approval') }}</h3><span>{{ __('dashboard.records_count', ['count' => $dashboard['pending_leaves']]) }}</span></div>
                @forelse ($dashboard['pending_leave_requests'] as $leave)
                    <div class="activity-list"><li><span class="activity-mark">C</span><div class="activity-copy"><strong>{{ $leave->employee?->name ?? __('dashboard.employee_not_found') }}</strong><span>{{ $leave->leave_type }} · {{ $leave->start_date?->format('d M') }}–{{ $leave->end_date?->format('d M Y') }}</span></div></li></div>
                @empty
                    <p class="empty-state">{{ __('dashboard.no_pending_leaves') }}</p>
                @endforelse
            </article>
        </section>

        <section class="panel card" style="margin-top: 18px;">
            <div class="panel-head"><h3>{{ __('dashboard.today_attendance') }}</h3><span>{{ now()->translatedFormat('d F Y') }}</span></div>
            @if ($dashboard['today_attendances']->isNotEmpty())
                <table class="data-table"><thead><tr><th>{{ __('common.employees') ?? 'Karyawan' }}</th><th>{{ __('dashboard.check_in') }}</th><th>{{ __('dashboard.location_status') }}</th></tr></thead><tbody>
                    @foreach ($dashboard['today_attendances'] as $attendance)
                        <tr><td>{{ $attendance->employee?->name ?? __('dashboard.employee_not_found') }}</td><td>{{ $attendance->check_in?->format('H:i') ?? '-' }}</td><td><span class="table-status">{{ $attendance->location_status ?? __('dashboard.recorded') }}</span></td></tr>
                    @endforeach
                </tbody></table>
            @else
                <p class="empty-state">{{ __('dashboard.no_attendance_today') }}</p>
            @endif
        </section>
    @elseif ($dashboardRole === 'super_admin')
        <section class="metric-grid" aria-label="Ringkasan sistem">
            <article class="metric-card card"><span>{{ __('dashboard.total_users') }}</span><strong>{{ number_format($dashboard['total_users']) }}</strong><small>{{ __('dashboard.registered_accounts') }}</small></article>
            <article class="metric-card card"><span>{{ __('dashboard.total_employees') }}</span><strong>{{ number_format($dashboard['total_employees']) }}</strong><small>{{ __('dashboard.saved_employees') }}</small></article>
            <article class="metric-card card"><span>{{ __('dashboard.system_activity') }}</span><strong>{{ number_format($dashboard['total_audit_logs']) }}</strong><small>{{ __('dashboard.available_audit_logs') }}</small></article>
            <article class="metric-card card"><span>{{ __('dashboard.system_status') }}</span><strong style="color: var(--brand-dark);">{{ __('dashboard.status_active') }}</strong><small>{{ __('dashboard.database_ready') }}</small></article>
        </section>
        <section class="panel card" style="max-width: 880px;">
            <div class="panel-head"><h3>{{ __('dashboard.recent_system_activities') }}</h3><span>{{ __('dashboard.audit_user_activities') }}</span></div>
            @forelse ($dashboard['recent_activities'] as $activity)
                <div class="activity-list"><li><span class="activity-mark">A</span><div class="activity-copy"><strong>{{ str($activity->action)->replace('_', ' ')->title() }}</strong><span>{{ $activity->user?->name ?? __('dashboard.system') }} · {{ $activity->created_at?->format('d M Y, H:i') ?? '-' }}</span></div></li></div>
            @empty
                <p class="empty-state">{{ __('dashboard.no_system_activities') }}</p>
            @endforelse
        </section>
    @elseif ($dashboardRole === 'taiwan_account')
        <section class="metric-grid" aria-label="Ringkasan monitoring">
            <article class="metric-card card"><span>{{ __('dashboard.total_employee_taiwan') }}</span><strong>{{ number_format($dashboard['total_employees']) }}</strong><small>{{ __('dashboard.employee_monitoring_desc') }}</small></article>
            <article class="metric-card card"><span>{{ __('dashboard.present_today') }}</span><strong>{{ number_format($dashboard['present_today']) }}</strong><small>{{ __('dashboard.check_in_at', ['date' => now()->format('d M Y')]) }}</small></article>
            <article class="metric-card card"><span>{{ __('dashboard.pending_leaves_taiwan') }}</span><strong>{{ number_format($dashboard['pending_leaves']) }}</strong><small>{{ __('dashboard.leave_summary_desc') }}</small></article>
            <article class="metric-card card"><span>{{ __('dashboard.payroll_summary') }}</span><strong>{{ $dashboard['payroll_summary'] !== null ? 'Rp '.number_format((float) $dashboard['payroll_summary'], 0, ',', '.') : '-' }}</strong><small>{{ __('dashboard.period', ['period' => now()->translatedFormat('F Y')]) }}</small></article>
        </section>
        <p class="read-only-note">{{ __('dashboard.taiwan_readonly_note') }}</p>
    @else
        <section class="card" style="padding: 24px;"><p style="margin: 0; color: var(--muted);">{{ __('dashboard.not_available') }}</p></section>
    @endif
@endsection
