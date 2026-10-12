@extends('layouts.app')

@section('title', 'Detail File · ' . $sharedFile->name)

@section('content')
<style>
    .page-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
    .page-title-box h2 { margin: 0; font-size: 22px; font-weight: 700; color: var(--ink); letter-spacing: -.02em; }
    .page-title-box p { margin: 4px 0 0; color: var(--muted); font-size: 13px; }
    .header-actions { display: flex; align-items: center; gap: 10px; }

    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; border-radius: var(--radius-sm, 6px); font-size: 13px; font-weight: 600; text-decoration: none; cursor: pointer; border: 1px solid transparent; transition: all .15s ease; line-height: 1.2; }
    .btn-primary { background: var(--brand); color: #fff; }
    .btn-primary:hover { background: var(--brand-dark); }
    .btn-outline { border-color: var(--line); background: var(--surface); color: var(--ink); }
    .btn-outline:hover { background: var(--surface-subtle); border-color: var(--line-strong); }
    .btn-danger { background: #fee2e2; color: #b91c1c; border-color: #fecaca; }
    .btn-danger:hover { background: #fecaca; }
    .btn-sm { padding: 6px 10px; font-size: 12px; }

    .detail-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start; }
    @media (max-width: 900px) { .detail-grid { grid-template-columns: 1fr; } }

    .detail-card { background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius-md, 8px); padding: 24px; box-shadow: var(--shadow-xs); margin-bottom: 24px; }
    .card-title { font-size: 15px; font-weight: 700; color: var(--ink); margin: 0 0 16px; border-bottom: 1px solid var(--line); padding-bottom: 10px; }

    .info-list { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }
    .info-item { display: flex; flex-direction: column; }
    .info-label { font-size: 11.5px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 4px; }
    .info-value { font-size: 13.5px; font-weight: 500; color: var(--ink); }

    .badge { display: inline-flex; align-items: center; padding: 3px 8px; border-radius: 999px; font-size: 11.5px; font-weight: 600; }
    .badge-synced { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .badge-pending { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }

    .recipients-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    .recipients-table th { padding: 10px 14px; background: #fbfcfc; color: var(--muted); font-size: 11px; font-weight: 700; text-transform: uppercase; border-bottom: 1px solid var(--line); text-align: left; }
    .recipients-table td { padding: 12px 14px; border-bottom: 1px solid var(--line); font-size: 13px; vertical-align: middle; }

    .form-control { width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: var(--radius-sm, 6px); font-size: 13px; background: #fafbfc; color: var(--ink); outline: none; margin-bottom: 14px; }
    .form-control:focus { border-color: var(--brand); background: #fff; box-shadow: 0 0 0 3px rgba(185, 28, 28, .1); }
</style>

<div class="page-header">
    <div class="page-title-box">
        <h2>Detail Dokumen</h2>
        <p>Informasi berkas, riwayat sinkronisasi, dan status akses pembagian</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('shared-files.index') }}" class="btn btn-outline">
            &larr; Kembali ke Daftar
        </a>
        <a href="{{ route('shared-files.download', $sharedFile) }}" class="btn btn-primary">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Unduh Berkas
        </a>
    </div>
</div>

<div class="detail-grid">
    <div>
        {{-- File Information --}}
        <div class="detail-card">
            <h3 class="card-title">Informasi File</h3>
            <div class="info-list">
                <div class="info-item">
                    <span class="info-label">Nama File</span>
                    <span class="info-value" style="font-weight: 700;">{{ $sharedFile->name }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Kategori</span>
                    <span class="info-value">{{ $sharedFile->category ?? 'Umum' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Ukuran Dokumen</span>
                    <span class="info-value tabular-nums">{{ $sharedFile->size ? number_format($sharedFile->size / 1024, 1) . ' KB' : '-' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Tipe MIME</span>
                    <span class="info-value">{{ $sharedFile->mime_type ?? '-' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Pemilik / Pengunggah</span>
                    <span class="info-value">{{ $sharedFile->owner_name ?? $sharedFile->uploader?->name ?? 'Admin' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Status Sinkronisasi</span>
                    <span class="info-value">
                        @if ($sharedFile->sync_status === 'synced')
                            <span class="badge badge-synced">Tersinkron</span>
                        @else
                            <span class="badge badge-pending">Pending</span>
                        @endif
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Waktu Unggah</span>
                    <span class="info-value tabular-nums">{{ $sharedFile->created_at->format('d M Y, H:i:s') }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Terakhir Diperbarui</span>
                    <span class="info-value tabular-nums">{{ $sharedFile->updated_at->format('d M Y, H:i:s') }}</span>
                </div>
            </div>

            @if ($sharedFile->description)
                <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--line);">
                    <span class="info-label">Deskripsi / Catatan</span>
                    <p style="margin: 6px 0 0; color: var(--ink-secondary); font-size: 13px; line-height: 1.6; white-space: pre-line;">{{ $sharedFile->description }}</p>
                </div>
            @endif
        </div>

        @if (! $isTaiwanAccount)
            {{-- Recipients Management --}}
            <div class="detail-card">
                <h3 class="card-title">Penerima Taiwan Account</h3>
                @if ($sharedFile->recipients->isNotEmpty())
                    <table class="recipients-table">
                        <thead>
                            <tr>
                                <th>Nama Akun</th>
                                <th>Email</th>
                                <th>Waktu Dibagikan</th>
                                <th style="text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sharedFile->recipients as $recipient)
                                <tr>
                                    <td><strong>{{ $recipient->name }}</strong></td>
                                    <td style="color: var(--muted);">{{ $recipient->email }}</td>
                                    <td class="tabular-nums" style="color: var(--muted); font-size: 12px;">
                                        {{ $recipient->pivot->shared_at ? \Carbon\Carbon::parse($recipient->pivot->shared_at)->format('d M Y, H:i') : '-' }}
                                    </td>
                                    <td style="text-align: right;">
                                        <form action="{{ route('shared-files.revoke', $sharedFile) }}" method="POST" onsubmit="return confirm('Cabut akses untuk akun {{ $recipient->name }}?');" style="display: inline;">
                                            @csrf
                                            <input type="hidden" name="user_id" value="{{ $recipient->id }}">
                                            <button type="submit" class="btn btn-danger btn-sm">Cabut Akses</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p style="color: var(--muted); font-size: 13px; font-style: italic; margin: 0 0 16px;">
                        File ini belum dibagikan kepada akun Taiwan manapun.
                    </p>
                @endif

                {{-- Quick Add Recipient Form --}}
                <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--line);">
                    <form action="{{ route('shared-files.share', $sharedFile) }}" method="POST">
                        @csrf
                        <label style="display: block; font-size: 12.5px; font-weight: 600; margin-bottom: 8px;">Pilih Akun Taiwan untuk Dibagikan:</label>
                        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                            @foreach ($taiwanUsers as $tUser)
                                <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 13px; margin-right: 12px; cursor: pointer;">
                                    <input type="checkbox" name="recipient_ids[]" value="{{ $tUser->id }}" 
                                        {{ $sharedFile->recipients->contains($tUser->id) ? 'checked' : '' }}>
                                    <span>{{ $tUser->name }}</span>
                                </label>
                            @endforeach
                            <button type="submit" class="btn btn-primary btn-sm">Simpan Pembagian</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>

    <div>
        @if (! $isTaiwanAccount)
            {{-- Edit Metadata Form --}}
            <div class="detail-card">
                <h3 class="card-title">Edit Informasi File</h3>
                <form action="{{ route('shared-files.update', $sharedFile) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div style="margin-bottom: 12px;">
                        <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Nama File</label>
                        <input type="text" name="name" value="{{ $sharedFile->name }}" required class="form-control">
                    </div>
                    <div style="margin-bottom: 12px;">
                        <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Kategori</label>
                        <input type="text" name="category" value="{{ $sharedFile->category }}" class="form-control">
                    </div>
                    <div style="margin-bottom: 12px;">
                        <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Deskripsi</label>
                        <textarea name="description" rows="3" class="form-control">{{ $sharedFile->description }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm" style="width: 100%; justify-content: center;">
                        Perbarui Data
                    </button>
                </form>
            </div>
        @endif

        {{-- Sync Logs Activity --}}
        @if (! $isTaiwanAccount)
            <div class="detail-card">
                <h3 class="card-title">Log Sinkronisasi</h3>
                @forelse ($syncLogs as $log)
                    <div style="padding: 10px 0; border-bottom: 1px solid var(--line-subtle); font-size: 12px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <strong style="color: var(--ink);">Status: {{ ucfirst($log->status) }}</strong>
                            <span class="tabular-nums" style="color: var(--muted);">{{ $log->synced_at?->format('d/m/Y H:i') }}</span>
                        </div>
                        <div style="color: var(--muted);">Arah: {{ strtoupper($log->direction) }}</div>
                    </div>
                @empty
                    <p style="color: var(--muted); font-size: 12px; margin: 0; font-style: italic;">Belum ada catatan log sinkronisasi.</p>
                @endforelse
            </div>
        @endif
    </div>
</div>
@endsection
