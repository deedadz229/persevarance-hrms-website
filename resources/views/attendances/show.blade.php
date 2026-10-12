@extends('layouts.app')

@section('title', __('attendances.detail_title') . ' · ' . ($attendance->employee?->name ?? __('attendances.employee')))

@section('content')
<style>
    .page-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
    .page-title-box h2 { margin: 0; font-size: 22px; font-weight: 700; color: var(--ink); letter-spacing: -.02em; }
    .page-title-box p { margin: 4px 0 0; color: var(--muted); font-size: 13px; }
    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; cursor: pointer; border: 1px solid transparent; transition: all .15s ease; }
    .btn-outline { border-color: var(--line); background: var(--surface); color: var(--ink); }
    .btn-outline:hover { background: #f0f4f5; }

    .grid-layout { display: grid; grid-template-columns: minmax(0, 1.3fr) minmax(320px, .7fr); gap: 24px; }
    .section-card { padding: 24px; margin-bottom: 20px; }
    .section-card h3 { margin: 0 0 16px; font-size: 15px; font-weight: 700; color: var(--ink); border-bottom: 1px solid var(--line); padding-bottom: 10px; }

    .info-list { display: grid; grid-template-columns: 160px 1fr; row-gap: 14px; column-gap: 16px; font-size: 13px; }
    .info-label { color: var(--muted); font-weight: 500; }
    .info-value { color: var(--ink); font-weight: 600; }

    .badge-status { display: inline-flex; align-items: center; padding: 3px 9px; border-radius: 999px; font-size: 12px; font-weight: 600; }
    .badge-hadir { background: #e6f7f2; color: #0a7b6f; }
    .badge-telat { background: #fff4dd; color: #9a6a00; }
    .badge-wfh { background: #e8f0fe; color: #1a73e8; }
    .badge-absen { background: #feebeb; color: #b92e2e; }

    .badge-pill { display: inline-flex; align-items: center; padding: 3px 8px; border-radius: 6px; font-size: 11.5px; font-weight: 600; }
    .badge-verified { background: #e6f7f2; color: #0a7b6f; }
    .badge-unverified { background: #f1f3f5; color: #707e8a; }
    .badge-failed { background: #feebeb; color: #b92e2e; }

    .photo-box { border: 1px solid var(--line); border-radius: 10px; overflow: hidden; background: #fafbfc; text-align: center; padding: 12px; }
    .photo-preview { width: 100%; max-height: 380px; object-fit: cover; border-radius: 8px; }
    .no-photo { padding: 48px 16px; color: var(--muted); font-size: 13px; text-align: center; }

    @media (max-width: 900px) { .grid-layout { grid-template-columns: 1fr; } }
</style>

<div class="page-header">
    <div class="page-title-box">
        <h2>{{ __('attendances.detail_title') }}</h2>
        <p>{{ __('attendances.detail_subtitle') }}</p>
    </div>
    <div>
        <a href="{{ route('attendances.index') }}" class="btn btn-outline">
            <span>←</span> {{ __('attendances.back_to_monitoring') }}
        </a>
    </div>
</div>

<div class="grid-layout">
    <div>
        <div class="card section-card">
            <h3>{{ __('attendances.identity_data') }}</h3>
            <div class="info-list">
                <span class="info-label">{{ __('employees.employee_name') }}</span>
                <span class="info-value">
                    <a href="{{ $attendance->employee ? route('employees.show', $attendance->employee) : '#' }}" style="color: var(--brand); text-decoration: none;">
                        {{ $attendance->employee?->name ?? __('attendances.deleted_employee') }}
                    </a>
                </span>

                <span class="info-label">{{ __('employees.employee_code_label') }}</span>
                <span class="info-value"><code>{{ $attendance->employee?->employee_code ?? '-' }}</code></span>

                <span class="info-label">{{ __('employees.department') }}</span>
                <span class="info-value">{{ $attendance->employee?->department?->name ?? '-' }}</span>

                <span class="info-label">{{ __('employees.position_label') }}</span>
                <span class="info-value">{{ $attendance->employee?->position?->name ?? '-' }}</span>
            </div>
        </div>

        <div class="card section-card">
            <h3>{{ __('attendances.time_and_status') }}</h3>
            <div class="info-list">
                <span class="info-label">{{ __('attendances.date_title') }}</span>
                <span class="info-value">{{ $attendance->attendance_date?->translatedFormat('l, d F Y') ?? '-' }}</span>

                <span class="info-label">{{ __('attendances.check_in') }}</span>
                <span class="info-value" style="font-size: 14px;">
                    <strong>{{ $attendance->check_in?->format('H:i:s') ?? '-' }}</strong>
                </span>

                <span class="info-label">{{ __('attendances.check_out') }}</span>
                <span class="info-value" style="font-size: 14px;">
                    <strong>{{ $attendance->check_out?->format('H:i:s') ?? __('attendances.not_checked_out') }}</strong>
                </span>

                <span class="info-label">{{ __('common.status') }}</span>
                <span class="info-value">
                    @php($st = $attendance->computed_status)
                    @if ($st === 'wfh')
                        <span class="badge-status badge-wfh">Work From Home (WFH)</span>
                    @elseif ($st === 'telat')
                        <span class="badge-status badge-telat">{{ __('attendances.late') }}</span>
                    @else
                        <span class="badge-status badge-hadir">{{ __('attendances.on_time') }}</span>
                    @endif
                </span>

                <span class="info-label">{{ __('attendances.employee_notes') }}</span>
                <span class="info-value" style="font-weight: normal; color: var(--ink);">
                    {{ $attendance->notes ?: __('attendances.no_notes') }}
                </span>
            </div>
        </div>

        <div class="card section-card">
            <h3>{{ __('attendances.validation_results') }}</h3>
            <div class="info-list">
                <span class="info-label">{{ __('attendances.network_verification') }}</span>
                <span class="info-value">
                    @if ($attendance->wifi_verified)
                        <span class="badge-pill badge-verified">{{ __('attendances.wifi_is_verified') }}</span>
                    @else
                        <span class="badge-pill badge-unverified">{{ __('attendances.wifi_not_verified') }}</span>
                    @endif
                </span>

                <span class="info-label">{{ __('attendances.wifi_ssid') }}</span>
                <span class="info-value"><code>{{ $attendance->wifi_ssid ?? '-' }}</code></span>

                <span class="info-label">{{ __('attendances.ip_address') }}</span>
                <span class="info-value"><code>{{ $attendance->ip_address ?? '-' }}</code></span>

                <span class="info-label">{{ __('attendances.face_verification_status') }}</span>
                <span class="info-value">
                    @php($face = strtolower($attendance->face_status ?? 'pending'))
                    @if ($face === 'verified')
                        <span class="badge-pill badge-verified">{{ __('attendances.face_verified') }}</span>
                    @elseif ($face === 'failed')
                        <span class="badge-pill badge-failed">{{ __('attendances.face_failed') }}</span>
                    @else
                        <span class="badge-pill badge-unverified">{{ ucfirst($attendance->face_status ?? 'Pending') }}</span>
                    @endif
                </span>

                <span class="info-label">{{ __('attendances.location_status') }}</span>
                <span class="info-value">
                    @php($loc = strtolower($attendance->location_status ?? 'pending'))
                    @if ($loc === 'verified')
                        <span class="badge-pill badge-verified">{{ __('attendances.location_verified') }}</span>
                    @elseif ($loc === 'failed')
                        <span class="badge-pill badge-failed">{{ __('attendances.location_failed') }}</span>
                    @elseif ($loc === 'wfh')
                        <span class="badge-pill badge-verified">{{ __('attendances.wfh_approved') }}</span>
                    @else
                        <span class="badge-pill badge-unverified">{{ ucfirst($attendance->location_status ?? 'Pending') }}</span>
                    @endif
                </span>

                <span class="info-label">{{ __('attendances.gps_coords') }}</span>
                <span class="info-value">
                    @if ($attendance->latitude && $attendance->longitude)
                        <code>{{ $attendance->latitude }}, {{ $attendance->longitude }}</code>
                        <a href="https://maps.google.com/?q={{ $attendance->latitude }},{{ $attendance->longitude }}" target="_blank" rel="noopener noreferrer" style="margin-left: 8px; font-size: 11.5px; color: var(--brand); text-decoration: none;">
                            {{ __('attendances.open_in_google_maps') }}
                        </a>
                    @else
                        <span style="color: var(--muted); font-weight: normal;">{{ __('attendances.no_coords_recorded') }}</span>
                    @endif
                </span>
            </div>
        </div>
    </div>

    <div>
        <div class="card section-card">
            <h3>{{ __('attendances.photo_evidence') }}</h3>
            <div class="photo-box">
                @if ($attendance->photo_path && file_exists(public_path('storage/' . $attendance->photo_path)))
                    <img src="{{ asset('storage/' . $attendance->photo_path) }}" alt="Bukti Foto Absensi {{ $attendance->employee?->name }}" class="photo-preview">
                @elseif ($attendance->photo_path)
                    <div style="padding: 20px; font-size: 13px;">
                        <span style="font-size: 32px; display: block; margin-bottom: 8px;">📷</span>
                        <strong>{{ __('attendances.photo_recorded') }}</strong>
                        <p style="margin: 4px 0 0; color: var(--muted); font-size: 11.5px; word-break: break-all;">
                            <code>{{ $attendance->photo_path }}</code>
                        </p>
                    </div>
                @else
                    <div class="no-photo">
                        <span style="font-size: 36px; display: block; margin-bottom: 8px;">📭</span>
                        <strong>{{ __('attendances.no_photo_available') }}</strong>
                        <p style="margin: 6px 0 0; color: var(--muted); font-size: 12px;">{{ __('attendances.no_photo_desc') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
