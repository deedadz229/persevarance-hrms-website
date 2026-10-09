<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveManagementTest extends TestCase
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

    public function test_super_admin_can_view_leave_index_and_detail(): void
    {
        $admin = $this->userForRole('super_admin');
        $emp = $this->createEmployeeWithDept('Budi Santoso', 'EMP-001');

        $leave = LeaveRequest::factory()->create([
            'employee_id' => $emp->id,
            'leave_type' => 'Cuti Tahunan',
        ]);

        $this->actingAs($admin)
            ->get(route('leave-requests.index'))
            ->assertOk()
            ->assertSee('Pengajuan & Persetujuan Cuti')
            ->assertSee('Budi Santoso');

        $this->actingAs($admin)
            ->get(route('leave-requests.show', $leave))
            ->assertOk()
            ->assertSee('Detail Pengajuan Cuti')
            ->assertSee('Budi Santoso')
            ->assertSee('Cuti Tahunan');
    }

    public function test_management_can_view_leave_index_and_detail(): void
    {
        $management = $this->userForRole('manager_hrd');
        $emp = $this->createEmployeeWithDept('Dewi Lestari', 'EMP-002');

        $leave = LeaveRequest::factory()->create([
            'employee_id' => $emp->id,
            'leave_type' => 'Izin Sakit',
        ]);

        $this->actingAs($management)
            ->get(route('leave-requests.index'))
            ->assertOk()
            ->assertSee('Dewi Lestari');

        $this->actingAs($management)
            ->get(route('leave-requests.show', $leave))
            ->assertOk()
            ->assertSee('Dewi Lestari')
            ->assertSee('Izin Sakit');
    }

    public function test_taiwan_account_can_view_leave_but_cannot_approve_or_reject(): void
    {
        $taiwan = $this->userForRole('taiwan_account');
        $emp = $this->createEmployeeWithDept('Tan Wei', 'EMP-003');

        $leave = LeaveRequest::factory()->create([
            'employee_id' => $emp->id,
            'status' => 'pending',
        ]);

        // 1. Taiwan CAN view index
        $indexResponse = $this->actingAs($taiwan)->get(route('leave-requests.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Tan Wei');
        // Taiwan must not see approve or reject buttons
        $indexResponse->assertDontSee('✓ Approve');
        $indexResponse->assertDontSee('✗ Reject');

        // 2. Taiwan CAN view detail
        $showResponse = $this->actingAs($taiwan)->get(route('leave-requests.show', $leave));
        $showResponse->assertOk();
        $showResponse->assertSee('Tan Wei');
        $showResponse->assertDontSee('✓ Setujui Pengajuan');

        // 3. Taiwan CANNOT approve (403)
        $this->actingAs($taiwan)
            ->post(route('leave-requests.approve', $leave))
            ->assertForbidden();

        // 4. Taiwan CANNOT reject (403)
        $this->actingAs($taiwan)
            ->post(route('leave-requests.reject', $leave), [
                'rejection_reason' => 'Alasan dari Taiwan',
            ])
            ->assertForbidden();

        // Status must remain pending
        $this->assertSame('pending', $leave->fresh()->status);
    }

    public function test_user_without_permission_receives_forbidden(): void
    {
        $userWithoutRole = new User([
            'name' => 'Unauthorized User',
            'email' => 'unauth@aranus.local',
        ]);

        $this->actingAs($userWithoutRole)
            ->get(route('leave-requests.index'))
            ->assertForbidden();
    }

    public function test_pending_leave_request_can_be_approved(): void
    {
        $management = $this->userForRole('manager_hrd');
        $emp = $this->createEmployeeWithDept('Karyawan Pending Approve', 'EMP-004');

        $leave = LeaveRequest::factory()->create([
            'employee_id' => $emp->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($management)->post(route('leave-requests.approve', $leave));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $fresh = $leave->fresh();
        $this->assertSame('approved', $fresh->status);
        $this->assertSame($management->id, $fresh->approved_by);
        $this->assertNotNull($fresh->approved_at);

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'leave_approved',
            'user_id' => $management->id,
            'entity_type' => LeaveRequest::class,
            'entity_id' => $leave->id,
        ]);
    }

    public function test_pending_leave_request_can_be_rejected_with_reason(): void
    {
        $management = $this->userForRole('manager_hrd');
        $emp = $this->createEmployeeWithDept('Karyawan Pending Reject', 'EMP-005');

        $leave = LeaveRequest::factory()->create([
            'employee_id' => $emp->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($management)->post(route('leave-requests.reject', $leave), [
            'rejection_reason' => 'Kuota personil shift pada tanggal tersebut sedang terbatas.',
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $fresh = $leave->fresh();
        $this->assertSame('rejected', $fresh->status);
        $this->assertSame($management->id, $fresh->approved_by);
        $this->assertNotNull($fresh->approved_at);
        $this->assertSame('Kuota personil shift pada tanggal tersebut sedang terbatas.', $fresh->rejection_reason);

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'leave_rejected',
            'user_id' => $management->id,
            'entity_type' => LeaveRequest::class,
            'entity_id' => $leave->id,
        ]);
    }

    public function test_rejecting_without_reason_fails_validation(): void
    {
        $management = $this->userForRole('manager_hrd');
        $emp = $this->createEmployeeWithDept('Reject Without Reason', 'EMP-006');

        $leave = LeaveRequest::factory()->create([
            'employee_id' => $emp->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($management)->post(route('leave-requests.reject', $leave), [
            'rejection_reason' => '',
        ]);
        $response->assertSessionHasErrors(['rejection_reason']);

        // Status must remain pending
        $this->assertSame('pending', $leave->fresh()->status);
    }

    public function test_approved_request_cannot_be_reprocessed(): void
    {
        $management = $this->userForRole('manager_hrd');
        $emp = $this->createEmployeeWithDept('Already Approved', 'EMP-007');

        $leave = LeaveRequest::factory()->approved($management)->create([
            'employee_id' => $emp->id,
        ]);

        // Try to approve again
        $response1 = $this->actingAs($management)->post(route('leave-requests.approve', $leave));
        $response1->assertSessionHas('error');

        // Try to reject approved request
        $response2 = $this->actingAs($management)->post(route('leave-requests.reject', $leave), [
            'rejection_reason' => 'Coba tolak setelah disetujui',
        ]);
        $response2->assertSessionHas('error');

        $this->assertSame('approved', $leave->fresh()->status);
    }

    public function test_rejected_request_cannot_be_reprocessed(): void
    {
        $management = $this->userForRole('manager_hrd');
        $emp = $this->createEmployeeWithDept('Already Rejected', 'EMP-008');

        $leave = LeaveRequest::factory()->rejected($management)->create([
            'employee_id' => $emp->id,
        ]);

        // Try to approve rejected request
        $response = $this->actingAs($management)->post(route('leave-requests.approve', $leave));
        $response->assertSessionHas('error');

        $this->assertSame('rejected', $leave->fresh()->status);
    }

    public function test_search_and_filter_functionality_works(): void
    {
        $management = $this->userForRole('manager_hrd');

        $deptFinance = Department::factory()->create(['name' => 'Finance & Accounting']);
        $deptTech = Department::factory()->create(['name' => 'Tech Division']);

        $empFinance = $this->createEmployeeWithDept('Citra Finance', 'EMP-777', $deptFinance);
        $empTech = $this->createEmployeeWithDept('Doni Tech', 'EMP-888', $deptTech);

        $leaveFinance = LeaveRequest::factory()->create([
            'employee_id' => $empFinance->id,
            'leave_type' => 'Cuti Menikah',
            'status' => 'pending',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-03',
        ]);

        $leaveTech = LeaveRequest::factory()->create([
            'employee_id' => $empTech->id,
            'leave_type' => 'Izin Sakit',
            'status' => 'approved',
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-11',
        ]);

        // 1. Search by name
        $this->actingAs($management)
            ->get(route('leave-requests.index', ['search' => 'Citra']))
            ->assertOk()
            ->assertSee('Citra Finance')
            ->assertDontSee('Doni Tech');

        // 2. Search by employee code
        $this->actingAs($management)
            ->get(route('leave-requests.index', ['search' => 'EMP-888']))
            ->assertOk()
            ->assertSee('Doni Tech')
            ->assertDontSee('Citra Finance');

        // 3. Filter by department
        $this->actingAs($management)
            ->get(route('leave-requests.index', ['department_id' => $deptFinance->id]))
            ->assertOk()
            ->assertSee('Citra Finance')
            ->assertDontSee('Doni Tech');

        // 4. Filter by status: pending
        $this->actingAs($management)
            ->get(route('leave-requests.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee('Citra Finance')
            ->assertDontSee('Doni Tech');

        // 5. Filter by leave_type
        $this->actingAs($management)
            ->get(route('leave-requests.index', ['leave_type' => 'Cuti Menikah']))
            ->assertOk()
            ->assertSee('Citra Finance')
            ->assertDontSee('Doni Tech');
    }

    public function test_duration_calculation_is_inclusive(): void
    {
        $emp = $this->createEmployeeWithDept('Duration Tester', 'EMP-999');

        $leave = LeaveRequest::factory()->create([
            'employee_id' => $emp->id,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-03',
        ]);

        $this->assertSame(3, $leave->duration_days);
        $this->assertSame('3 Hari', $leave->duration_label);

        $singleDayLeave = LeaveRequest::factory()->create([
            'employee_id' => $emp->id,
            'start_date' => '2026-10-15',
            'end_date' => '2026-10-15',
        ]);

        $this->assertSame(1, $singleDayLeave->duration_days);
        $this->assertSame('1 Hari', $singleDayLeave->duration_label);
    }
}
