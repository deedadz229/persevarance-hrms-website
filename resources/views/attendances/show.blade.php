@extends('layouts.app')

@section('title', 'Detail Absensi · ' . ($attendance->employee?->name ?? 'Karyawan'))

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
        <h2>Detail Absensi Karyawan</h2>
        <p>Verifikasi rekaman check-in, waktu, biometrik, lokasi GPS, dan jaringan kantor</p>
    </div>
    <div>
        <a href="{{ route('attendances.index') }}" class="btn btn-outline">
            <span>←</span> Kembali ke Monitoring
        </a>
    </div>
</div>

<div class="grid-layout">
    <div>
        <div class="card section-card">
            <h3>Data Identitas Karyawan</h3>
            <div class="info-list">
                <span class="info-label">Nama Karyawan</span>
                <span class="info-value">
                    <a href="{{ $attendance->employee ? route('employees.show', $attendance->employee) : '#' }}" style="color: var(--brand); text-decoration: none;">
                        {{ $attendance->employee?->name ?? 'Karyawan Dihapus' }}
                    </a>
                </span>

                <span class="info-label">ID / Kode Karyawan</span>
                <span class="info-value"><code>{{ $attendance->employee?->employee_code ?? '-' }}</code></span>

                <span class="info-label">Departemen</span>
                <span class="info-value">{{ $attendance->employee?->department?->name ?? '-' }}</span>

                <span class="info-label">Jabatan (Posisi)</span>
                <span class="info-value">{{ $attendance->employee?->position?->name ?? '-' }}</span>
            </div>
        </div>

        <div class="card section-card">
            <h3>Waktu & Status Kehadiran</h3>
            <div class="info-list">
                <span class="info-label">Tanggal Absensi</span>
                <span class="info-value">{{ $attendance->attendance_date?->translatedFormat('l, d F Y') ?? '-' }}</span>

                <span class="info-label">Jam Masuk (Check-In)</span>
                <span class="info-value" style="font-size: 14px;">
                    <strong>{{ $attendance->check_in?->format('H:i:s') ?? '-' }}</strong>
                </span>

                <span class="info-label">Jam Keluar (Check-Out)</span>
                <span class="info-value" style="font-size: 14px;">
                    <strong>{{ $attendance->check_out?->format('H:i:s') ?? 'Belum check-out' }}</strong>
                </span>

                <span class="info-label">Status Kehadiran</span>
                <span class="info-value">
                    @php($st = $attendance->computed_status)
                    @if ($st === 'wfh')
                        <span class="badge-status badge-wfh">Work From Home (WFH)</span>
                    @elseif ($st === 'telat')
                        <span class="badge-status badge-telat">Terlambat</span>
                    @else
                        <span class="badge-status badge-hadir">Hadir Tepat Waktu</span>
                    @endif
                </span>

                <span class="info-label">Catatan Karyawan</span>
                <span class="info-value" style="font-weight: normal; color: var(--ink);">
                    {{ $attendance->notes ?: 'Tidak ada catatan tambahan.' }}
                </span>
            </div>
        </div>

        <div class="card section-card">
            <h3>Hasil Validasi Perangkat & Jaringan</h3>
            <div class="info-list">
                <span class="info-label">Verifikasi Jaringan WiFi</span>
                <span class="info-value">
                    @if ($attendance->wifi_verified)
                        <span class="badge-pill badge-verified">✓ WiFi Terverifikasi</span>
                    @else
                        <span class="badge-pill badge-unverified">WiFi Tidak Terverifikasi</span>
                    @endif
                </span>

                <span class="info-label">SSID WiFi</span>
                <span class="info-value"><code>{{ $attendance->wifi_ssid ?? '-' }}</code></span>

                <span class="info-label">IP Address</span>
                <span class="info-value"><code>{{ $attendance->ip_address ?? '-' }}</code></span>

                <span class="info-label">Status Verifikasi Wajah</span>
                <span class="info-value">
                    @php($face = strtolower($attendance->face_status ?? 'pending'))
                    @if ($face === 'verified')
                        <span class="badge-pill badge-verified">✓ Face Verified</span>
                    @elseif ($face === 'failed')
                        <span class="badge-pill badge-failed">✗ Face Failed</span>
                    @else
                        <span class="badge-pill badge-unverified">{{ ucfirst($attendance->face_status ?? 'Pending') }}</span>
                    @endif
                </span>

                <span class="info-label">Status Lokasi (Geofencing)</span>
                <span class="info-value">
                    @php($loc = strtolower($attendance->location_status ?? 'pending'))
                    @if ($loc === 'verified')
                        <span class="badge-pill badge-verified">✓ Location Verified</span>
                    @elseif ($loc === 'failed')
                        <span class="badge-pill badge-failed">✗ Location Failed</span>
                    @elseif ($loc === 'wfh')
                        <span class="badge-pill badge-verified">✓ WFH Approved</span>
                    @else
                        <span class="badge-pill badge-unverified">{{ ucfirst($attendance->location_status ?? 'Pending') }}</span>
                    @endif
                </span>

                <span class="info-label">Koordinat GPS</span>
                <span class="info-value">
                    @if ($attendance->latitude && $attendance->longitude)
                        <code>{{ $attendance->latitude }}, {{ $attendance->longitude }}</code>
                        <a href="https://maps.google.com/?q={{ $attendance->latitude }},{{ $attendance->longitude }}" target="_blank" rel="noopener noreferrer" style="margin-left: 8px; font-size: 11.5px; color: var(--brand); text-decoration: none;">
                            Buka di Google Maps ↗
                        </a>
                    @else
                        <span style="color: var(--muted); font-weight: normal;">Tidak ada koordinat yang terekam</span>
                    @endif
                </span>
            </div>
        </div>
    </div>

    <div>
        <div class="card section-card">
            <h3>Bukti Foto Absensi</h3>
            <div class="photo-box">
                @if ($attendance->photo_path && file_exists(public_path('storage/' . $attendance->photo_path)))
                    <img src="{{ asset('storage/' . $attendance->photo_path) }}" alt="Bukti Foto Absensi {{ $attendance->employee?->name }}" class="photo-preview">
                @elseif ($attendance->photo_path)
                    <div style="padding: 20px; font-size: 13px;">
                        <span style="font-size: 32px; display: block; margin-bottom: 8px;">📷</span>
                        <strong>Foto Tercatat</strong>
                        <p style="margin: 4px 0 0; color: var(--muted); font-size: 11.5px; word-break: break-all;">
                            <code>{{ $attendance->photo_path }}</code>
                        </p>
                    </div>
                @else
                    <div class="no-photo">
                        <span style="font-size: 36px; display: block; margin-bottom: 8px;">📭</span>
                        <strong>Bukti foto tidak tersedia.</strong>
                        <p style="margin: 6px 0 0; color: var(--muted); font-size: 12px;">Absensi ini dicatat tanpa unggahan foto verifikasi.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
