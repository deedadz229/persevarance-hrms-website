<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(
            ['name' => 'super_admin'],
            ['description' => 'Memiliki akses penuh terhadap sistem.']
        );

        Role::firstOrCreate(
            ['name' => 'manager_hrd'],
            ['description' => 'Mengelola dan memantau proses HRD.']
        );

        Role::firstOrCreate(
            ['name' => 'taiwan_account'],
            ['description' => 'Memiliki akses sebagai viewer untuk monitoring dan laporan.']
        );

        Role::firstOrCreate(
            ['name' => 'employee'],
            ['description' => 'Akses pengguna karyawan mobile/portal.']
        );
    }
}
