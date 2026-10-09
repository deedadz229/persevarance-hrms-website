<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentAndAutomaticEmployeeIdTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function userForRole(string $roleName): User
    {
        $role = Role::query()->where('name', $roleName)->firstOrFail();

        return User::query()->where('role_id', $role->id)->firstOrFail();
    }

    private function createDepartmentAndPosition(?string $deptName = 'Teknologi Informasi'): array
    {
        $dept = Department::query()->create([
            'name' => $deptName,
            'description' => 'Divisi pengembang teknologi',
            'status' => 'active',
        ]);

        $pos = Position::query()->create([
            'department_id' => $dept->id,
            'name' => 'Backend Developer',
            'status' => 'active',
        ]);

        return [$dept, $pos];
    }

    // ==========================================
    // PART A: DEPARTMENT MANAGEMENT TESTS
    // ==========================================

    public function test_1_department_list_displays_all_columns_and_counts(): void
    {
        $superAdmin = $this->userForRole('super_admin');
        [$dept, $pos] = $this->createDepartmentAndPosition('Finance & Accounting');

        Employee::query()->create([
            'employee_code' => '1',
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'name' => 'Karyawan Akuntansi',
            'email' => 'akuntansi@aranus.local',
            'join_date' => '2025-01-01',
            'employment_type' => 'permanent',
            'status' => 'active',
        ]);

        $response = $this->actingAs($superAdmin)->get(route('departments.index'));
        $response->assertOk();
        $response->assertSee('Finance & Accounting');
        $response->assertSee('Divisi pengembang teknologi');
        $response->assertSee('1 orang');
        $response->assertSee('Active');
    }

    public function test_2_department_search_works(): void
    {
        $superAdmin = $this->userForRole('super_admin');
        Department::query()->create(['name' => 'Marketing & Sales', 'status' => 'active']);
        Department::query()->create(['name' => 'Human Resources', 'status' => 'active']);

        $response = $this->actingAs($superAdmin)->get(route('departments.index', ['search' => 'Marketing']));
        $response->assertOk();
        $response->assertSee('Marketing & Sales');
        $response->assertDontSee('Human Resources');
    }

    public function test_3_department_status_filter_works(): void
    {
        $superAdmin = $this->userForRole('super_admin');
        Department::query()->create(['name' => 'Dept Aktif', 'status' => 'active']);
        Department::query()->create(['name' => 'Dept Nonaktif', 'status' => 'inactive']);

        $response = $this->actingAs($superAdmin)->get(route('departments.index', ['status' => 'inactive']));
        $response->assertOk();
        $response->assertSee('Dept Nonaktif');
        $response->assertDontSee('Dept Aktif');
    }

    public function test_4_super_admin_can_create_department(): void
    {
        $superAdmin = $this->userForRole('super_admin');

        $response = $this->actingAs($superAdmin)->post(route('departments.store'), [
            'name' => 'Operations & Logistics',
            'description' => 'Departemen operasional pergudangan',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('departments.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('departments', [
            'name' => 'Operations & Logistics',
            'description' => 'Departemen operasional pergudangan',
            'status' => 'active',
        ]);
    }

    public function test_5_duplicate_department_name_is_rejected(): void
    {
        $superAdmin = $this->userForRole('super_admin');
        Department::query()->create(['name' => 'Duplicate Dept', 'status' => 'active']);

        $response = $this->actingAs($superAdmin)->post(route('departments.store'), [
            'name' => 'Duplicate Dept',
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_6_super_admin_can_edit_department_and_ignore_own_unique(): void
    {
        $superAdmin = $this->userForRole('super_admin');
        $dept = Department::query()->create(['name' => 'Nama Lama', 'status' => 'active']);

        $response = $this->actingAs($superAdmin)->put(route('departments.update', $dept), [
            'name' => 'Nama Lama', // Sama tapi tidak dianggap duplicate
            'description' => 'Deskripsi diperbarui',
            'status' => 'inactive',
        ]);

        $response->assertRedirect(route('departments.index'));
        $this->assertDatabaseHas('departments', [
            'id' => $dept->id,
            'name' => 'Nama Lama',
            'description' => 'Deskripsi diperbarui',
            'status' => 'inactive',
        ]);
    }

    public function test_7_department_detail_shows_info_and_paginated_employees(): void
    {
        $superAdmin = $this->userForRole('super_admin');
        [$dept, $pos] = $this->createDepartmentAndPosition('Research & Development');

        $emp = Employee::query()->create([
            'employee_code' => '1',
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'name' => 'Peneliti Utama',
            'email' => 'peneliti@aranus.local',
            'join_date' => '2025-01-01',
            'employment_type' => 'permanent',
            'status' => 'active',
        ]);

        $response = $this->actingAs($superAdmin)->get(route('departments.show', $dept));
        $response->assertOk();
        $response->assertSee('Research & Development');
        $response->assertSee('Peneliti Utama');
        $response->assertSee('1 orang');
    }

    public function test_8_department_with_employees_cannot_be_deleted(): void
    {
        $superAdmin = $this->userForRole('super_admin');
        [$dept, $pos] = $this->createDepartmentAndPosition('Dept Terpakai');

        Employee::query()->create([
            'employee_code' => '1',
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'name' => 'Staf Tetap',
            'email' => 'tetap@aranus.local',
            'join_date' => '2025-01-01',
            'employment_type' => 'permanent',
            'status' => 'active',
        ]);

        $response = $this->actingAs($superAdmin)->delete(route('departments.destroy', $dept));
        $response->assertRedirect(route('departments.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('departments', ['id' => $dept->id]);
    }

    public function test_9_department_with_positions_cannot_be_deleted(): void
    {
        $superAdmin = $this->userForRole('super_admin');
        [$dept, $pos] = $this->createDepartmentAndPosition('Dept Dengan Jabatan Saja');

        $response = $this->actingAs($superAdmin)->delete(route('departments.destroy', $dept));
        $response->assertRedirect(route('departments.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('departments', ['id' => $dept->id]);
    }

    public function test_10_empty_department_can_be_deleted(): void
    {
        $superAdmin = $this->userForRole('super_admin');
        $dept = Department::query()->create(['name' => 'Dept Kosong', 'status' => 'active']);

        $response = $this->actingAs($superAdmin)->delete(route('departments.destroy', $dept));
        $response->assertRedirect(route('departments.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('departments', ['id' => $dept->id]);
    }

    public function test_11_rbac_department_access(): void
    {
        $managerHrd = $this->userForRole('manager_hrd');
        $taiwanAccount = $this->userForRole('taiwan_account');
        $dept = Department::query()->create(['name' => 'Dept RBAC', 'status' => 'active']);

        // Manager HRD & Taiwan can read index and show
        $this->actingAs($managerHrd)->get(route('departments.index'))->assertOk();
        $this->actingAs($managerHrd)->get(route('departments.show', $dept))->assertOk();
        $this->actingAs($taiwanAccount)->get(route('departments.index'))->assertOk();
        $this->actingAs($taiwanAccount)->get(route('departments.show', $dept))->assertOk();

        // Manager HRD cannot create, edit, or delete
        $this->actingAs($managerHrd)->get(route('departments.create'))->assertForbidden();
        $this->actingAs($managerHrd)->post(route('departments.store'), ['name' => 'X', 'status' => 'active'])->assertForbidden();
        $this->actingAs($managerHrd)->get(route('departments.edit', $dept))->assertForbidden();
        $this->actingAs($managerHrd)->put(route('departments.update', $dept), ['name' => 'X', 'status' => 'active'])->assertForbidden();
        $this->actingAs($managerHrd)->delete(route('departments.destroy', $dept))->assertForbidden();

        // Taiwan Account cannot create, edit, or delete
        $this->actingAs($taiwanAccount)->get(route('departments.create'))->assertForbidden();
        $this->actingAs($taiwanAccount)->post(route('departments.store'), ['name' => 'X', 'status' => 'active'])->assertForbidden();
        $this->actingAs($taiwanAccount)->get(route('departments.edit', $dept))->assertForbidden();
        $this->actingAs($taiwanAccount)->put(route('departments.update', $dept), ['name' => 'X', 'status' => 'active'])->assertForbidden();
        $this->actingAs($taiwanAccount)->delete(route('departments.destroy', $dept))->assertForbidden();
    }

    // ==========================================
    // PART B: AUTOMATIC EMPLOYEE ID TESTS
    // ==========================================

    public function test_case_1_no_employee_generates_code_1(): void
    {
        $superAdmin = $this->userForRole('super_admin');
        [$dept, $pos] = $this->createDepartmentAndPosition();

        $this->assertDatabaseCount('employees', 0);

        // Preview in create form
        $createResponse = $this->actingAs($superAdmin)->get(route('employees.create'));
        $createResponse->assertOk();
        $createResponse->assertSee('value="1"', false);

        // Submit new employee without typing employee_code
        $response = $this->actingAs($superAdmin)->post(route('employees.store'), [
            'name' => 'Karyawan Pertama',
            'email' => 'pertama@aranus.local',
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'join_date' => '2025-01-01',
            'employment_type' => 'permanent',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('employees.index'));
        $this->assertDatabaseHas('employees', [
            'employee_code' => '1',
            'email' => 'pertama@aranus.local',
        ]);
    }

    public function test_case_2_existing_1_generates_2(): void
    {
        $superAdmin = $this->userForRole('super_admin');
        [$dept, $pos] = $this->createDepartmentAndPosition();

        Employee::query()->create([
            'employee_code' => '1',
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'name' => 'Karyawan 1',
            'email' => 'karyawan1@aranus.local',
            'join_date' => '2025-01-01',
            'employment_type' => 'permanent',
            'status' => 'active',
        ]);

        $this->assertEquals('2', Employee::generateNextEmployeeCode());

        $this->actingAs($superAdmin)->post(route('employees.store'), [
            'name' => 'Karyawan 2',
            'email' => 'karyawan2@aranus.local',
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'join_date' => '2025-01-01',
            'employment_type' => 'permanent',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('employees', ['employee_code' => '2']);
    }

    public function test_case_3_existing_1_2_3_generates_4(): void
    {
        $superAdmin = $this->userForRole('super_admin');
        [$dept, $pos] = $this->createDepartmentAndPosition();

        foreach (['1', '2', '3'] as $code) {
            Employee::query()->create([
                'employee_code' => $code,
                'department_id' => $dept->id,
                'position_id' => $pos->id,
                'name' => "Karyawan $code",
                'email' => "karyawan{$code}@aranus.local",
                'join_date' => '2025-01-01',
                'employment_type' => 'permanent',
                'status' => 'active',
            ]);
        }

        $this->assertEquals('4', Employee::generateNextEmployeeCode());

        $this->actingAs($superAdmin)->post(route('employees.store'), [
            'name' => 'Karyawan 4',
            'email' => 'karyawan4@aranus.local',
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'join_date' => '2025-01-01',
            'employment_type' => 'permanent',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('employees', ['employee_code' => '4']);
    }

    public function test_case_4_existing_1_2_5_generates_6(): void
    {
        $superAdmin = $this->userForRole('super_admin');
        [$dept, $pos] = $this->createDepartmentAndPosition();

        foreach (['1', '2', '5'] as $code) {
            Employee::query()->create([
                'employee_code' => $code,
                'department_id' => $dept->id,
                'position_id' => $pos->id,
                'name' => "Karyawan $code",
                'email' => "karyawan{$code}@aranus.local",
                'join_date' => '2025-01-01',
                'employment_type' => 'permanent',
                'status' => 'active',
            ]);
        }

        $this->assertEquals('6', Employee::generateNextEmployeeCode());

        $this->actingAs($superAdmin)->post(route('employees.store'), [
            'name' => 'Karyawan Baru',
            'email' => 'karyawanbaru@aranus.local',
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'join_date' => '2025-01-01',
            'employment_type' => 'permanent',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('employees', ['employee_code' => '6']);
    }

    public function test_case_5_resigned_employee_4_does_not_get_reused_generates_5(): void
    {
        $superAdmin = $this->userForRole('super_admin');
        [$dept, $pos] = $this->createDepartmentAndPosition();

        foreach (['1', '2', '3'] as $code) {
            Employee::query()->create([
                'employee_code' => $code,
                'department_id' => $dept->id,
                'position_id' => $pos->id,
                'name' => "Karyawan $code",
                'email' => "karyawan{$code}@aranus.local",
                'join_date' => '2025-01-01',
                'employment_type' => 'permanent',
                'status' => 'active',
            ]);
        }

        // Karyawan 4 berstatus resigned
        Employee::query()->create([
            'employee_code' => '4',
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'name' => 'Karyawan 4 Resign',
            'email' => 'karyawan4resign@aranus.local',
            'join_date' => '2024-01-01',
            'employment_type' => 'permanent',
            'status' => 'resigned',
            'resigned_at' => '2025-12-31',
        ]);

        // Code berikutnya harus 5, BUKAN 4
        $this->assertEquals('5', Employee::generateNextEmployeeCode());

        $this->actingAs($superAdmin)->post(route('employees.store'), [
            'name' => 'Karyawan Baru Setelah Resign',
            'email' => 'karyawansetelahresign@aranus.local',
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'join_date' => '2026-01-01',
            'employment_type' => 'permanent',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('employees', ['employee_code' => '5']);
        $this->assertDatabaseMissing('employees', [
            'employee_code' => '4',
            'email' => 'karyawansetelahresign@aranus.local',
        ]);
    }

    public function test_case_6_editing_employee_retains_original_code(): void
    {
        $superAdmin = $this->userForRole('super_admin');
        [$dept, $pos] = $this->createDepartmentAndPosition();

        $employee = Employee::query()->create([
            'employee_code' => '4',
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'name' => 'Nama Sebelum Edit',
            'email' => 'editme@aranus.local',
            'join_date' => '2025-01-01',
            'employment_type' => 'permanent',
            'status' => 'active',
        ]);

        $response = $this->actingAs($superAdmin)->put(route('employees.update', $employee), [
            'employee_code' => '999', // Mencoba manipulasi employee_code saat update
            'name' => 'Nama Sesudah Edit',
            'email' => 'editme@aranus.local',
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'join_date' => '2025-01-01',
            'employment_type' => 'permanent',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('employees.show', $employee));
        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'name' => 'Nama Sesudah Edit',
            'employee_code' => '4', // Tetap 4!
        ]);
        $this->assertDatabaseMissing('employees', ['employee_code' => '999']);
    }

    public function test_case_7_user_cannot_override_employee_code_via_manual_post_request(): void
    {
        $superAdmin = $this->userForRole('super_admin');
        [$dept, $pos] = $this->createDepartmentAndPosition();

        Employee::query()->create([
            'employee_code' => '1',
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'name' => 'Karyawan 1',
            'email' => 'karyawan1@aranus.local',
            'join_date' => '2025-01-01',
            'employment_type' => 'permanent',
            'status' => 'active',
        ]);

        // User mencoba mengirim manual employee_code '888' via POST
        $response = $this->actingAs($superAdmin)->post(route('employees.store'), [
            'employee_code' => '888',
            'name' => 'Hacker Input',
            'email' => 'hacker@aranus.local',
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'join_date' => '2025-01-01',
            'employment_type' => 'permanent',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('employees.index'));

        // Harus tetap bernomor 2 sesuai generator sistem, bukan 888
        $this->assertDatabaseHas('employees', [
            'email' => 'hacker@aranus.local',
            'employee_code' => '2',
        ]);
        $this->assertDatabaseMissing('employees', ['employee_code' => '888']);
    }
}
