<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Overtime;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OvertimeManagementTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function userForRole(string $roleName): User
    {
        $role = Role::query()->where('name', $roleName)->firstOrFail();

        return User::query()->where('role_id', $role->id)->firstOrFail();
    }

    private function createEmployeeWithDept(string $name, string $code, ?Department $dept = null): Employee
    {
        $department = $dept ?? Department::query()->firstOrCreate(
            ['name' => 'HR Operations'],
            ['status' => 'active']
        );

        $position = Position::query()->firstOrCreate(
            ['department_id' => $department->id, 'name' => 'HR Specialist'],
            ['status' => 'active']
        );

        return Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
            'name' => $name,
            'employee_code' => $code,
            'status' => 'active',
        ]);
    }

    public function test_super_admin_can_view_overtime_index_and_detail(): void
    {
        $admin = $this->userForRole('super_admin');
        $emp = $this->createEmployeeWithDept('Budi Santoso', 'EMP-001');

        $overtime = Overtime::factory()->create([
            'employee_id' => $emp->id,
            'reason' => 'Perbaikan server mendadak',
        ]);

        $this->actingAs($admin)
            ->get(route('overtimes.index'))
            ->assertOk()
            ->assertSee('Pengajuan & Monitoring Lembur')
            ->assertSee('Budi Santoso');

        $this->actingAs($admin)
            ->get(route('overtimes.show', $overtime))
            ->assertOk()
            ->assertSee('Detail Pengajuan Lembur')
            ->assertSee('Budi Santoso')
            ->assertSee('Perbaikan server mendadak');
    }

    public function test_management_can_view_overtime_index_and_detail(): void
    {
        $management = $this->userForRole('manager_hrd');
        $emp = $this->createEmployeeWithDept('Dewi Lestari', 'EMP-002');

        $overtime = Overtime::factory()->create([
            'employee_id' => $emp->id,
            'reason' => 'Penyusunan laporan bulanan',
        ]);

        $this->actingAs($management)
            ->get(route('overtimes.index'))
            ->assertOk()
            ->assertSee('Dewi Lestari');

        $this->actingAs($management)
            ->get(route('overtimes.show', $overtime))
            ->assertOk()
            ->assertSee('Dewi Lestari')
            ->assertSee('Penyusunan laporan bulanan');
    }

    public function test_taiwan_account_can_view_overtime_but_cannot_approve_reject_or_create(): void
    {
        $taiwan = $this->userForRole('taiwan_account');
        $emp = $this->createEmployeeWithDept('Tan Wei', 'EMP-003');

        $overtime = Overtime::factory()->create([
            'employee_id' => $emp->id,
            'status' => 'pending',
            'reason' => 'Audit inventaris pabrik',
        ]);

        // 1. Taiwan CAN view index
        $indexResponse = $this->actingAs($taiwan)->get(route('overtimes.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Tan Wei');
        // Taiwan must not see create, approve, or reject buttons
        $indexResponse->assertDontSee('Tambah Pengajuan');
        $indexResponse->assertDontSee('✓ Approve');
        $indexResponse->assertDontSee('✗ Reject');

        // 2. Taiwan CAN view detail
        $showResponse = $this->actingAs($taiwan)->get(route('overtimes.show', $overtime));
        $showResponse->assertOk();
        $showResponse->assertSee('Tan Wei');
        $showResponse->assertDontSee('✓ Setujui Lembur');
        $showResponse->assertDontSee('✗ Tolak Lembur');

        // 3. Taiwan CANNOT access create page (403)
        $this->actingAs($taiwan)
            ->get(route('overtimes.create'))
            ->assertForbidden();

        // 4. Taiwan CANNOT submit overtime store (403)
        $this->actingAs($taiwan)
            ->post(route('overtimes.store'), [
                'employee_id' => $emp->id,
                'overtime_date' => '2026-10-05',
                'start_time' => '18:00',
                'end_time' => '21:00',
                'reason' => 'Lembur dari taiwan',
            ])
            ->assertForbidden();

        // 5. Taiwan CANNOT approve (403)
        $this->actingAs($taiwan)
            ->post(route('overtimes.approve', $overtime))
            ->assertForbidden();

        // 6. Taiwan CANNOT reject (403)
        $this->actingAs($taiwan)
            ->post(route('overtimes.reject', $overtime), [
                'rejection_reason' => 'Ditolak dari Taiwan',
            ])
            ->assertForbidden();

        // Status must remain pending
        $this->assertSame('pending', $overtime->fresh()->status);
    }

    public function test_user_without_permission_receives_forbidden(): void
    {
        $userWithoutRole = new User([
            'name' => 'Unauthorized User',
            'email' => 'unauth@aranus.local',
        ]);

        $this->actingAs($userWithoutRole)
            ->get(route('overtimes.index'))
            ->assertForbidden();
    }

    public function test_management_can_access_create_form_and_store_overtime(): void
    {
        $management = $this->userForRole('manager_hrd');
        $emp = $this->createEmployeeWithDept('Ahmad Rizki', 'EMP-004');

        // 1. Can view create page
        $this->actingAs($management)
            ->get(route('overtimes.create'))
            ->assertOk()
            ->assertSee('Tambah Pengajuan Lembur')
            ->assertSee('Ahmad Rizki');

        // 2. Can store overtime
        $response = $this->actingAs($management)
            ->post(route('overtimes.store'), [
                'employee_id' => $emp->id,
                'overtime_date' => '2026-10-05',
                'start_time' => '18:00',
                'end_time' => '21:30',
                'reason' => 'Maintenance infrastruktur database',
            ]);

        $response->assertRedirect(route('overtimes.index'));
        $response->assertSessionHas('success');

        // Verify database and calculated duration: 18:00 to 21:30 = 3.5 hours
        $this->assertDatabaseHas('overtimes', [
            'employee_id' => $emp->id,
            'overtime_date' => '2026-10-05 00:00:00',
            'start_time' => '18:00',
            'end_time' => '21:30',
            'duration' => 3.5,
            'status' => 'pending',
            'reason' => 'Maintenance infrastruktur database',
        ]);

        // Verify audit log
        $createdOvertime = Overtime::where('employee_id', $emp->id)->latest()->first();
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'overtime_created',
            'user_id' => $management->id,
            'entity_type' => Overtime::class,
            'entity_id' => $createdOvertime->id,
        ]);
    }

    public function test_store_overtime_fails_validation_when_fields_are_missing(): void
    {
        $management = $this->userForRole('manager_hrd');

        $response = $this->actingAs($management)
            ->post(route('overtimes.store'), [
                'employee_id' => '',
                'overtime_date' => '',
                'start_time' => '',
                'end_time' => '',
                'reason' => '',
            ]);

        $response->assertSessionHasErrors(['employee_id', 'overtime_date', 'start_time', 'end_time', 'reason']);
    }

    public function test_pending_overtime_can_be_approved(): void
    {
        $management = $this->userForRole('manager_hrd');
        $emp = $this->createEmployeeWithDept('Karyawan Pending Approve', 'EMP-005');

        $overtime = Overtime::factory()->create([
            'employee_id' => $emp->id,
            'status' => 'pending',
            'duration' => 3.0,
        ]);

        $response = $this->actingAs($management)->post(route('overtimes.approve', $overtime));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $fresh = $overtime->fresh();
        $this->assertSame('approved', $fresh->status);
        $this->assertSame($management->id, $fresh->approved_by);
        $this->assertNotNull($fresh->approved_at);

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'overtime_approved',
            'user_id' => $management->id,
            'entity_type' => Overtime::class,
            'entity_id' => $overtime->id,
        ]);
    }

    public function test_pending_overtime_can_be_rejected_with_reason(): void
    {
        $management = $this->userForRole('manager_hrd');
        $emp = $this->createEmployeeWithDept('Karyawan Pending Reject', 'EMP-006');

        $overtime = Overtime::factory()->create([
            'employee_id' => $emp->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($management)->post(route('overtimes.reject', $overtime), [
            'rejection_reason' => 'Tugas lembur tidak memiliki surat tugas resmi dari supervisor.',
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $fresh = $overtime->fresh();
        $this->assertSame('rejected', $fresh->status);
        $this->assertSame($management->id, $fresh->approved_by);
        $this->assertNotNull($fresh->approved_at);
        $this->assertSame('Tugas lembur tidak memiliki surat tugas resmi dari supervisor.', $fresh->rejection_reason);

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'overtime_rejected',
            'user_id' => $management->id,
            'entity_type' => Overtime::class,
            'entity_id' => $overtime->id,
        ]);
    }

    public function test_rejecting_overtime_without_reason_fails_validation(): void
    {
        $management = $this->userForRole('manager_hrd');
        $emp = $this->createEmployeeWithDept('Reject Without Reason', 'EMP-007');

        $overtime = Overtime::factory()->create([
            'employee_id' => $emp->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($management)->post(route('overtimes.reject', $overtime), [
            'rejection_reason' => '',
        ]);
        $response->assertSessionHasErrors(['rejection_reason']);

        // Status must remain pending
        $this->assertSame('pending', $overtime->fresh()->status);
    }

    public function test_approved_overtime_cannot_be_reprocessed(): void
    {
        $management = $this->userForRole('manager_hrd');
        $emp = $this->createEmployeeWithDept('Already Approved', 'EMP-008');

        $overtime = Overtime::factory()->approved($management)->create([
            'employee_id' => $emp->id,
        ]);

        // Try to approve again
        $response1 = $this->actingAs($management)->post(route('overtimes.approve', $overtime));
        $response1->assertSessionHas('error');

        // Try to reject approved overtime
        $response2 = $this->actingAs($management)->post(route('overtimes.reject', $overtime), [
            'rejection_reason' => 'Coba tolak setelah disetujui',
        ]);
        $response2->assertSessionHas('error');

        $this->assertSame('approved', $overtime->fresh()->status);
    }

    public function test_rejected_overtime_cannot_be_reprocessed(): void
    {
        $management = $this->userForRole('manager_hrd');
        $emp = $this->createEmployeeWithDept('Already Rejected', 'EMP-009');

        $overtime = Overtime::factory()->rejected($management)->create([
            'employee_id' => $emp->id,
        ]);

        // Try to approve rejected overtime
        $response = $this->actingAs($management)->post(route('overtimes.approve', $overtime));
        $response->assertSessionHas('error');

        $this->assertSame('rejected', $overtime->fresh()->status);
    }

    public function test_duration_calculation_logic(): void
    {
        // 1. Same-day standard shift: 18:00 to 21:00 = 3 hours
        $this->assertSame(3.0, Overtime::calculateDuration('18:00', '21:00'));

        // 2. Fractional hour: 17:30 to 20:15 = 2.75 hours
        $this->assertSame(2.75, Overtime::calculateDuration('17:30', '20:15'));

        // 3. Overnight shift crossing midnight: 22:00 to 02:00 = 4 hours
        $this->assertSame(4.0, Overtime::calculateDuration('22:00', '02:00'));

        // 4. Test accessors
        $emp = $this->createEmployeeWithDept('Duration Tester', 'EMP-010');
        $overtime = Overtime::factory()->create([
            'employee_id' => $emp->id,
            'start_time' => '18:00',
            'end_time' => '20:30',
            'duration' => 2.5,
        ]);

        $this->assertSame(2.5, $overtime->duration_hours);
        $this->assertSame('2.5 Jam', $overtime->duration_label);
    }

    public function test_search_and_filter_functionality_works(): void
    {
        $management = $this->userForRole('manager_hrd');

        $deptProduction = Department::factory()->create(['name' => 'Production Department']);
        $deptWarehouse = Department::factory()->create(['name' => 'Warehouse Logistics']);

        $empProd = $this->createEmployeeWithDept('Fajar Production', 'EMP-501', $deptProduction);
        $empWh = $this->createEmployeeWithDept('Gita Warehouse', 'EMP-502', $deptWarehouse);

        Overtime::factory()->create([
            'employee_id' => $empProd->id,
            'overtime_date' => '2026-11-01',
            'status' => 'pending',
            'reason' => 'Overtime Shift Pabrik',
        ]);

        Overtime::factory()->create([
            'employee_id' => $empWh->id,
            'overtime_date' => '2026-11-10',
            'status' => 'approved',
            'reason' => 'Stock opname gudang akhir pekan',
        ]);

        // 1. Search by name
        $this->actingAs($management)
            ->get(route('overtimes.index', ['search' => 'Fajar']))
            ->assertOk()
            ->assertSee('Fajar Production')
            ->assertDontSee('Gita Warehouse');

        // 2. Search by employee code
        $this->actingAs($management)
            ->get(route('overtimes.index', ['search' => 'EMP-502']))
            ->assertOk()
            ->assertSee('Gita Warehouse')
            ->assertDontSee('Fajar Production');

        // 3. Filter by department
        $this->actingAs($management)
            ->get(route('overtimes.index', ['department_id' => $deptProduction->id]))
            ->assertOk()
            ->assertSee('Fajar Production')
            ->assertDontSee('Gita Warehouse');

        // 4. Filter by status: pending
        $this->actingAs($management)
            ->get(route('overtimes.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee('Fajar Production')
            ->assertDontSee('Gita Warehouse');

        // 5. Filter by date
        $this->actingAs($management)
            ->get(route('overtimes.index', ['date' => '2026-11-10']))
            ->assertOk()
            ->assertSee('Gita Warehouse')
            ->assertDontSee('Fajar Production');
    }

    public function test_overtime_cost_is_not_invented_shows_payroll_disclaimer(): void
    {
        $management = $this->userForRole('manager_hrd');

        $this->actingAs($management)
            ->get(route('overtimes.index'))
            ->assertOk()
            ->assertSee('Modul Payroll')
            ->assertSee('Dihitung saat proses penggajian');
    }
}
