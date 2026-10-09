<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\LeaveRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureCanViewLeave($request);

        $search = $request->query('search');
        $departmentId = $request->query('department_id');
        $status = $request->query('status');
        $leaveType = $request->query('leave_type');
        $date = $request->query('date');

        $departments = Department::query()->orderBy('name')->get();
        $leaveTypes = LeaveRequest::query()
            ->select('leave_type')
            ->distinct()
            ->pluck('leave_type');

        // Summary counts directly from SQLite
        $summary = [
            'total' => LeaveRequest::query()->count(),
            'pending' => LeaveRequest::query()->where('status', 'pending')->count(),
            'approved' => LeaveRequest::query()->where('status', 'approved')->count(),
            'rejected' => LeaveRequest::query()->where('status', 'rejected')->count(),
        ];

        $query = LeaveRequest::query()
            ->with(['employee.department', 'employee.position', 'approver']);

        if ($search) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%");
            });
        }

        if ($departmentId) {
            $query->whereHas('employee', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        if ($status && in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $status);
        }

        if ($leaveType) {
            $query->where('leave_type', $leaveType);
        }

        if ($date) {
            $query->where(function ($q) use ($date) {
                $q->whereDate('start_date', '<=', $date)
                    ->whereDate('end_date', '>=', $date);
            });
        }

        $leaveRequests = $query->latest('id')->paginate(15)->withQueryString();

        return view('leave_requests.index', [
            'leaveRequests' => $leaveRequests,
            'departments' => $departments,
            'leaveTypes' => $leaveTypes,
            'summary' => $summary,
            'currentSearch' => $search,
            'currentDepartment' => $departmentId,
            'currentStatus' => $status,
            'currentLeaveType' => $leaveType,
            'currentDate' => $date,
        ]);
    }

    public function show(Request $request, LeaveRequest $leaveRequest): View
    {
        $this->ensureCanViewLeave($request);

        $leaveRequest->loadMissing([
            'employee.department',
            'employee.position',
            'approver',
        ]);

        return view('leave_requests.show', [
            'leaveRequest' => $leaveRequest,
        ]);
    }

    public function approve(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->ensureCanApproveLeave($request);

        if ($leaveRequest->status !== 'pending') {
            return redirect()
                ->back()
                ->with('error', 'Pengajuan cuti ini sudah diproses sebelumnya dan tidak dapat diubah lagi.');
        }

        $user = $request->user();
        $oldStatus = $leaveRequest->status;

        $leaveRequest->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'leave_approved',
            'entity_type' => LeaveRequest::class,
            'entity_id' => $leaveRequest->id,
            'old_values' => ['status' => $oldStatus],
            'new_values' => [
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now()->toDateTimeString(),
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()
            ->back()
            ->with('success', 'Pengajuan cuti berhasil disetujui.');
    }

    public function reject(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->ensureCanApproveLeave($request);

        if ($leaveRequest->status !== 'pending') {
            return redirect()
                ->back()
                ->with('error', 'Pengajuan cuti ini sudah diproses sebelumnya dan tidak dapat diubah lagi.');
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:3', 'max:500'],
        ], [
            'rejection_reason.required' => 'Alasan penolakan cuti wajib diisi.',
            'rejection_reason.min' => 'Alasan penolakan minimal 3 karakter.',
        ]);

        $user = $request->user();
        $oldStatus = $leaveRequest->status;

        $leaveRequest->update([
            'status' => 'rejected',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'leave_rejected',
            'entity_type' => LeaveRequest::class,
            'entity_id' => $leaveRequest->id,
            'old_values' => ['status' => $oldStatus],
            'new_values' => [
                'status' => 'rejected',
                'approved_by' => $user->id,
                'approved_at' => now()->toDateTimeString(),
                'rejection_reason' => $validated['rejection_reason'],
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()
            ->back()
            ->with('success', 'Pengajuan cuti telah ditolak.');
    }

    private function ensureCanApproveLeave(Request $request): void
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if ($user->role?->name === 'taiwan_account') {
            abort(403, 'Taiwan Account bersifat read-only dan tidak diizinkan melakukan approval.');
        }

        $hasPermission = $user->role?->permissions->contains('name', 'approve_leave');
        if (! $hasPermission) {
            abort(403, 'Anda tidak memiliki permission untuk menyetujui atau menolak pengajuan cuti.');
        }
    }

    private function ensureCanViewLeave(Request $request): void
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        $canView = $user->role?->name === 'taiwan_account'
            || $user->role?->name === 'super_admin'
            || ($user->role?->permissions->contains('name', 'manage_leave') ?? false);

        if (! $canView) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat pengajuan cuti.');
        }
    }
}
