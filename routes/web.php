<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\OvertimeController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SharedFileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/locale/{locale}', [LocaleController::class, 'switch'])
    ->name('locale.switch');

Route::redirect('/', '/login');

Route::get('/login', [LoginController::class, 'showLogin'])
    ->name('login');

Route::post('/login', [LoginController::class, 'login'])
    ->name('login.process');

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:view_dashboard')
        ->name('dashboard');

    // Department Management
    Route::get('/departments', [DepartmentController::class, 'index'])
        ->name('departments.index');

    Route::middleware('role:super_admin')->group(function () {
        Route::get('/departments/create', [DepartmentController::class, 'create'])
            ->name('departments.create');
        Route::post('/departments', [DepartmentController::class, 'store'])
            ->name('departments.store');
        Route::get('/departments/{department}/edit', [DepartmentController::class, 'edit'])
            ->name('departments.edit');
        Route::match(['put', 'patch'], '/departments/{department}', [DepartmentController::class, 'update'])
            ->name('departments.update');
        Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])
            ->name('departments.destroy');
    });

    Route::get('/departments/{department}', [DepartmentController::class, 'show'])
        ->name('departments.show');

    Route::get('/rbac-test', function () {
        return 'RBAC Super Admin berhasil.';
    })->middleware(['role:super_admin', 'permission:manage_roles']);

    // Employee Management
    Route::get('/employees', [EmployeeController::class, 'index'])
        ->name('employees.index');

    Route::get('/employees/resigned', [EmployeeController::class, 'resigned'])
        ->name('employees.resigned');

    Route::middleware(['role:super_admin,manager_hrd', 'permission:manage_employees'])->group(function () {
        Route::get('/employees/create', [EmployeeController::class, 'create'])
            ->name('employees.create');
        Route::post('/employees', [EmployeeController::class, 'store'])
            ->name('employees.store');
        Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])
            ->name('employees.edit');
        Route::put('/employees/{employee}', [EmployeeController::class, 'update'])
            ->name('employees.update');
        Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])
            ->name('employees.destroy');
    });

    Route::get('/employees/{employee}', [EmployeeController::class, 'show'])
        ->name('employees.show');

    // Attendance Management
    Route::middleware('permission:view_attendance')->group(function () {
        Route::get('/attendances', [AttendanceController::class, 'index'])
            ->name('attendances.index');
        Route::get('/attendances/export', [AttendanceController::class, 'export'])
            ->name('attendances.export');
        Route::get('/attendances/{attendance}', [AttendanceController::class, 'show'])
            ->name('attendances.show');
    });

    // Leave Management (Cuti & Izin)
    Route::get('/leave-requests', [LeaveRequestController::class, 'index'])
        ->name('leave-requests.index');
    Route::get('/leave-requests/{leaveRequest}', [LeaveRequestController::class, 'show'])
        ->name('leave-requests.show');

    Route::middleware(['role:super_admin,manager_hrd', 'permission:approve_leave'])->group(function () {
        Route::post('/leave-requests/{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])
            ->name('leave-requests.approve');
        Route::post('/leave-requests/{leaveRequest}/reject', [LeaveRequestController::class, 'reject'])
            ->name('leave-requests.reject');
    });

    // Overtime Management (Lembur)
    Route::get('/overtimes', [OvertimeController::class, 'index'])
        ->name('overtimes.index');

    Route::middleware(['role:super_admin,manager_hrd', 'permission:manage_overtime'])->group(function () {
        Route::get('/overtimes/create', [OvertimeController::class, 'create'])
            ->name('overtimes.create');
        Route::post('/overtimes', [OvertimeController::class, 'store'])
            ->name('overtimes.store');
    });

    Route::get('/overtimes/{overtime}', [OvertimeController::class, 'show'])
        ->name('overtimes.show');

    Route::middleware(['role:super_admin,manager_hrd', 'permission:approve_overtime'])->group(function () {
        Route::post('/overtimes/{overtime}/approve', [OvertimeController::class, 'approve'])
            ->name('overtimes.approve');
        Route::post('/overtimes/{overtime}/reject', [OvertimeController::class, 'reject'])
            ->name('overtimes.reject');
    });

    // Payroll Management (Penggajian)
    Route::get('/payrolls', [PayrollController::class, 'index'])
        ->name('payrolls.index');
    Route::get('/payrolls/department-summary', [PayrollController::class, 'departmentSummary'])
        ->name('payrolls.department-summary');
    Route::get('/payrolls/evaluation', [PayrollController::class, 'evaluation'])
        ->name('payrolls.evaluation');

    Route::middleware(['role:super_admin,manager_hrd', 'permission:manage_payroll'])->group(function () {
        Route::get('/payrolls/create', [PayrollController::class, 'create'])
            ->name('payrolls.create');
        Route::post('/payrolls', [PayrollController::class, 'store'])
            ->name('payrolls.store');
        Route::get('/payrolls/{payroll}/correction', [PayrollController::class, 'correction'])
            ->name('payrolls.correction');
        Route::post('/payrolls/{payroll}/correction', [PayrollController::class, 'updateCorrection'])
            ->name('payrolls.update-correction');
        Route::post('/payrolls/{payroll}/generate', [PayrollController::class, 'generate'])
            ->name('payrolls.generate');
    });

    Route::get('/payrolls/{payroll}', [PayrollController::class, 'show'])
        ->name('payrolls.show');
    Route::get('/payrolls/{payroll}/slip', [PayrollController::class, 'slip'])
        ->name('payrolls.slip');

    // Reports / Laporan Module
    Route::middleware('permission:view_reports')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])
            ->name('reports.index');

        // 1. Absensi
        Route::get('/reports/attendances', [ReportController::class, 'attendances'])
            ->name('reports.attendances');

        // 2. Cuti & Izin
        Route::get('/reports/leaves', [ReportController::class, 'leaves'])
            ->name('reports.leaves');

        // 3. Lembur
        Route::get('/reports/overtimes', [ReportController::class, 'overtimes'])
            ->name('reports.overtimes');

        // 4. Payroll
        Route::get('/reports/payrolls', [ReportController::class, 'payrolls'])
            ->name('reports.payrolls');

        // 5. Turnover Karyawan
        Route::get('/reports/turnover', [ReportController::class, 'turnover'])
            ->name('reports.turnover');

        // 6. Audit Trail (Requires view_audit_logs permission)
        Route::middleware('permission:view_audit_logs')->group(function () {
            Route::get('/reports/audit-logs', [ReportController::class, 'auditLogs'])
                ->name('reports.audit-logs');
        });

        // Export routes (Requires export_reports permission & excludes taiwan_account)
        Route::middleware(['role:super_admin,manager_hrd', 'permission:export_reports'])->group(function () {
            Route::get('/reports/attendances/export', [ReportController::class, 'exportAttendances'])
                ->name('reports.attendances.export');
            Route::get('/reports/leaves/export', [ReportController::class, 'exportLeaves'])
                ->name('reports.leaves.export');
            Route::get('/reports/overtimes/export', [ReportController::class, 'exportOvertimes'])
                ->name('reports.overtimes.export');
            Route::get('/reports/payrolls/export', [ReportController::class, 'exportPayrolls'])
                ->name('reports.payrolls.export');
            Route::get('/reports/turnover/export', [ReportController::class, 'exportTurnover'])
                ->name('reports.turnover.export');
            Route::get('/reports/audit-logs/export', [ReportController::class, 'exportAuditLogs'])
                ->name('reports.audit-logs.export');
        });
    });

    // Audit Trail Module
    Route::middleware('permission:view_audit_logs')->group(function () {
        Route::get('/audit-logs', [AuditLogController::class, 'index'])
            ->name('audit-logs.index');
        Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show'])
            ->name('audit-logs.show');
    });

    // Aktivitas File / Shared Files Module
    Route::middleware('permission:view_shared_files')->group(function () {
        Route::get('/shared-files', [SharedFileController::class, 'index'])
            ->name('shared-files.index');
        Route::get('/shared-files/{sharedFile}', [SharedFileController::class, 'show'])
            ->name('shared-files.show');
        Route::get('/shared-files/{sharedFile}/download', [SharedFileController::class, 'download'])
            ->name('shared-files.download');

        Route::middleware('permission:manage_shared_files')->group(function () {
            Route::post('/shared-files', [SharedFileController::class, 'store'])
                ->name('shared-files.store');
            Route::put('/shared-files/{sharedFile}', [SharedFileController::class, 'update'])
                ->name('shared-files.update');
            Route::post('/shared-files/{sharedFile}/share', [SharedFileController::class, 'share'])
                ->name('shared-files.share');
            Route::post('/shared-files/{sharedFile}/revoke', [SharedFileController::class, 'revoke'])
                ->name('shared-files.revoke');
            Route::delete('/shared-files/{sharedFile}', [SharedFileController::class, 'destroy'])
                ->name('shared-files.destroy');
        });
    });

    // User Management (Super Admin only)
    Route::middleware(['role:super_admin', 'permission:manage_users'])->group(function () {
        Route::get('/users', [UserController::class, 'index'])
            ->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])
            ->name('users.create');
        Route::post('/users', [UserController::class, 'store'])
            ->name('users.store');
        Route::get('/users/{user}', [UserController::class, 'show'])
            ->name('users.show');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])
            ->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])
            ->name('users.update');
        Route::post('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])
            ->name('users.toggle-status');
    });

    // Role & Permission Management (Super Admin only)
    Route::middleware(['role:super_admin', 'permission:manage_roles'])->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])
            ->name('roles.index');
        Route::get('/roles/{role}', [RoleController::class, 'show'])
            ->name('roles.show');
        Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])
            ->name('roles.edit');
        Route::put('/roles/{role}', [RoleController::class, 'update'])
            ->name('roles.update');
    });

    // Settings Module (Profile & System Info for all authenticated users)
    Route::get('/settings', [SettingController::class, 'index'])
        ->name('settings.index');
    Route::put('/settings/profile', [SettingController::class, 'updateProfile'])
        ->name('settings.profile');
    Route::put('/settings/password', [SettingController::class, 'updatePassword'])
        ->name('settings.password');
});
