<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected User $superAdmin;

    protected User $managerHrd;

    protected User $taiwanAccount;

    protected Role $superAdminRole;

    protected Role $managerHrdRole;

    protected Role $taiwanRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdminRole = Role::where('name', 'super_admin')->first();
        $this->managerHrdRole = Role::where('name', 'manager_hrd')->first();
        $this->taiwanRole = Role::where('name', 'taiwan_account')->first();

        $this->superAdmin = User::where('email', 'admin@aranus.local')->first();
        $this->managerHrd = User::where('email', 'hrd@aranus.local')->first();
        $this->taiwanAccount = User::where('email', 'taiwan@aranus.local')->first();
    }

    public function test_super_admin_can_access_all_admin_modules(): void
    {
        $log = AuditLog::create([
            'user_id' => $this->superAdmin->id,
            'action' => 'test_action',
            'entity_type' => User::class,
            'created_at' => now(),
        ]);

        $this->actingAs($this->superAdmin)->get('/users')->assertOk();
        $this->actingAs($this->superAdmin)->get('/users/create')->assertOk();
        $this->actingAs($this->superAdmin)->get('/users/'.$this->superAdmin->id)->assertOk();
        $this->actingAs($this->superAdmin)->get('/users/'.$this->superAdmin->id.'/edit')->assertOk();

        $this->actingAs($this->superAdmin)->get('/roles')->assertOk();
        $this->actingAs($this->superAdmin)->get('/roles/'.$this->superAdminRole->id)->assertOk();
        $this->actingAs($this->superAdmin)->get('/roles/'.$this->superAdminRole->id.'/edit')->assertOk();

        $this->actingAs($this->superAdmin)->get('/audit-logs')->assertOk();
        $this->actingAs($this->superAdmin)->get('/audit-logs/'.$log->id)->assertOk();

        $this->actingAs($this->superAdmin)->get('/settings')->assertOk();
    }

    public function test_manager_hrd_has_audit_logs_and_settings_but_cannot_access_user_or_role_management(): void
    {
        $log = AuditLog::create([
            'user_id' => $this->managerHrd->id,
            'action' => 'test_action_hrd',
            'entity_type' => User::class,
            'created_at' => now(),
        ]);

        // Forbidden on Users and Roles
        $this->actingAs($this->managerHrd)->get('/users')->assertForbidden();
        $this->actingAs($this->managerHrd)->get('/users/create')->assertForbidden();
        $this->actingAs($this->managerHrd)->get('/roles')->assertForbidden();
        $this->actingAs($this->managerHrd)->get('/roles/'.$this->managerHrdRole->id.'/edit')->assertForbidden();

        // Allowed on Audit Logs (has view_audit_logs) and Settings
        $this->actingAs($this->managerHrd)->get('/audit-logs')->assertOk();
        $this->actingAs($this->managerHrd)->get('/audit-logs/'.$log->id)->assertOk();
        $this->actingAs($this->managerHrd)->get('/settings')->assertOk();
    }

    public function test_taiwan_account_cannot_access_users_roles_or_audit_logs_but_can_access_settings(): void
    {
        $log = AuditLog::create([
            'user_id' => $this->superAdmin->id,
            'action' => 'test_taiwan_forbidden',
            'entity_type' => User::class,
            'created_at' => now(),
        ]);

        $this->actingAs($this->taiwanAccount)->get('/users')->assertForbidden();
        $this->actingAs($this->taiwanAccount)->get('/roles')->assertForbidden();
        $this->actingAs($this->taiwanAccount)->get('/audit-logs')->assertForbidden();
        $this->actingAs($this->taiwanAccount)->get('/audit-logs/'.$log->id)->assertForbidden();

        // Settings is available for own profile
        $this->actingAs($this->taiwanAccount)->get('/settings')->assertOk();
    }

    public function test_super_admin_can_create_user_with_validation_and_audit_log(): void
    {
        $response = $this->actingAs($this->superAdmin)->post('/users', [
            'name' => 'Staf HR Baru',
            'email' => 'stafbaru@aranus.local',
            'role_id' => $this->managerHrdRole->id,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'status' => 'active',
        ]);

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', [
            'name' => 'Staf HR Baru',
            'email' => 'stafbaru@aranus.local',
            'role_id' => $this->managerHrdRole->id,
            'status' => 'active',
        ]);

        $newUser = User::where('email', 'stafbaru@aranus.local')->first();
        $this->assertTrue(Hash::check('password123', $newUser->password));

        // Audit log created and does not contain plain text password
        $audit = AuditLog::where('action', 'user_created')->where('entity_id', $newUser->id)->first();
        $this->assertNotNull($audit);
        $this->assertArrayNotHasKey('password', $audit->new_values ?? []);
        $this->assertEquals('stafbaru@aranus.local', $audit->new_values['email']);
    }

    public function test_user_creation_fails_if_email_is_duplicate_or_password_too_short(): void
    {
        $response = $this->actingAs($this->superAdmin)->post('/users', [
            'name' => 'User Duplikat',
            'email' => 'admin@aranus.local', // duplicate
            'role_id' => $this->managerHrdRole->id,
            'password' => 'short',
            'password_confirmation' => 'short',
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors(['email', 'password']);
    }

    public function test_super_admin_can_update_user_and_records_audit_log(): void
    {
        $user = User::factory()->create([
            'role_id' => $this->managerHrdRole->id,
            'name' => 'User Lama',
            'email' => 'userlama@aranus.local',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->superAdmin)->put('/users/'.$user->id, [
            'name' => 'User Telah Diubah',
            'email' => 'userlama@aranus.local',
            'role_id' => $this->managerHrdRole->id,
            'status' => 'active',
        ]);

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'User Telah Diubah',
        ]);

        $audit = AuditLog::where('action', 'user_updated')->where('entity_id', $user->id)->first();
        $this->assertNotNull($audit);
        $this->assertEquals('User Lama', $audit->old_values['name']);
        $this->assertEquals('User Telah Diubah', $audit->new_values['name']);
        $this->assertArrayNotHasKey('password', $audit->new_values ?? []);
    }

    public function test_super_admin_cannot_deactivate_themselves(): void
    {
        $response = $this->actingAs($this->superAdmin)->post('/users/'.$this->superAdmin->id.'/toggle-status');

        $response->assertSessionHas('error');
        $this->assertEquals('active', $this->superAdmin->fresh()->status);
    }

    public function test_super_admin_cannot_deactivate_the_last_active_super_admin(): void
    {
        // Only one super admin currently
        $response = $this->actingAs($this->superAdmin)->put('/users/'.$this->superAdmin->id, [
            'name' => $this->superAdmin->name,
            'email' => $this->superAdmin->email,
            'role_id' => $this->superAdminRole->id,
            'status' => 'inactive',
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals('active', $this->superAdmin->fresh()->status);
    }

    public function test_super_admin_can_toggle_status_of_other_users(): void
    {
        $this->assertEquals('active', $this->managerHrd->status);

        $response = $this->actingAs($this->superAdmin)->post('/users/'.$this->managerHrd->id.'/toggle-status');
        $response->assertSessionHas('success');
        $this->assertEquals('inactive', $this->managerHrd->fresh()->status);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user_deactivated',
            'entity_id' => $this->managerHrd->id,
        ]);

        // Toggle back to active
        $response = $this->actingAs($this->superAdmin)->post('/users/'.$this->managerHrd->id.'/toggle-status');
        $response->assertSessionHas('success');
        $this->assertEquals('active', $this->managerHrd->fresh()->status);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user_activated',
            'entity_id' => $this->managerHrd->id,
        ]);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $this->managerHrd->update(['status' => 'inactive']);

        $response = $this->post('/login', [
            'email' => 'hrd@aranus.local',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_super_admin_can_update_role_permissions_and_super_admin_critical_permissions_are_protected(): void
    {
        $viewAttendancePerm = Permission::where('name', 'view_attendance')->first();

        // Updating taiwan_account permissions
        $response = $this->actingAs($this->superAdmin)->put('/roles/'.$this->taiwanRole->id, [
            'description' => 'Role peninjau dari Taiwan',
            'permissions' => [$viewAttendancePerm->id],
        ]);

        $response->assertRedirect('/roles/'.$this->taiwanRole->id);
        $this->assertEquals(['view_attendance'], $this->taiwanRole->fresh()->permissions->pluck('name')->toArray());

        // Updating super_admin without passing manage_roles or view_dashboard
        // Critical permissions must be preserved automatically
        $response = $this->actingAs($this->superAdmin)->put('/roles/'.$this->superAdminRole->id, [
            'description' => 'Akun Super Administrator Utama',
            'permissions' => [$viewAttendancePerm->id],
        ]);

        $superPerms = $this->superAdminRole->fresh()->permissions->pluck('name');
        $this->assertTrue($superPerms->contains('manage_roles'));
        $this->assertTrue($superPerms->contains('manage_users'));
        $this->assertTrue($superPerms->contains('view_dashboard'));
        $this->assertTrue($superPerms->contains('view_audit_logs'));
        $this->assertTrue($superPerms->contains('view_attendance'));
    }

    public function test_user_can_update_own_profile_in_settings(): void
    {
        $response = $this->actingAs($this->managerHrd)->put('/settings/profile', [
            'name' => 'Manager HRD Updated',
            'email' => 'hrd_new@aranus.local',
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals('Manager HRD Updated', $this->managerHrd->fresh()->name);
        $this->assertEquals('hrd_new@aranus.local', $this->managerHrd->fresh()->email);
        // Role cannot be changed from settings
        $this->assertEquals('manager_hrd', $this->managerHrd->fresh()->role->name);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->managerHrd->id,
            'action' => 'profile_updated',
        ]);
    }

    public function test_user_can_change_password_with_correct_current_password(): void
    {
        $response = $this->actingAs($this->managerHrd)->put('/settings/password', [
            'current_password' => 'password',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHas('success');
        $this->assertTrue(Hash::check('newpassword123', $this->managerHrd->fresh()->password));

        $audit = AuditLog::where('action', 'password_changed')->where('user_id', $this->managerHrd->id)->first();
        $this->assertNotNull($audit);
        $this->assertArrayNotHasKey('password', $audit->new_values ?? []);
    }

    public function test_user_cannot_change_password_with_wrong_current_password(): void
    {
        $response = $this->actingAs($this->managerHrd)->put('/settings/password', [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('password', $this->managerHrd->fresh()->password));
    }

    public function test_login_and_logout_audit_logging(): void
    {
        $this->post('/login', [
            'email' => 'admin@aranus.local',
            'password' => 'password',
        ])->assertRedirect('/dashboard');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->superAdmin->id,
            'action' => 'user_login',
        ]);

        $this->post('/logout')->assertRedirect('/login');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->superAdmin->id,
            'action' => 'user_logout',
        ]);
    }

    public function test_audit_logs_filters(): void
    {
        AuditLog::create([
            'user_id' => $this->superAdmin->id,
            'action' => 'custom_filter_action',
            'entity_type' => User::class,
            'created_at' => now()->subDays(2),
        ]);

        AuditLog::create([
            'user_id' => $this->managerHrd->id,
            'action' => 'another_filter_action',
            'entity_type' => Employee::class,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->superAdmin)->get('/audit-logs?action=custom_filter_action');
        $response->assertOk();
        $logs = $response->viewData('auditLogs');
        $this->assertTrue($logs->contains('action', 'custom_filter_action'));
        $this->assertFalse($logs->contains('action', 'another_filter_action'));

        $responseUser = $this->actingAs($this->superAdmin)->get('/audit-logs?user_id='.$this->managerHrd->id);
        $responseUser->assertOk();
        $userLogs = $responseUser->viewData('auditLogs');
        $this->assertTrue($userLogs->contains('action', 'another_filter_action'));
        $this->assertFalse($userLogs->contains('action', 'custom_filter_action'));
    }
}
