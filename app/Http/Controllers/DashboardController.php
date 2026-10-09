<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $user->loadMissing('role.permissions');

        $today = now()->toDateString();
        $periodMonth = now()->month;
        $periodYear = now()->year;

        $dashboard = match ($user->role?->name) {
            'super_admin' => [
                'total_users' => User::query()->count(),
                'total_employees' => Employee::query()->count(),
                'total_audit_logs' => AuditLog::query()->count(),
                'recent_activities' => AuditLog::query()
                    ->with('user:id,name')
                    ->latest('created_at')
                    ->limit(5)
                    ->get(),
            ],
            'manager_hrd' => [
                'total_employees' => Employee::query()->count(),
                'present_today' => Attendance::query()
                    ->whereDate('attendance_date', $today)
                    ->count(),
                'pending_leaves' => LeaveRequest::query()
                    ->where('status', 'pending')
                    ->count(),
                'total_payroll' => Payroll::query()
                    ->where('period_month', $periodMonth)
                    ->where('period_year', $periodYear)
                    ->sum('net_salary'),
                'recent_activities' => AuditLog::query()
                    ->with('user:id,name')
                    ->latest('created_at')
                    ->limit(5)
                    ->get(),
                'pending_leave_requests' => LeaveRequest::query()
                    ->with('employee:id,name')
                    ->where('status', 'pending')
                    ->latest()
                    ->limit(5)
                    ->get(),
                'today_attendances' => Attendance::query()
                    ->with('employee:id,name')
                    ->whereDate('attendance_date', $today)
                    ->latest('check_in')
                    ->limit(5)
                    ->get(),
            ],
            'taiwan_account' => [
                'total_employees' => Employee::query()->count(),
                'present_today' => Attendance::query()
                    ->whereDate('attendance_date', $today)
                    ->count(),
                'pending_leaves' => LeaveRequest::query()
                    ->where('status', 'pending')
                    ->count(),
                'payroll_summary' => $user->role?->permissions->contains('name', 'view_reports')
                    ? Payroll::query()
                        ->where('period_month', $periodMonth)
                        ->where('period_year', $periodYear)
                        ->sum('net_salary')
                    : null,
            ],
            default => [],
        };

        return view('dashboard', [
            'user' => $user,
            'dashboard' => $dashboard,
            'dashboardRole' => $user->role?->name,
        ]);
    }
}
