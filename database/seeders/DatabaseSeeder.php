<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            UserSeeder::class,
        ]);

        $superAdmin = Role::where('name', 'super_admin')->first();
        $managerHrd = Role::where('name', 'manager_hrd')->first();
        $taiwanAccount = Role::where('name', 'taiwan_account')->first();

        $permissions = Permission::all();

        // Super Admin mendapatkan semua permission
        $superAdmin->permissions()->sync(
            $permissions->pluck('id')
        );

        // Manager/HRD
        $managerPermissions = Permission::whereIn('name', [
            'view_dashboard',
            'manage_employees',
            'view_attendance',
            'manage_leave',
            'approve_leave',
            'manage_overtime',
            'approve_overtime',
            'manage_payroll',
            'view_reports',
            'export_reports',
            'view_shared_files',
            'manage_shared_files',
            'view_audit_logs',
        ])->pluck('id');

        $managerHrd->permissions()->sync(
            $managerPermissions
        );

        // Taiwan Account hanya viewer
        $taiwanPermissions = Permission::whereIn('name', [
            'view_dashboard',
            'view_attendance',
            'view_reports',
            'view_shared_files',
        ])->pluck('id');

        $taiwanAccount->permissions()->sync(
            $taiwanPermissions
        );
    }
}
