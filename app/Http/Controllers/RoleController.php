<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::withCount(['permissions', 'users'])
            ->orderBy('id')
            ->get();

        return view('roles.index', compact('roles'));
    }

    public function show(Role $role): View
    {
        $role->load(['permissions', 'users.employee']);

        $groupedPermissions = $this->groupPermissions($role->permissions);

        return view('roles.show', compact('role', 'groupedPermissions'));
    }

    public function edit(Role $role): View
    {
        $allPermissions = Permission::orderBy('name')->get();
        $assignedPermissionIds = $role->permissions()->pluck('permissions.id')->toArray();
        $groupedPermissions = $this->groupPermissions($allPermissions);

        return view('roles.edit', compact('role', 'groupedPermissions', 'assignedPermissionIds'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ], [
            'permissions.*.exists' => 'Permission yang dipilih tidak valid dalam sistem.',
        ]);

        $permissionIds = $validated['permissions'] ?? [];

        // Proteksi: Super Admin tidak boleh kehilangan permission administrasi kritis
        if ($role->name === 'super_admin') {
            $criticalPermissions = Permission::whereIn('name', [
                'view_dashboard',
                'manage_users',
                'manage_roles',
                'view_audit_logs',
            ])->pluck('id')->toArray();

            foreach ($criticalPermissions as $criticalId) {
                if (! in_array($criticalId, $permissionIds)) {
                    $permissionIds[] = $criticalId;
                }
            }
        }

        $oldPermissions = $role->permissions()->pluck('name')->sort()->values()->toArray();

        DB::transaction(function () use ($role, $validated, $permissionIds) {
            if (isset($validated['description'])) {
                $role->update(['description' => $validated['description']]);
            }
            $role->permissions()->sync($permissionIds);
        });

        $newPermissions = $role->fresh()->permissions()->pluck('name')->sort()->values()->toArray();

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'role_permissions_updated',
            'entity_type' => Role::class,
            'entity_id' => $role->id,
            'old_values' => ['permissions' => $oldPermissions],
            'new_values' => ['permissions' => $newPermissions],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()->route('roles.show', $role)
            ->with('success', "Hak akses permission untuk role {$role->label} berhasil diperbarui.");
    }

    /**
     * Group permissions logically for UI rendering.
     */
    private function groupPermissions($permissions)
    {
        return $permissions->groupBy(function ($permission) {
            $name = $permission->name;
            if (str_contains($name, 'dashboard')) {
                return 'Dashboard';
            }
            if (str_contains($name, 'employee')) {
                return 'Karyawan';
            }
            if (str_contains($name, 'attendance')) {
                return 'Kehadiran (Absensi)';
            }
            if (str_contains($name, 'leave')) {
                return 'Cuti & Izin';
            }
            if (str_contains($name, 'overtime')) {
                return 'Lembur';
            }
            if (str_contains($name, 'payroll')) {
                return 'Penggajian (Payroll)';
            }
            if (str_contains($name, 'report')) {
                return 'Laporan (Reports)';
            }
            if (str_contains($name, 'user') || str_contains($name, 'role')) {
                return 'Pengguna & Role';
            }
            if (str_contains($name, 'audit')) {
                return 'Audit Trail';
            }
            if (str_contains($name, 'shared_files')) {
                return 'Manajemen File';
            }

            return 'Lainnya';
        });
    }
}
