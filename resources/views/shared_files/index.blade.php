@extends('layouts.app')

@section('title', 'Aktivitas File')

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

    /* Summary Stat Cards */
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px; }
    .stat-card { background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius-md, 8px); padding: 18px 20px; box-shadow: var(--shadow-xs); display: flex; flex-direction: column; justify-content: space-between; }
    .stat-header { display: flex; align-items: center; justify-content: space-between; color: var(--muted); font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }
    .stat-value { font-size: 24px; font-weight: 700; color: var(--ink); margin: 8px 0 2px; }
    .stat-desc { font-size: 12px; color: var(--muted); }

    /* Filter Toolbar */
    .filter-card { padding: 16px 20px; margin-bottom: 20px; background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius-md, 8px); box-shadow: var(--shadow-xs); }
    .filter-form { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
    .filter-group { flex: 1; min-width: 180px; }
    .filter-group.search-group { flex: 2; min-width: 240px; }
    .form-control { width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: var(--radius-sm, 6px); font-size: 13px; background: #fafbfc; color: var(--ink); outline: none; }
    .form-control:focus { border-color: var(--brand); background: #fff; box-shadow: 0 0 0 3px rgba(185, 28, 28, .1); }

    /* Table */
    .table-container { overflow-x: auto; background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius-md, 8px); box-shadow: var(--shadow-xs); }
    .table-custom { width: 100%; border-collapse: collapse; text-align: left; }
    .table-custom th { padding: 13px 18px; background: #fbfcfc; color: var(--muted); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid var(--line); }
    .table-custom td { padding: 14px 18px; border-bottom: 1px solid var(--line); font-size: 13px; vertical-align: middle; }
    .table-custom tbody tr:hover { background: #fafcfc; }
    
    .file-title strong { display: block; font-weight: 600; color: var(--ink); font-size: 13.5px; }
    .file-title span { display: block; margin-top: 2px; color: var(--muted); font-size: 12px; }
    
    .badge { display: inline-flex; align-items: center; padding: 3px 8px; border-radius: 999px; font-size: 11.5px; font-weight: 600; }
    .badge-synced { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .badge-pending { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
    .badge-category { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11.5px; font-weight: 500; background: #f1f5f9; color: #334155; border: 1px solid #e2e8f0; }

    .recipients-stack { display: flex; flex-wrap: wrap; gap: 4px; }
    .recipient-tag { display: inline-flex; align-items: center; padding: 2px 6px; font-size: 11px; font-weight: 500; background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; border-radius: 4px; }

    .action-links { display: flex; align-items: center; gap: 6px; }
    .pagination-wrapper { padding: 14px 20px; display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: var(--muted); border-top: 1px solid var(--line); background: var(--surface); border-bottom-left-radius: var(--radius-md, 8px); border-bottom-right-radius: var(--radius-md, 8px); }
    .empty-state { padding: 48px 20px; text-align: center; color: var(--muted); }

    /* Modal Styling */
    .modal-backdrop { display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.6); z-index: 100; align-items: center; justify-content: center; backdrop-filter: blur(2px); }
    .modal-backdrop.is-open { display: flex; }
    .modal-card { background: #fff; width: 100%; max-width: 540px; border-radius: var(--radius-lg, 12px); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); overflow: hidden; }
    .modal-head { display: flex; align-items: center; justify-content: space-between; padding: 18px 24px; border-bottom: 1px solid var(--line); background: #fafbfc; }
    .modal-head h3 { margin: 0; font-size: 16px; font-weight: 700; color: var(--ink); }
    .modal-close { background: none; border: none; font-size: 20px; color: var(--muted); cursor: pointer; line-height: 1; padding: 4px; }
    .modal-close:hover { color: var(--ink); }
    .modal-body { padding: 24px; max-height: calc(85vh - 120px); overflow-y: auto; }
    .modal-footer { padding: 16px 24px; border-top: 1px solid var(--line); display: flex; justify-content: flex-end; gap: 10px; background: #fafbfc; }
    .form-label { display: block; font-size: 12.5px; font-weight: 600; color: var(--ink); margin-bottom: 6px; }
    .form-help { font-size: 11.5px; color: var(--muted); margin-top: 4px; }
    .form-group { margin-bottom: 18px; }
    .checkbox-list { display: flex; flex-direction: column; gap: 8px; max-height: 140px; overflow-y: auto; padding: 8px 12px; border: 1px solid var(--line); border-radius: 6px; background: #fafbfc; }
    .checkbox-item { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--ink); cursor: pointer; }
</style>

<div class="page-header">
    <div class="page-title-box">
        <h2>{{ __('shared_files.title') }}</h2>
        <p>
            @if ($isTaiwanAccount)
                {{ __('shared_files.subtitle_taiwan') }}
            @else
                {{ __('shared_files.subtitle_admin') }}
            @endif
        </p>
    </div>
    <div class="header-actions">
        @if (! $isTaiwanAccount)
            <button type="button" class="btn btn-primary" onclick="openUploadModal()">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                {{ __('shared_files.upload_btn') }}
            </button>
        @endif
    </div>
</div>

{{-- Summary Metric Cards --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header">
            <span>{{ $isTaiwanAccount ? __('shared_files.total_shared_taiwan') : __('shared_files.total_files') }}</span>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div class="stat-value tabular-nums">{{ $totalFiles }}</div>
        <div class="stat-desc">{{ __('shared_files.document_size') }}</div>
    </div>
    @if (! $isTaiwanAccount)
        <div class="stat-card">
            <div class="stat-header">
                <span>{{ __('shared_files.shared_files') }}</span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div class="stat-value tabular-nums">{{ $sharedCount }}</div>
            <div class="stat-desc">{{ __('shared_files.taiwan_recipients') }}</div>
        </div>
    @endif
    <div class="stat-card">
        <div class="stat-header">
            <span>{{ __('shared_files.synced_status') }}</span>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <div class="stat-value tabular-nums" style="color: #15803d;">{{ $syncedCount }}</div>
        <div class="stat-desc">{{ __('shared_files.active_shared') }}</div>
    </div>
    @if (! $isTaiwanAccount)
        <div class="stat-card">
            <div class="stat-header">
                <span>{{ __('shared_files.pending_status') }}</span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div class="stat-value tabular-nums" style="color: #b45309;">{{ $pendingCount }}</div>
            <div class="stat-desc">{{ __('shared_files.unshared') }}</div>
        </div>
    @endif
</div>

{{-- Filter Card --}}
<div class="filter-card">
    <form action="{{ route('shared-files.index') }}" method="GET" class="filter-form">
        <div class="filter-group search-group">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('shared_files.search_placeholder') }}" class="form-control">
        </div>
        <div class="filter-group">
            <select name="category" class="form-control">
                <option value="">{{ __('shared_files.all_categories') }}</option>
                @foreach ($availableCategories as $cat)
                    <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>
                        {{ $cat }}
                    </option>
                @endforeach
            </select>
        </div>
        @if (! $isTaiwanAccount)
            <div class="filter-group">
                <select name="sync_status" class="form-control">
                    <option value="">{{ __('shared_files.all_sync_status') }}</option>
                    <option value="synced" {{ request('sync_status') === 'synced' ? 'selected' : '' }}>{{ __('shared_files.status_synced') }}</option>
                    <option value="pending" {{ request('sync_status') === 'pending' ? 'selected' : '' }}>{{ __('shared_files.status_pending') }}</option>
                </select>
            </div>
        @endif
        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary" style="padding: 8px 14px;">{{ __('common.filter') }}</button>
            @if (request()->hasAny(['search', 'category', 'sync_status']))
                <a href="{{ route('shared-files.index') }}" class="btn btn-outline" style="padding: 8px 12px;" title="{{ __('common.reset') }}">{{ __('common.reset') }}</a>
            @endif
        </div>
    </form>
</div>

{{-- Files Table --}}
<div class="table-container">
    <table class="table-custom">
        <thead>
            <tr>
                <th>{{ __('shared_files.file_name') }}</th>
                <th>{{ __('shared_files.category') }}</th>
                <th>{{ __('shared_files.size') }}</th>
                <th>{{ __('shared_files.owner_uploader') }}</th>
                @if (! $isTaiwanAccount)
                    <th>{{ __('shared_files.recipients') }}</th>
                    <th>{{ __('shared_files.sync_status') }}</th>
                @endif
                <th>{{ __('shared_files.uploaded_at') }}</th>
                <th style="text-align: right;">{{ __('common.action') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($sharedFiles as $file)
                <tr>
                    <td>
                        <div class="file-title">
                            <strong>{{ $file->name }}</strong>
                            @if ($file->description)
                                <span>{{ Str::limit($file->description, 50) }}</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        <span class="badge-category">{{ $file->category ?? __('shared_files.general_category') }}</span>
                    </td>
                    <td class="tabular-nums" style="white-space: nowrap; color: var(--muted); font-size: 12.5px;">
                        {{ $file->size ? number_format($file->size / 1024, 1) . ' KB' : '-' }}
                    </td>
                    <td>
                        <span style="font-weight: 500; color: var(--ink);">{{ $file->owner_name ?? $file->uploader?->name ?? '-' }}</span>
                        @if ($file->employee)
                            <div style="font-size: 11.5px; color: var(--muted);">{{ __('shared_files.related_prefix', ['name' => $file->employee->name, 'code' => $file->employee->employee_code]) }}</div>
                        @endif
                    </td>
                    @if (! $isTaiwanAccount)
                        <td>
                            @if ($file->recipients->isNotEmpty())
                                <div class="recipients-stack">
                                    @foreach ($file->recipients as $recipient)
                                        <span class="recipient-tag">{{ $recipient->name }}</span>
                                    @endforeach
                                </div>
                            @else
                                <span style="color: var(--muted); font-size: 12px; font-style: italic;">{{ __('shared_files.not_shared_yet') }}</span>
                            @endif
                        </td>
                        <td>
                            @if ($file->sync_status === 'synced')
                                <span class="badge badge-synced">{{ __('shared_files.synced_status') }}</span>
                            @else
                                <span class="badge badge-pending">{{ __('shared_files.pending_status') }}</span>
                            @endif
                        </td>
                    @endif
                    <td class="tabular-nums" style="white-space: nowrap; color: var(--muted); font-size: 12.5px;">
                        {{ $file->created_at->format('d M Y, H:i') }}
                    </td>
                    <td style="text-align: right;">
                        <div class="action-links" style="justify-content: flex-end;">
                            {{-- Download Button (Safe Server Action) --}}
                            <a href="{{ route('shared-files.download', $file) }}" class="btn btn-outline btn-sm" title="{{ __('common.download') }}">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                {{ __('common.download') }}
                            </a>

                            {{-- Detail Button --}}
                            <a href="{{ route('shared-files.show', $file) }}" class="btn btn-outline btn-sm" title="{{ __('common.detail') }}">
                                {{ __('common.detail') }}
                            </a>

                            @if (! $isTaiwanAccount)
                                {{-- Share Button --}}
                                <button type="button" class="btn btn-outline btn-sm" 
                                    onclick="openShareModal({{ $file->id }}, '{{ addslashes($file->name) }}', {{ json_encode($file->recipients->pluck('id')) }})" 
                                    title="{{ __('shared_files.share') }}">
                                    {{ __('shared_files.share') }}
                                </button>

                                {{-- Delete Button --}}
                                <form action="{{ route('shared-files.destroy', $file) }}" method="POST" onsubmit="return confirm('{{ __('shared_files.confirm_delete') }}');" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" title="{{ __('common.delete') }}">
                                        {{ __('common.delete') }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $isTaiwanAccount ? 5 : 8 }}" class="empty-state">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color: var(--muted-light); margin-bottom: 8px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <p style="margin: 0; font-weight: 500;">{{ __('shared_files.no_files') }}</p>
                        <p style="margin: 4px 0 0; font-size: 12px; color: var(--muted);">
                            @if ($isTaiwanAccount)
                                {{ __('shared_files.no_files_taiwan') }}
                            @else
                                {{ __('shared_files.no_files_admin') }}
                            @endif
                        </p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    @if ($sharedFiles->hasPages())
        <div class="pagination-wrapper">
            <div>{{ __('shared_files.showing_records', ['from' => $sharedFiles->firstItem(), 'to' => $sharedFiles->lastItem(), 'total' => $sharedFiles->total()]) }}</div>
            <div>{{ $sharedFiles->links() }}</div>
        </div>
    @endif
</div>

@if (! $isTaiwanAccount)
    {{-- Upload Modal --}}
    <div class="modal-backdrop" id="uploadModal">
        <div class="modal-card">
            <div class="modal-head">
                <h3>{{ __('shared_files.upload_modal_title') }}</h3>
                <button type="button" class="modal-close" onclick="closeUploadModal()">&times;</button>
            </div>
            <form action="{{ route('shared-files.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="file_input">{{ __('shared_files.choose_file') }} *</label>
                        <input type="file" name="file" id="file_input" required class="form-control" accept=".pdf,.xlsx,.xls,.doc,.docx,.csv,.png,.jpg,.jpeg,.zip">
                        <div class="form-help">{{ __('shared_files.file_hint') }}</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="file_name">{{ __('shared_files.display_name') }}</label>
                        <input type="text" name="name" id="file_name" placeholder="{{ __('shared_files.display_name_hint') }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="file_category">{{ __('shared_files.document_category') }}</label>
                        <input type="text" name="category" id="file_category" list="category_suggestions" placeholder="Misal: Kebijakan Perusahaan, Laporan Bulanan, Regulasi..." class="form-control" value="Umum">
                        <datalist id="category_suggestions">
                            <option value="Kebijakan & SOP">
                            <option value="Laporan Manajerial">
                            <option value="Regulasi & Legal">
                            <option value="Dokumen HRD">
                            <option value="Penggajian & Finansial">
                            <option value="Umum">
                        </datalist>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="employee_id">{{ __('shared_files.related_employee') }}</label>
                        <select name="employee_id" id="employee_id" class="form-control">
                            <option value="">{{ __('shared_files.no_employee_link') }}</option>
                            @foreach ($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->employee_code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('shared_files.share_direct') }}</label>
                        <div class="checkbox-list">
                            @forelse ($taiwanUsers as $tUser)
                                <label class="checkbox-item">
                                    <input type="checkbox" name="recipient_ids[]" value="{{ $tUser->id }}">
                                    <span>{{ $tUser->name }} ({{ $tUser->email }})</span>
                                </label>
                            @empty
                                <span style="font-size: 12px; color: var(--muted);">{{ __('shared_files.no_active_taiwan') }}</span>
                            @endforelse
                        </div>
                        <div class="form-help">{{ __('shared_files.direct_share_hint') }}</div>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" for="file_description">{{ __('shared_files.additional_description') }}</label>
                        <textarea name="description" id="file_description" rows="3" class="form-control" placeholder="{{ __('shared_files.description_placeholder') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeUploadModal()">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('shared_files.upload_btn') }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Share Modal --}}
    <div class="modal-backdrop" id="shareModal">
        <div class="modal-card">
            <div class="modal-head">
                <h3>{{ __('shared_files.share_modal_title') }}</h3>
                <button type="button" class="modal-close" onclick="closeShareModal()">&times;</button>
            </div>
            <form id="shareForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p style="margin: 0 0 16px; font-size: 13px; color: var(--muted);">
                        {{ __('shared_files.share_modal_desc', ['name' => '']) }}<strong id="shareModalFileName" style="color: var(--ink);"></strong>.
                    </p>
                    <div class="form-group">
                        <label class="form-label">{{ __('shared_files.taiwan_recipients') }} *</label>
                        <div class="checkbox-list" id="shareCheckboxes">
                            @foreach ($taiwanUsers as $tUser)
                                <label class="checkbox-item">
                                    <input type="checkbox" name="recipient_ids[]" value="{{ $tUser->id }}" id="recipient_{{ $tUser->id }}">
                                    <span>{{ $tUser->name }} ({{ $tUser->email }})</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeShareModal()">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('shared_files.save_sharing') }}</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openUploadModal() {
            document.getElementById('uploadModal').classList.add('is-open');
        }
        function closeUploadModal() {
            document.getElementById('uploadModal').classList.remove('is-open');
        }
        function openShareModal(fileId, fileName, recipientIds) {
            document.getElementById('shareModalFileName').textContent = fileName;
            const form = document.getElementById('shareForm');
            form.action = '/shared-files/' + fileId + '/share';
            
            // Set checked states
            const checkboxes = document.querySelectorAll('#shareCheckboxes input[type="checkbox"]');
            checkboxes.forEach(cb => {
                cb.checked = recipientIds.includes(parseInt(cb.value));
            });

            document.getElementById('shareModal').classList.add('is-open');
        }
        function closeShareModal() {
            document.getElementById('shareModal').classList.remove('is-open');
        }

        // Close on click backdrop
        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-backdrop')) {
                e.target.classList.remove('is-open');
            }
        });
    </script>
@endif
@endsection
