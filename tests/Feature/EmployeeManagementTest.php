<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function userForRole(string $roleName): User
    {
        $role = Role::query()->where('name', $roleName)->firstOrFail();

        return User::query()->where('role_id', $role->id)->firstOrFail();
    }

    private function createDepartmentAndPosition(): array
    {
        $dept = Department::factory()->create([
            'name' => 'Engineering',
            'status' => 'active',
        ]);

        $pos = Position::factory()->create([
            'department_id' => $dept->id,
            'name' => 'Software Engineer',
            'status' => 'active',
        ]);

        return [$dept, $pos];
    }

    public function test_super_admin_can_view_create_edit_and_resigned_employees(): void
    {
        $superAdmin = $this->userForRole('super_admin');
        [$dept, $pos] = $this->createDepartmentAndPosition();

        $activeEmployee = Employee::factory()->create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'status' => 'active',
        ]);

        $resignedEmployee = Employee::factory()->resigned()->create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
        ]);

        // 1. Can view employee index
        $indexResponse = $this->actingAs($superAdmin)->get(route('employees.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee($activeEmployee->name);
        // By default, resigned employee should not be shown in active directory
        $indexResponse->assertDontSee($resignedEmployee->name);

        // 2. Can view create page
        $createResponse = $this->actingAs($superAdmin)->get(route('employees.create'));
        $createResponse->assertOk();
        $createResponse->assertSee('Tambah Karyawan');

        // 3. Can store employee
        $storeData = [
            'employee_code' => 'EMP-TEST01',
            'name' => 'Budi Santoso',
            'email' => 'budi.santoso@aranus.local',
            'phone' => '081234567890',
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'join_date' => '2025-01-15',
            'employment_type' => 'permanent',
            'status' => 'active',
            'special_status' => 'Probation Passed',
        ];

        $storeResponse = $this->actingAs($superAdmin)->post(route('employees.store'), $storeData);
        $storeResponse->assertRedirect(route('employees.index'));
        $this->assertDatabaseHas('employees', [
            'email' => 'budi.santoso@aranus.local',
        ]);

        // Verify audit log created
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'employee_created',
            'user_id' => $superAdmin->id,
        ]);

        $newEmployee = Employee::query()->where('email', 'budi.santoso@aranus.local')->firstOrFail();

        // 4. Can view detail
        $showResponse = $this->actingAs($superAdmin)->get(route('employees.show', $newEmployee));
        $showResponse->assertOk();
        $showResponse->assertSee('Budi Santoso');
        $showResponse->assertSee($newEmployee->employee_code);

        // 5. Can edit employee and change status to resigned
        $editResponse = $this->actingAs($superAdmin)->get(route('employees.edit', $newEmployee));
        $editResponse->assertOk();
        $editResponse->assertSee('Edit Data Karyawan');

        $updateData = array_merge($storeData, [
            'name' => 'Budi Santoso Updated',
            'status' => 'resigned',
            'resigned_at' => '2026-03-31',
        ]);

        $updateResponse = $this->actingAs($superAdmin)->put(route('employees.update', $newEmployee), $updateData);
        $updateResponse->assertRedirect(route('employees.show', $newEmployee));
        $this->assertDatabaseHas('employees', [
            'id' => $newEmployee->id,
            'name' => 'Budi Santoso Updated',
            'status' => 'resigned',
        ]);
        $this->assertSame('2026-03-31', $newEmployee->fresh()->resigned_at?->format('Y-m-d'));

        // Verify audit log for resigned
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'employee_resigned',
            'user_id' => $superAdmin->id,
        ]);

        // 6. Can view resigned employees page
        $resignedResponse = $this->actingAs($superAdmin)->get(route('employees.resigned'));
        $resignedResponse->assertOk();
        $resignedResponse->assertSee('Budi Santoso Updated');
        $resignedResponse->assertSee($resignedEmployee->name);
    }

    public function test_management_can_view_create_edit_and_see_resigned_employees(): void
    {
        $management = $this->userForRole('manager_hrd');
        [$dept, $pos] = $this->createDepartmentAndPosition();

        $employee = Employee::factory()->create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'status' => 'active',
        ]);

        // View index
        $this->actingAs($management)
            ->get(route('employees.index'))
            ->assertOk()
            ->assertSee($employee->name);

        // View detail
        $this->actingAs($management)
            ->get(route('employees.show', $employee))
            ->assertOk()
            ->assertSee($employee->name);

        // View create form
        $this->actingAs($management)
            ->get(route('employees.create'))
            ->assertOk();

        // Create employee
        $storeData = [
            'employee_code' => 'EMP-MGT01',
            'name' => 'Dewi Lestari',
            'email' => 'dewi@aranus.local',
            'phone' => '08987654321',
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'join_date' => '2025-06-01',
            'employment_type' => 'contract',
            'status' => 'active',
        ];

        $this->actingAs($management)
            ->post(route('employees.store'), $storeData)
            ->assertRedirect(route('employees.index'));

        $created = Employee::where('email', 'dewi@aranus.local')->firstOrFail();
        $this->assertDatabaseHas('employees', ['id' => $created->id, 'email' => 'dewi@aranus.local']);

        // View edit form
        $this->actingAs($management)
            ->get(route('employees.edit', $created))
            ->assertOk();

        // View resigned list
        $this->actingAs($management)
            ->get(route('employees.resigned'))
            ->assertOk();
    }

    public function test_taiwan_account_can_only_view_and_cannot_create_edit_or_delete(): void
    {
        $taiwan = $this->userForRole('taiwan_account');
        [$dept, $pos] = $this->createDepartmentAndPosition();

        $employee = Employee::factory()->create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'name' => 'Karyawan Taiwan Viewer',
            'status' => 'active',
        ]);

        $resigned = Employee::factory()->resigned()->create([
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'name' => 'Mantan Karyawan',
        ]);

        // 1. Taiwan CAN view employee list
        $indexResponse = $this->actingAs($taiwan)->get(route('employees.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Karyawan Taiwan Viewer');
        // Taiwan must not see create button
        $indexResponse->assertDontSee('Tambah Karyawan');

        // 2. Taiwan CAN view employee detail
        $showResponse = $this->actingAs($taiwan)->get(route('employees.show', $employee));
        $showResponse->assertOk();
        $showResponse->assertSee('Karyawan Taiwan Viewer');
        $showResponse->assertDontSee('Edit Karyawan');

        // 3. Taiwan CAN view resigned list
        $resignedResponse = $this->actingAs($taiwan)->get(route('employees.resigned'));
        $resignedResponse->assertOk();
        $resignedResponse->assertSee('Mantan Karyawan');

        // 4. Taiwan CANNOT open create page (403)
        $this->actingAs($taiwan)
            ->get(route('employees.create'))
            ->assertForbidden();

        // 5. Taiwan CANNOT submit new employee (403)
        $this->actingAs($taiwan)
            ->post(route('employees.store'), [
                'employee_code' => 'EMP-TAIWAN-ATTEMPT',
                'name' => 'Taiwan Hacker',
                'email' => 'taiwan.attempt@aranus.local',
                'department_id' => $dept->id,
                'position_id' => $pos->id,
                'join_date' => '2026-01-01',
                'employment_type' => 'permanent',
                'status' => 'active',
            ])
            ->assertForbidden();

        // Verify data was NOT saved
        $this->assertDatabaseMissing('employees', ['employee_code' => 'EMP-TAIWAN-ATTEMPT']);

        // 6. Taiwan CANNOT open edit page (403)
        $this->actingAs($taiwan)
            ->get(route('employees.edit', $employee))
            ->assertForbidden();

        // 7. Taiwan CANNOT submit update (403)
        $this->actingAs($taiwan)
            ->put(route('employees.update', $employee), [
                'employee_code' => $employee->employee_code,
                'name' => 'Name Changed By Taiwan',
                'email' => $employee->email,
                'department_id' => $dept->id,
                'position_id' => $pos->id,
                'join_date' => '2026-01-01',
                'employment_type' => 'permanent',
                'status' => 'active',
            ])
            ->assertForbidden();

        // 8. Taiwan CANNOT delete employee (403)
        $this->actingAs($taiwan)
            ->delete(route('employees.destroy', $employee))
            ->assertForbidden();
    }

    public function test_validation_errors_prevent_saving_invalid_employee(): void
    {
        $superAdmin = $this->userForRole('super_admin');
        [$dept, $pos] = $this->createDepartmentAndPosition();

        $existing = Employee::factory()->create([
            'employee_code' => 'EMP-EXISTING',
            'email' => 'existing@aranus.local',
            'department_id' => $dept->id,
            'position_id' => $pos->id,
        ]);

        // 1. Duplicate employee_code & email
        $response = $this->actingAs($superAdmin)->post(route('employees.store'), [
            'employee_code' => 'EMP-EXISTING',
            'email' => 'existing@aranus.local',
            'name' => 'Duplicate Person',
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'join_date' => '2026-01-01',
            'employment_type' => 'permanent',
            'status' => 'active',
        ]);
        $response->assertSessionHasErrors(['email']);

        // 2. Invalid department_id and position_id
        $response2 = $this->actingAs($superAdmin)->post(route('employees.store'), [
            'employee_code' => 'EMP-NEW-VAL',
            'email' => 'newval@aranus.local',
            'name' => 'Invalid Dept Person',
            'department_id' => 99999,
            'position_id' => 99999,
            'join_date' => '2026-01-01',
            'employment_type' => 'invalid_type',
            'status' => 'invalid_status',
        ]);
        $response2->assertSessionHasErrors(['department_id', 'position_id', 'employment_type', 'status']);

        // 3. Required resigned_at when status is resigned
        $response3 = $this->actingAs($superAdmin)->post(route('employees.store'), [
            'employee_code' => 'EMP-RESIGNED-VAL',
            'email' => 'resignedval@aranus.local',
            'name' => 'Resigned Without Date',
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'join_date' => '2026-01-01',
            'employment_type' => 'permanent',
            'status' => 'resigned',
            'resigned_at' => null,
        ]);
        $response3->assertSessionHasErrors(['resigned_at']);
    }

    public function test_search_and_filter_functionality_works(): void
    {
        $superAdmin = $this->userForRole('super_admin');
        [$dept1, $pos1] = $this->createDepartmentAndPosition();

        $dept2 = Department::factory()->create(['name' => 'Human Resources']);
        $pos2 = Position::factory()->create(['department_id' => $dept2->id, 'name' => 'HR Officer']);

        $emp1 = Employee::factory()->create([
            'name' => 'Anisa Rahma',
            'employee_code' => 'EMP-001',
            'email' => 'anisa@aranus.local',
            'department_id' => $dept1->id,
            'position_id' => $pos1->id,
            'status' => 'active',
        ]);

        $emp2 = Employee::factory()->create([
            'name' => 'Bambang Sudirman',
            'employee_code' => 'EMP-002',
            'email' => 'bambang@aranus.local',
            'department_id' => $dept2->id,
            'position_id' => $pos2->id,
            'status' => 'inactive',
        ]);

        // Search by name
        $this->actingAs($superAdmin)
            ->get(route('employees.index', ['search' => 'Anisa']))
            ->assertOk()
            ->assertSee('Anisa Rahma')
            ->assertDontSee('Bambang Sudirman');

        // Search by code
        $this->actingAs($superAdmin)
            ->get(route('employees.index', ['search' => 'EMP-002']))
            ->assertOk()
            ->assertSee('Bambang Sudirman')
            ->assertDontSee('Anisa Rahma');

        // Filter by department
        $this->actingAs($superAdmin)
            ->get(route('employees.index', ['department_id' => $dept2->id]))
            ->assertOk()
            ->assertSee('Bambang Sudirman')
            ->assertDontSee('Anisa Rahma');

        // Filter by status
        $this->actingAs($superAdmin)
            ->get(route('employees.index', ['status' => 'active']))
            ->assertOk()
            ->assertSee('Anisa Rahma')
            ->assertDontSee('Bambang Sudirman');
    }
}
