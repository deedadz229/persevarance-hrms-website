<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\SharedFile;
use App\Models\SyncLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SharedFileController extends Controller
{
    /**
     * Display a listing of shared files.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $isTaiwanAccount = $user?->role?->name === 'taiwan_account';

        $query = SharedFile::query()
            ->with(['employee:id,name,employee_code', 'uploader:id,name,email', 'recipients:id,name,email'])
            ->latest('id');

        // RBAC constraint: Taiwan Account ONLY sees files explicitly shared with them
        if ($isTaiwanAccount) {
            $query->whereHas('recipients', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        // Filters
        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('owner_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        if ($request->filled('sync_status') && ! $isTaiwanAccount) {
            $query->where('sync_status', $request->query('sync_status'));
        }

        $sharedFiles = $query->paginate(15)->withQueryString();

        // Metrics for dashboard summary
        $totalFiles = $isTaiwanAccount
            ? $user->sharedFiles()->count()
            : SharedFile::count();

        $sharedCount = $isTaiwanAccount
            ? $totalFiles
            : SharedFile::whereHas('recipients')->count();

        $syncedCount = $isTaiwanAccount
            ? $user->sharedFiles()->where('sync_status', 'synced')->count()
            : SharedFile::where('sync_status', 'synced')->count();

        $pendingCount = $isTaiwanAccount
            ? $user->sharedFiles()->where('sync_status', 'pending')->count()
            : SharedFile::where('sync_status', 'pending')->count();

        // Distinct categories for filter
        $availableCategories = SharedFile::whereNotNull('category')
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        // Taiwan Account users for sharing selection (for Super Admin / HRD)
        $taiwanUsers = collect();
        $employees = collect();
        if (! $isTaiwanAccount) {
            $taiwanUsers = User::whereHas('role', function ($q) {
                $q->where('name', 'taiwan_account');
            })->where('status', 'active')->orderBy('name')->get();

            $employees = Employee::where('status', 'active')->orderBy('name')->get(['id', 'name', 'employee_code']);
        }

        return view('shared_files.index', compact(
            'sharedFiles',
            'totalFiles',
            'sharedCount',
            'syncedCount',
            'pendingCount',
            'availableCategories',
            'taiwanUsers',
            'employees',
            'isTaiwanAccount'
        ));
    }

    /**
     * Store a newly uploaded file and optionally share it.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:20480', 'mimes:pdf,xlsx,xls,doc,docx,csv,png,jpg,jpeg,zip'],
            'name' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'recipient_ids' => ['nullable', 'array'],
            'recipient_ids.*' => ['exists:users,id'],
        ], [
            'file.required' => 'Pilih file dokumen yang akan diunggah.',
            'file.file' => 'Berkas yang diunggah tidak valid.',
            'file.max' => 'Ukuran file maksimal adalah 20 MB.',
            'file.mimes' => 'Format file harus berupa: pdf, xlsx, xls, doc, docx, csv, png, jpg, jpeg, atau zip.',
        ]);

        $uploadedFile = $request->file('file');
        $originalName = $uploadedFile->getClientOriginalName();
        $displayName = ! empty($validated['name']) ? $validated['name'] : pathinfo($originalName, PATHINFO_FILENAME);
        $extension = $uploadedFile->getClientOriginalExtension();

        // Ensure display name has proper extension if not included
        if ($extension && ! Str::endsWith(strtolower($displayName), '.'.strtolower($extension))) {
            $displayName .= '.'.$extension;
        }

        $safeFileName = Str::random(40).($extension ? '.'.$extension : '');
        $storagePath = $uploadedFile->storeAs('shared_files', $safeFileName, 'local');

        $user = $request->user();
        $ownerName = $user?->name ?? 'Admin';

        $hasRecipients = ! empty($validated['recipient_ids']);

        $sharedFile = SharedFile::create([
            'employee_id' => $validated['employee_id'] ?? null,
            'name' => $displayName,
            'category' => $validated['category'] ?? 'Umum',
            'description' => $validated['description'] ?? null,
            'mime_type' => $uploadedFile->getClientMimeType(),
            'size' => $uploadedFile->getSize(),
            'storage_path' => $storagePath,
            'owner_name' => $ownerName,
            'uploaded_by' => $user?->id,
            'sync_status' => $hasRecipients ? 'synced' : 'pending',
        ]);

        if ($hasRecipients) {
            $sharedAt = now();
            $attachData = [];
            foreach ($validated['recipient_ids'] as $recipientId) {
                $attachData[$recipientId] = ['shared_at' => $sharedAt];
            }
            $sharedFile->recipients()->attach($attachData);

            // Log Sync
            SyncLog::create([
                'entity_type' => SharedFile::class,
                'entity_id' => $sharedFile->id,
                'direction' => 'outbound',
                'status' => 'success',
                'request_data' => [
                    'file_id' => $sharedFile->id,
                    'file_name' => $sharedFile->name,
                    'recipients' => $validated['recipient_ids'],
                ],
                'response_data' => ['synced' => true],
                'synced_at' => now(),
            ]);
        }

        // Audit Log
        AuditLog::create([
            'user_id' => $user?->id,
            'action' => 'shared_file_uploaded',
            'entity_type' => SharedFile::class,
            'entity_id' => $sharedFile->id,
            'old_values' => null,
            'new_values' => [
                'name' => $sharedFile->name,
                'size' => $sharedFile->size,
                'category' => $sharedFile->category,
                'recipients_count' => count($validated['recipient_ids'] ?? []),
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()->route('shared-files.index')
            ->with('success', "File \"{$sharedFile->name}\" berhasil diunggah".($hasRecipients ? ' dan dibagikan.' : '.'));
    }

    /**
     * Show details of a shared file.
     */
    public function show(Request $request, SharedFile $sharedFile): View
    {
        $user = $request->user();
        $isTaiwanAccount = $user?->role?->name === 'taiwan_account';

        // Authorization check for Taiwan Account
        if ($isTaiwanAccount && ! $sharedFile->recipients()->where('users.id', $user->id)->exists()) {
            abort(403, 'Anda tidak memiliki izin untuk melihat file ini.');
        }

        $sharedFile->load(['employee', 'uploader', 'recipients']);

        $syncLogs = SyncLog::where('entity_type', SharedFile::class)
            ->where('entity_id', $sharedFile->id)
            ->latest()
            ->take(5)
            ->get();

        $taiwanUsers = collect();
        if (! $isTaiwanAccount) {
            $taiwanUsers = User::whereHas('role', function ($q) {
                $q->where('name', 'taiwan_account');
            })->where('status', 'active')->orderBy('name')->get();
        }

        return view('shared_files.show', compact('sharedFile', 'syncLogs', 'isTaiwanAccount', 'taiwanUsers'));
    }

    /**
     * Update file metadata (name, category, description).
     */
    public function update(Request $request, SharedFile $sharedFile): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'employee_id' => ['nullable', 'exists:employees,id'],
        ], [
            'name.required' => 'Nama file wajib diisi.',
        ]);

        $oldValues = $sharedFile->only(['name', 'category', 'description', 'employee_id']);

        $sharedFile->update([
            'name' => $validated['name'],
            'category' => $validated['category'] ?? $sharedFile->category,
            'description' => $validated['description'] ?? null,
            'employee_id' => $validated['employee_id'] ?? null,
        ]);

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'shared_file_updated',
            'entity_type' => SharedFile::class,
            'entity_id' => $sharedFile->id,
            'old_values' => $oldValues,
            'new_values' => $sharedFile->only(['name', 'category', 'description', 'employee_id']),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()->route('shared-files.index')
            ->with('success', "Informasi file \"{$sharedFile->name}\" berhasil diperbarui.");
    }

    /**
     * Share file with designated Taiwan Account users.
     */
    public function share(Request $request, SharedFile $sharedFile): RedirectResponse
    {
        $validated = $request->validate([
            'recipient_ids' => ['required', 'array', 'min:1'],
            'recipient_ids.*' => ['exists:users,id'],
        ], [
            'recipient_ids.required' => 'Pilih setidaknya satu akun Taiwan untuk membagikan file.',
            'recipient_ids.min' => 'Pilih setidaknya satu akun Taiwan untuk membagikan file.',
        ]);

        $syncData = [];
        $now = now();
        foreach ($validated['recipient_ids'] as $userId) {
            $syncData[$userId] = ['shared_at' => $now];
        }

        // Sync recipients
        $sharedFile->recipients()->sync($syncData);
        $sharedFile->update(['sync_status' => 'synced']);

        // Record SyncLog
        SyncLog::create([
            'entity_type' => SharedFile::class,
            'entity_id' => $sharedFile->id,
            'direction' => 'outbound',
            'status' => 'success',
            'request_data' => [
                'action' => 'share',
                'file_id' => $sharedFile->id,
                'recipients' => $validated['recipient_ids'],
            ],
            'response_data' => ['success' => true, 'synced_count' => count($validated['recipient_ids'])],
            'synced_at' => now(),
        ]);

        // Audit Log
        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'shared_file_shared',
            'entity_type' => SharedFile::class,
            'entity_id' => $sharedFile->id,
            'old_values' => null,
            'new_values' => ['recipients' => $validated['recipient_ids']],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()->back()
            ->with('success', "File \"{$sharedFile->name}\" berhasil dibagikan kepada penerima yang dipilih.");
    }

    /**
     * Revoke access for a recipient or all recipients.
     */
    public function revoke(Request $request, SharedFile $sharedFile): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
        ]);

        if (! empty($validated['user_id'])) {
            $revokedUser = User::find($validated['user_id']);
            $sharedFile->recipients()->detach($validated['user_id']);
            $message = 'Akses file untuk '.($revokedUser?->name ?? 'pengguna').' berhasil dicabut.';
        } else {
            $sharedFile->recipients()->detach();
            $message = "Semua akses pembagian untuk file \"{$sharedFile->name}\" berhasil dicabut.";
        }

        $remainingCount = $sharedFile->recipients()->count();
        if ($remainingCount === 0) {
            $sharedFile->update(['sync_status' => 'pending']);
        }

        // Record SyncLog
        SyncLog::create([
            'entity_type' => SharedFile::class,
            'entity_id' => $sharedFile->id,
            'direction' => 'outbound',
            'status' => 'revoked',
            'request_data' => [
                'action' => 'revoke',
                'revoked_user_id' => $validated['user_id'] ?? 'all',
                'remaining_count' => $remainingCount,
            ],
            'response_data' => ['revoked' => true],
            'synced_at' => now(),
        ]);

        // Audit Log
        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'shared_file_revoked',
            'entity_type' => SharedFile::class,
            'entity_id' => $sharedFile->id,
            'old_values' => ['revoked_user_id' => $validated['user_id'] ?? 'all'],
            'new_values' => ['remaining_recipients' => $remainingCount],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()->back()->with('success', $message);
    }

    /**
     * Download the file securely.
     */
    public function download(Request $request, SharedFile $sharedFile): StreamedResponse
    {
        $user = $request->user();
        $isTaiwanAccount = $user?->role?->name === 'taiwan_account';

        // RBAC check: Taiwan Account must be in recipients
        if ($isTaiwanAccount) {
            $isRecipient = $sharedFile->recipients()->where('users.id', $user->id)->exists();
            if (! $isRecipient) {
                abort(403, 'Anda tidak memiliki hak akses untuk mengunduh file ini.');
            }
        }

        // Check storage file existence
        if (! Storage::disk('local')->exists($sharedFile->storage_path)) {
            abort(404, 'File fisik tidak ditemukan pada sistem penyimpanan.');
        }

        // Audit Log
        AuditLog::create([
            'user_id' => $user?->id,
            'action' => 'shared_file_downloaded',
            'entity_type' => SharedFile::class,
            'entity_id' => $sharedFile->id,
            'old_values' => null,
            'new_values' => [
                'file_name' => $sharedFile->name,
                'size' => $sharedFile->size,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return Storage::disk('local')->download($sharedFile->storage_path, $sharedFile->name);
    }

    /**
     * Delete the shared file and its physical storage.
     */
    public function destroy(Request $request, SharedFile $sharedFile): RedirectResponse
    {
        $fileName = $sharedFile->name;

        // Delete from local disk
        if (Storage::disk('local')->exists($sharedFile->storage_path)) {
            Storage::disk('local')->delete($sharedFile->storage_path);
        }

        // Detach recipients
        $sharedFile->recipients()->detach();

        // Audit log
        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'shared_file_deleted',
            'entity_type' => SharedFile::class,
            'entity_id' => $sharedFile->id,
            'old_values' => [
                'name' => $sharedFile->name,
                'category' => $sharedFile->category,
                'size' => $sharedFile->size,
            ],
            'new_values' => null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        $sharedFile->delete();

        return redirect()->route('shared-files.index')
            ->with('success', "File \"{$fileName}\" berhasil dihapus.");
    }
}
