<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = Role::where('name', 'super_admin')->first();
        $managerHrd = Role::where('name', 'manager_hrd')->first();
        $taiwanAccount = Role::where('name', 'taiwan_account')->first();

        User::create([
            'role_id' => $superAdmin->id,
            'name' => 'Super Admin',
            'email' => 'admin@aranus.local',
            'password' => 'password',
            'status' => 'active',
        ]);

        User::create([
            'role_id' => $managerHrd->id,
            'name' => 'Manager HRD',
            'email' => 'hrd@aranus.local',
            'password' => 'password',
            'status' => 'active',
        ]);

        User::create([
            'role_id' => $taiwanAccount->id,
            'name' => 'Taiwan Account',
            'email' => 'taiwan@aranus.local',
            'password' => 'password',
            'status' => 'active',
        ]);
    }
}
