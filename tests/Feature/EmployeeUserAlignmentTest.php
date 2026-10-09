<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeUserAlignmentTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function getSuperAdmin(): User
    {
        $role = Role::where('name', 'super_admin')->firstOrFail();

        return User::where('role_id', $role->id)->firstOrFail();
    }

    private function getDepartmentAndPosition(): array
    {
        $dept = Department::factory()->create([
            'name' => 'Technology',
            'status' => 'active',
        ]);

        $pos = Position::factory()->create([
            'department_id' => $dept->id,
            'name' => 'Software Developer',
            'status' => 'active',
        ]);

        return [$dept, $pos];
    }

    public function test_role_employee_exists_and_cannot_access_hrd_routes(): void
    {
        $employeeRole = Role::where('name', 'employee')->first();
        $this->assertNotNull($employeeRole);

        $employeeUser = User::factory()->create([
            'role_id' => $employeeRole->id,
            'status' => 'active',
        ]);

        // Employee should NOT have access to employees.create
        $this->actingAs($employeeUser)->get(route('employees.create'))->assertForbidden();

        // Employee should NOT have access to payrolls.index
        $this->actingAs($employeeUser)->get(route('payrolls.index'))->assertForbidden();

        // Employee should NOT have access to users.index
        $this->actingAs($employeeUser)->get(route('users.index'))->assertForbidden();

        // Employee should NOT have access to audit-logs.index
        $this->actingAs($employeeUser)->get(route('audit-logs.index'))->assertForbidden();
    }

    public function test_users_employee_id_is_unique_when_not_null(): void
    {
        $superAdmin = $this->getSuperAdmin();
        [$dept, $pos] = $this->getDepartmentAndPosition();

        $employee = Employee::factory()->create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
        ]);

        $role = Role::where('name', 'employee')->firstOrFail();

        // 1. Linking first user to employee succeeds
        $user1 = User::create([
            'name' => 'User One',
            'email' => 'user1@example.com',
            'role_id' => $role->id,
            'employee_id' => $employee->id,
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('users', ['id' => $user1->id, 'employee_id' => $employee->id]);

        // 2. Linking second user via UserController store to SAME employee must fail validation
        $response = $this->actingAs($superAdmin)->post(route('users.store'), [
            'name' => 'User Two',
            'email' => 'user2@example.com',
            'role_id' => $role->id,
            'employee_id' => $employee->id,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors(['employee_id']);

        // 3. Multiple users with null employee_id can exist without collision
        $nullUser1 = User::factory()->create(['employee_id' => null]);
        $nullUser2 = User::factory()->create(['employee_id' => null]);
        $this->assertNull($nullUser1->employee_id);
        $this->assertNull($nullUser2->employee_id);
    }

    public function test_create_employee_without_account_and_without_photo(): void
    {
        $superAdmin = $this->getSuperAdmin();
        [$dept, $pos] = $this->getDepartmentAndPosition();

        $response = $this->actingAs($superAdmin)->post(route('employees.store'), [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'phone' => '08123456789',
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'join_date' => '2026-01-15',
            'employment_type' => 'permanent',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('employees.index'));
        $this->assertDatabaseHas('employees', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'photo_path' => null,
        ]);

        $employee = Employee::where('email', 'budi@example.com')->firstOrFail();
        $this->assertNull($employee->user);
    }

    public function test_create_employee_with_profile_photo_and_user_account(): void
    {
        Storage::fake('public');
        $superAdmin = $this->getSuperAdmin();
        [$dept, $pos] = $this->getDepartmentAndPosition();

        $photo = UploadedFile::fake()->image('profile.jpg', 300, 300);

        $response = $this->actingAs($superAdmin)->post(route('employees.store'), [
            'name' => 'Siti Nurhaliza',
            'email' => 'siti@example.com',
            'phone' => '08987654321',
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'join_date' => '2026-02-01',
            'employment_type' => 'contract',
            'status' => 'active',
            'photo' => $photo,
            'create_account' => '1',
            'account_password' => 'secret1234',
            'account_password_confirmation' => 'secret1234',
        ]);

        $response->assertRedirect(route('employees.index'));

        $employee = Employee::where('email', 'siti@example.com')->firstOrFail();
        $this->assertNotNull($employee->photo_path);
        $this->assertEquals(1, $employee->photo_version);
        Storage::disk('public')->assertExists($employee->photo_path);

        // Check user account strictly has the same email as employee
        $user = $employee->user;
        $this->assertNotNull($user);
        $this->assertEquals('siti@example.com', $user->email);
        $this->assertEquals('employee', $user->role->name);
        $this->assertTrue(Hash::check('secret1234', $user->password));

        // Check audit log
        $audit = AuditLog::where('action', 'employee_account_created')->firstOrFail();
        $this->assertEquals($user->id, $audit->entity_id);
        $this->assertArrayNotHasKey('password', $audit->new_values);
    }

    public function test_create_employee_fails_and_rolls_back_if_user_email_already_taken(): void
    {
        Storage::fake('public');
        $superAdmin = $this->getSuperAdmin();
        [$dept, $pos] = $this->getDepartmentAndPosition();

        User::factory()->create(['email' => 'alreadytaken@example.com']);

        $photo = UploadedFile::fake()->image('profile.png');

        $response = $this->actingAs($superAdmin)->post(route('employees.store'), [
            'name' => 'Ahmad Dani',
            'email' => 'alreadytaken@example.com',
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'join_date' => '2026-03-01',
            'employment_type' => 'permanent',
            'status' => 'active',
            'photo' => $photo,
            'create_account' => '1',
            'account_password' => 'password123',
            'account_password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertDatabaseMissing('employees', ['name' => 'Ahmad Dani']);
    }

    public function test_update_employee_replaces_photo_and_increments_version(): void
    {
        Storage::fake('public');
        $superAdmin = $this->getSuperAdmin();
        [$dept, $pos] = $this->getDepartmentAndPosition();

        $oldPhoto = UploadedFile::fake()->image('old.jpg');
        $oldPath = $oldPhoto->store('employees', 'public');

        $employee = Employee::factory()->create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'photo_path' => $oldPath,
            'photo_version' => 1,
        ]);

        $newPhoto = UploadedFile::fake()->image('new.jpg');

        $response = $this->actingAs($superAdmin)->put(route('employees.update', $employee), [
            'employee_code' => $employee->employee_code,
            'name' => $employee->name,
            'email' => $employee->email,
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'join_date' => '2026-01-01',
            'employment_type' => 'permanent',
            'status' => 'active',
            'photo' => $newPhoto,
        ]);

        $response->assertRedirect(route('employees.show', $employee));

        $employee->refresh();
        $this->assertNotEquals($oldPath, $employee->photo_path);
        $this->assertEquals(2, $employee->photo_version);
        Storage::disk('public')->assertExists($employee->photo_path);
        Storage::disk('public')->assertMissing($oldPath);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'employee_photo_updated',
            'entity_id' => $employee->id,
        ]);
    }

    public function test_update_employee_delete_photo_removes_file_and_increments_version(): void
    {
        Storage::fake('public');
        $superAdmin = $this->getSuperAdmin();
        [$dept, $pos] = $this->getDepartmentAndPosition();

        $oldPhoto = UploadedFile::fake()->image('old.png');
        $oldPath = $oldPhoto->store('employees', 'public');

        $employee = Employee::factory()->create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'photo_path' => $oldPath,
            'photo_version' => 1,
        ]);

        $response = $this->actingAs($superAdmin)->put(route('employees.update', $employee), [
            'employee_code' => $employee->employee_code,
            'name' => $employee->name,
            'email' => $employee->email,
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'join_date' => '2026-01-01',
            'employment_type' => 'permanent',
            'status' => 'active',
            'delete_photo' => '1',
        ]);

        $response->assertRedirect(route('employees.show', $employee));

        $employee->refresh();
        $this->assertNull($employee->photo_path);
        $this->assertEquals(2, $employee->photo_version);
        Storage::disk('public')->assertMissing($oldPath);
    }

    public function test_update_employee_with_existing_user_updates_account_without_duplicate(): void
    {
        $superAdmin = $this->getSuperAdmin();
        [$dept, $pos] = $this->getDepartmentAndPosition();

        $employee = Employee::factory()->create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'email' => 'old.employee@example.com',
        ]);

        $role = Role::where('name', 'employee')->firstOrFail();
        $user = User::create([
            'name' => $employee->name,
            'email' => 'old.employee@example.com',
            'role_id' => $role->id,
            'employee_id' => $employee->id,
            'password' => Hash::make('oldpassword123'),
            'status' => 'active',
        ]);

        // Updating employee email automatically syncs user email to match
        $response = $this->actingAs($superAdmin)->put(route('employees.update', $employee), [
            'employee_code' => $employee->employee_code,
            'name' => 'Updated Name',
            'email' => 'new.employee@example.com',
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'join_date' => '2026-01-01',
            'employment_type' => 'permanent',
            'status' => 'active',
            'account_password' => '',
            'account_password_confirmation' => '',
        ]);

        $response->assertRedirect(route('employees.show', $employee));

        $user->refresh();
        $this->assertEquals('new.employee@example.com', $user->email);
        $this->assertEquals('Updated Name', $user->name);
        $this->assertTrue(Hash::check('oldpassword123', $user->password));
        $this->assertEquals(1, User::where('employee_id', $employee->id)->count());
    }

    public function test_user_cannot_be_created_with_different_email_than_linked_employee(): void
    {
        $superAdmin = $this->getSuperAdmin();
        [$dept, $pos] = $this->getDepartmentAndPosition();

        $employee = Employee::factory()->create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'email' => 'emp.strict@example.com',
        ]);

        $role = Role::where('name', 'employee')->firstOrFail();

        $response = $this->actingAs($superAdmin)->post(route('users.store'), [
            'name' => 'Mismatched User',
            'email' => 'mismatched@example.com',
            'role_id' => $role->id,
            'employee_id' => $employee->id,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertDatabaseMissing('users', ['email' => 'mismatched@example.com']);
    }

    public function test_user_can_be_created_with_matching_email_as_linked_employee(): void
    {
        $superAdmin = $this->getSuperAdmin();
        [$dept, $pos] = $this->getDepartmentAndPosition();

        $employee = Employee::factory()->create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'email' => 'emp.matched@example.com',
        ]);

        $role = Role::where('name', 'employee')->firstOrFail();

        $response = $this->actingAs($superAdmin)->post(route('users.store'), [
            'name' => 'Matched User',
            'email' => 'emp.matched@example.com',
            'role_id' => $role->id,
            'employee_id' => $employee->id,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'emp.matched@example.com',
            'employee_id' => $employee->id,
        ]);
    }

    public function test_user_cannot_be_updated_with_different_email_than_linked_employee(): void
    {
        $superAdmin = $this->getSuperAdmin();
        [$dept, $pos] = $this->getDepartmentAndPosition();

        $employee = Employee::factory()->create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'email' => 'emp.update@example.com',
        ]);

        $role = Role::where('name', 'employee')->firstOrFail();
        $user = User::create([
            'name' => 'Initial User',
            'email' => 'emp.update@example.com',
            'role_id' => $role->id,
            'employee_id' => $employee->id,
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $response = $this->actingAs($superAdmin)->put(route('users.update', $user), [
            'name' => 'Initial User',
            'email' => 'different.update@example.com',
            'role_id' => $role->id,
            'employee_id' => $employee->id,
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors(['email']);
        $user->refresh();
        $this->assertEquals('emp.update@example.com', $user->email);
    }

    public function test_user_without_employee_can_use_any_valid_email(): void
    {
        $superAdmin = $this->getSuperAdmin();
        $role = Role::where('name', 'manager_hrd')->firstOrFail();

        $response = $this->actingAs($superAdmin)->post(route('users.store'), [
            'name' => 'Standalone Manager',
            'email' => 'standalone.manager@example.com',
            'role_id' => $role->id,
            'employee_id' => null,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'standalone.manager@example.com',
            'employee_id' => null,
        ]);
    }

    public function test_update_employee_email_fails_if_new_email_collides_with_another_user(): void
    {
        $superAdmin = $this->getSuperAdmin();
        [$dept, $pos] = $this->getDepartmentAndPosition();

        User::factory()->create(['email' => 'occupied@example.com']);

        $employee = Employee::factory()->create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'email' => 'emp.free@example.com',
        ]);

        $role = Role::where('name', 'employee')->firstOrFail();
        User::create([
            'name' => $employee->name,
            'email' => 'emp.free@example.com',
            'role_id' => $role->id,
            'employee_id' => $employee->id,
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $response = $this->actingAs($superAdmin)->put(route('employees.update', $employee), [
            'employee_code' => $employee->employee_code,
            'name' => $employee->name,
            'email' => 'occupied@example.com',
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'join_date' => '2026-01-01',
            'employment_type' => 'permanent',
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors(['email']);
        $employee->refresh();
        $this->assertEquals('emp.free@example.com', $employee->email);
    }

    public function test_employee_show_and_index_views_display_photo_and_account_status(): void
    {
        Storage::fake('public');
        $superAdmin = $this->getSuperAdmin();
        [$dept, $pos] = $this->getDepartmentAndPosition();

        $photo = UploadedFile::fake()->image('avatar.jpg');
        $path = $photo->store('employees', 'public');

        $employee = Employee::factory()->create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'photo_path' => $path,
            'photo_version' => 1,
        ]);

        $role = Role::where('name', 'employee')->firstOrFail();
        User::create([
            'name' => $employee->name,
            'email' => 'linked@example.com',
            'role_id' => $role->id,
            'employee_id' => $employee->id,
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        // Index page should show image
        $indexResponse = $this->actingAs($superAdmin)->get(route('employees.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('/storage/'.$path);

        // Show page should show image and "Terdaftar"
        $showResponse = $this->actingAs($superAdmin)->get(route('employees.show', $employee));
        $showResponse->assertOk();
        $showResponse->assertSee('/storage/'.$path);
        $showResponse->assertSee('Terdaftar');
        $showResponse->assertSee('linked@example.com');
    }
}
