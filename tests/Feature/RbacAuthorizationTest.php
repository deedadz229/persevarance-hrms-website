<?php

namespace Tests\Feature;

use App\Http\Middleware\PermissionMiddleware;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase as BaseTestCase;

class RbacAuthorizationTest extends BaseTestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_super_admin_can_open_administrative_route(): void
    {
        $superAdmin = $this->userForRole('super_admin');

        $response = $this->actingAs($superAdmin)
            ->get('/rbac-test');

        $response->assertOk();

        $this->actingAs($superAdmin)
            ->get('/departments')
            ->assertOk();
    }

    public function test_management_can_open_dashboard_but_not_super_admin_configuration(): void
    {
        $management = $this->userForRole('manager_hrd');

        $this->actingAs($management)
            ->get('/dashboard')
            ->assertOk();

        $this->actingAs($management)
            ->get('/rbac-test')
            ->assertForbidden();
    }

    public function test_taiwan_account_can_view_dashboard_but_cannot_create_or_modify_configuration(): void
    {
        $taiwanAccount = $this->userForRole('taiwan_account');
        $department = Department::query()->create([
            'name' => 'Departemen Test',
            'status' => 'active',
        ]);

        $this->actingAs($taiwanAccount)
            ->get('/dashboard')
            ->assertOk();

        $this->actingAs($taiwanAccount)
            ->post('/departments', [
                'name' => 'Tidak Boleh Dibuat',
                'status' => 'active',
            ])
            ->assertForbidden();

        $this->actingAs($taiwanAccount)
            ->get('/departments/create')
            ->assertForbidden();

        $this->actingAs($taiwanAccount)
            ->put("/departments/{$department->id}", [
                'name' => 'Tidak Boleh Diubah',
                'status' => 'active',
            ])
            ->assertForbidden();

        $this->actingAs($taiwanAccount)
            ->delete("/departments/{$department->id}")
            ->assertForbidden();
    }

    public function test_user_without_required_permission_receives_forbidden_response(): void
    {
        $user = new User(['name' => 'Without Permission', 'email' => 'without-permission@example.test']);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertForbidden();
    }

    public function test_taiwan_account_cannot_approve_leave(): void
    {
        $request = Request::create('/leave-requests/1/approve', 'POST');
        $request->setUserResolver(fn (): User => $this->userForRole('taiwan_account'));

        try {
            (new PermissionMiddleware)->handle(
                $request,
                fn (): Response => new Response,
                'approve_leave'
            );

            $this->fail('Taiwan Account must not receive approve_leave permission.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_final_role_permission_matrix_and_labels_are_correct(): void
    {
        $allPermissions = [
            'view_dashboard', 'manage_employees', 'view_attendance', 'manage_leave',
            'approve_leave', 'manage_overtime', 'approve_overtime', 'manage_payroll',
            'view_reports', 'export_reports', 'manage_users', 'manage_roles',
            'view_shared_files', 'manage_shared_files', 'view_audit_logs',
        ];

        $managementPermissions = [
            'view_dashboard', 'manage_employees', 'view_attendance', 'manage_leave',
            'approve_leave', 'manage_overtime', 'approve_overtime', 'manage_payroll',
            'view_reports', 'export_reports', 'view_shared_files',
            'manage_shared_files', 'view_audit_logs',
        ];

        $taiwanPermissions = [
            'view_dashboard', 'view_attendance', 'view_reports', 'view_shared_files',
        ];

        $this->assertRolePermissions('super_admin', 'Super Admin', $allPermissions);
        $this->assertRolePermissions('manager_hrd', 'Management', $managementPermissions);
        $this->assertRolePermissions('taiwan_account', 'Taiwan Account', $taiwanPermissions);
    }

    public function test_each_role_can_log_in_and_only_sees_its_navigation(): void
    {
        $roles = [
            [
                'email' => 'admin@aranus.local',
                'label' => 'Super Admin',
                'visible' => ['Dashboard', 'Karyawan', 'Absensi', 'Cuti & Izin', 'Lembur', 'Penggajian', 'Laporan', 'Organisasi', 'User Management', 'Role & Permission', 'Audit Trail', 'Pengaturan'],
                'hidden' => ['View Employee', 'Kehadiran Karyawan'],
            ],
            [
                'email' => 'hrd@aranus.local',
                'label' => 'Management',
                'visible' => ['Dashboard', 'Karyawan', 'Absensi', 'Cuti & Izin', 'Lembur', 'Penggajian', 'Laporan', 'Organisasi'],
                'hidden' => ['User Management', 'Role & Permission', 'Audit Trail', 'Pengaturan', 'View Employee'],
            ],
            [
                'email' => 'taiwan@aranus.local',
                'label' => 'Taiwan Account',
                'visible' => ['Dashboard', 'View Employee', 'Departemen', 'Posisi', 'Kehadiran Karyawan', 'Karyawan Resign', 'Karyawan Lembur', 'Penggajian', 'Cuti & Izin', 'Laporan', 'Organisasi'],
                'hidden' => ['User Management', 'Role & Permission', 'Audit Trail', 'Pengaturan'],
            ],
        ];

        foreach ($roles as $role) {
            $this->post('/login', [
                'email' => $role['email'],
                'password' => 'password',
            ])->assertRedirect('/dashboard');

            $dashboard = $this->get('/dashboard')->assertOk();

            $dashboard->assertSee($role['label']);

            foreach ($role['visible'] as $menu) {
                $dashboard->assertSee($menu);
            }

            foreach ($role['hidden'] as $menu) {
                $dashboard->assertDontSee($menu);
            }

            $this->post('/logout')->assertRedirect('/login');
        }
    }

    private function userForRole(string $roleName): User
    {
        $role = Role::query()->where('name', $roleName)->firstOrFail();

        return User::query()->where('role_id', $role->id)->firstOrFail();
    }

    /** @param array<int, string> $expectedPermissions */
    private function assertRolePermissions(string $roleName, string $label, array $expectedPermissions): void
    {
        $role = Role::query()->where('name', $roleName)->firstOrFail();

        $this->assertSame($label, $role->label);
        $this->assertEqualsCanonicalizing(
            $expectedPermissions,
            $role->permissions()->pluck('name')->all()
        );
    }
}
