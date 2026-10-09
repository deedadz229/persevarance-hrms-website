<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            [
                'name' => 'view_dashboard',
                'description' => 'Melihat dashboard sistem.',
            ],
            [
                'name' => 'manage_employees',
                'description' => 'Mengelola data karyawan.',
            ],
            [
                'name' => 'view_attendance',
                'description' => 'Melihat data kehadiran karyawan.',
            ],
            [
                'name' => 'manage_leave',
                'description' => 'Mengelola pengajuan cuti.',
            ],
            [
                'name' => 'approve_leave',
                'description' => 'Menyetujui atau menolak pengajuan cuti.',
            ],
            [
                'name' => 'manage_overtime',
                'description' => 'Mengelola data lembur.',
            ],
            [
                'name' => 'approve_overtime',
                'description' => 'Menyetujui atau menolak lembur.',
            ],
            [
                'name' => 'manage_payroll',
                'description' => 'Mengelola data payroll dan slip gaji.',
            ],
            [
                'name' => 'view_reports',
                'description' => 'Melihat dan mengakses laporan.',
            ],
            [
                'name' => 'export_reports',
                'description' => 'Melakukan export laporan.',
            ],
            [
                'name' => 'manage_users',
                'description' => 'Mengelola akun pengguna website.',
            ],
            [
                'name' => 'manage_roles',
                'description' => 'Mengelola role dan permission.',
            ],
            [
                'name' => 'view_shared_files',
                'description' => 'Melihat file yang dibagikan.',
            ],
            [
                'name' => 'manage_shared_files',
                'description' => 'Mengelola file yang dibagikan.',
            ],
            [
                'name' => 'view_audit_logs',
                'description' => 'Melihat riwayat aktivitas sistem.',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }
    }
}
