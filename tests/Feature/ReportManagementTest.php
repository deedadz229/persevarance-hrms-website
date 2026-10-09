<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Overtime;
use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportManagementTest extends TestCase
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

    public function test_super_admin_can_view_reports_index_and_all_six_reports(): void
    {
        $admin = $this->userForRole('super_admin');

        $this->actingAs($admin)->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Pusat Laporan')
            ->assertSee('Laporan Absensi Bulanan')
            ->assertSee('Laporan Penggajian (Payroll)')
            ->assertSee('Laporan Cuti')
            ->assertSee('Laporan Lembur (Overtime)')
            ->assertSee('Laporan Turnover Karyawan')
            ->assertSee('Laporan Audit Trail');

        $this->actingAs($admin)->get(route('reports.attendances'))->assertOk();
        $this->actingAs($admin)->get(route('reports.leaves'))->assertOk();
        $this->actingAs($admin)->get(route('reports.overtimes'))->assertOk();
        $this->actingAs($admin)->get(route('reports.payrolls'))->assertOk();
        $this->actingAs($admin)->get(route('reports.turnover'))->assertOk();
        $this->actingAs($admin)->get(route('reports.audit-logs'))->assertOk();
    }

    public function test_manager_hrd_can_view_reports_index_and_all_six_reports(): void
    {
        $management = $this->userForRole('manager_hrd');

        $this->actingAs($management)->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Pusat Laporan')
            ->assertSee('Laporan Audit Trail');

        $this->actingAs($management)->get(route('reports.attendances'))->assertOk();
        $this->actingAs($management)->get(route('reports.leaves'))->assertOk();
        $this->actingAs($management)->get(route('reports.overtimes'))->assertOk();
        $this->actingAs($management)->get(route('reports.payrolls'))->assertOk();
        $this->actingAs($management)->get(route('reports.turnover'))->assertOk();
        $this->actingAs($management)->get(route('reports.audit-logs'))->assertOk();
    }

    public function test_taiwan_account_can_view_permitted_reports_but_forbidden_from_audit_logs(): void
    {
        $taiwan = $this->userForRole('taiwan_account');

        // 1. Taiwan CAN view main reports index
        $indexResponse = $this->actingAs($taiwan)->get(route('reports.index'));
        $indexResponse->assertOk();
        // Taiwan must not see the Audit Trail card or Export CSV button
        $indexResponse->assertDontSee('Laporan Audit Trail');
        $indexResponse->assertDontSee('Export CSV');

        // 2. Taiwan CAN view 5 operational reports
        $this->actingAs($taiwan)->get(route('reports.attendances'))->assertOk()->assertDontSee('Export CSV');
        $this->actingAs($taiwan)->get(route('reports.leaves'))->assertOk()->assertDontSee('Export CSV');
        $this->actingAs($taiwan)->get(route('reports.overtimes'))->assertOk()->assertDontSee('Export CSV');
        $this->actingAs($taiwan)->get(route('reports.payrolls'))->assertOk()->assertDontSee('Export CSV');
        $this->actingAs($taiwan)->get(route('reports.turnover'))->assertOk()->assertDontSee('Export CSV');

        // 3. Taiwan IS FORBIDDEN from Audit Trail (HTTP 403)
        $this->actingAs($taiwan)->get(route('reports.audit-logs'))->assertForbidden();
    }

    public function test_taiwan_account_is_forbidden_from_all_export_endpoints(): void
    {
        $taiwan = $this->userForRole('taiwan_account');

        $this->actingAs($taiwan)->get(route('reports.attendances.export'))->assertForbidden();
        $this->actingAs($taiwan)->get(route('reports.leaves.export'))->assertForbidden();
        $this->actingAs($taiwan)->get(route('reports.overtimes.export'))->assertForbidden();
        $this->actingAs($taiwan)->get(route('reports.payrolls.export'))->assertForbidden();
        $this->actingAs($taiwan)->get(route('reports.turnover.export'))->assertForbidden();
        $this->actingAs($taiwan)->get(route('reports.audit-logs.export'))->assertForbidden();
    }

    public function test_user_without_permission_receives_forbidden(): void
    {
        $userWithoutRole = new User([
            'name' => 'Unauthorized User',
            'email' => 'unauth@aranus.local',
        ]);

        $this->actingAs($userWithoutRole)->get(route('reports.index'))->assertForbidden();
    }

    public function test_attendance_report_filters_and_summary_work(): void
    {
        $management = $this->userForRole('manager_hrd');
        $emp1 = $this->createEmployeeWithDept('Karyawan Tepat Waktu', 'EMP-A01');
        $emp2 = $this->createEmployeeWithDept('Karyawan Telat', 'EMP-A02');

        Attendance::create([
            'employee_id' => $emp1->id,
            'attendance_date' => '2026-10-05',
            'check_in' => '08:00:00',
            'check_out' => '17:00:00',
            'location_status' => 'valid',
            'wifi_verified' => true,
            'ip_address' => '192.168.1.10',
            'face_verified' => true,
        ]);

        Attendance::create([
            'employee_id' => $emp2->id,
            'attendance_date' => '2026-10-05',
            'check_in' => '09:15:00',
            'check_out' => '17:30:00',
            'location_status' => 'late',
            'wifi_verified' => true,
            'ip_address' => '192.168.1.11',
            'face_verified' => true,
        ]);

        $response = $this->actingAs($management)->get(route('reports.attendances', [
            'month' => 10,
            'year' => 2026,
        ]));

        $response->assertOk();
        $response->assertSee('Karyawan Tepat Waktu');
        $response->assertSee('Karyawan Telat');
        $response->assertSee('Terlambat');

        // Filter by status telat
        $filteredResponse = $this->actingAs($management)->get(route('reports.attendances', [
            'month' => 10,
            'year' => 2026,
            'status' => 'telat',
        ]));

        $filteredResponse->assertOk();
        $filteredResponse->assertSee('Karyawan Telat');
        $filteredResponse->assertDontSee('Karyawan Tepat Waktu');
    }

    public function test_leave_report_filters_and_summary_work(): void
    {
        $management = $this->userForRole('manager_hrd');
        $emp = $this->createEmployeeWithDept('Karyawan Cuti', 'EMP-L01');

        LeaveRequest::factory()->create([
            'employee_id' => $emp->id,
            'leave_type' => 'Cuti Tahunan',
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-12',
            'status' => 'approved',
            'reason' => 'Liburan keluarga',
        ]);

        $response = $this->actingAs($management)->get(route('reports.leaves', [
            'month' => 10,
            'year' => 2026,
            'status' => 'approved',
        ]));

        $response->assertOk();
        $response->assertSee('Karyawan Cuti');
        $response->assertSee('Cuti Tahunan');
        $response->assertSee('3 Hari');
    }

    public function test_overtime_report_filters_and_summary_work(): void
    {
        $management = $this->userForRole('manager_hrd');
        $emp = $this->createEmployeeWithDept('Karyawan Lembur Report', 'EMP-O01');

        Overtime::factory()->create([
            'employee_id' => $emp->id,
            'overtime_date' => '2026-10-15',
            'start_time' => '18:00',
            'end_time' => '21:00',
            'duration' => 3.0,
            'status' => 'approved',
            'reason' => 'Perbaikan server web',
        ]);

        $response = $this->actingAs($management)->get(route('reports.overtimes', [
            'month' => 10,
            'year' => 2026,
            'status' => 'approved',
        ]));

        $response->assertOk();
        $response->assertSee('Karyawan Lembur Report');
        $response->assertSee('3 Jam');
        $response->assertSee('Perbaikan server web');
    }

    public function test_payroll_report_filters_and_summary_work(): void
    {
        $management = $this->userForRole('manager_hrd');
        $emp = $this->createEmployeeWithDept('Karyawan Payroll Report', 'EMP-P01');

        $payroll = Payroll::factory()->create([
            'employee_id' => $emp->id,
            'period_month' => 10,
            'period_year' => 2026,
            'basic_salary' => 8000000,
            'total_allowance' => 1000000,
            'total_deduction' => 500000,
            'net_salary' => 8500000,
            'status' => 'generated',
        ]);

        PayrollDetail::factory()->bpjs(300000)->create(['payroll_id' => $payroll->id]);
        PayrollDetail::factory()->pph21(200000)->create(['payroll_id' => $payroll->id]);

        $response = $this->actingAs($management)->get(route('reports.payrolls', [
            'month' => 10,
            'year' => 2026,
        ]));

        $response->assertOk();
        $response->assertSee('Karyawan Payroll Report');
        $response->assertSee('8.000.000');
        $response->assertSee('8.500.000');
        $response->assertSee('300.000'); // BPJS
        $response->assertSee('200.000'); // PPh21
    }

    public function test_turnover_report_filters_and_summary_work(): void
    {
        $management = $this->userForRole('manager_hrd');
        $dept = Department::factory()->create(['name' => 'Logistics Ops']);

        $empActive = $this->createEmployeeWithDept('Karyawan Aktif', 'EMP-T01', $dept);
        $empResigned = Employee::factory()->create([
            'department_id' => $dept->id,
            'name' => 'Karyawan Telah Resign',
            'employee_code' => 'EMP-T02',
            'status' => 'resigned',
            'resigned_at' => '2026-09-30',
        ]);

        $response = $this->actingAs($management)->get(route('reports.turnover', [
            'department_id' => $dept->id,
        ]));

        $response->assertOk();
        $response->assertSee('Karyawan Aktif');
        $response->assertSee('Karyawan Telah Resign');
        $response->assertSee('Resign');
    }

    public function test_audit_logs_report_filters_work(): void
    {
        $management = $this->userForRole('manager_hrd');

        AuditLog::create([
            'user_id' => $management->id,
            'action' => 'payroll_created',
            'entity_type' => 'App\Models\Payroll',
            'entity_id' => 99,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($management)->get(route('reports.audit-logs', [
            'action' => 'payroll_created',
        ]));

        $response->assertOk();
        $response->assertSee('Laporan Audit Trail');
        $response->assertSee('payroll_created');
        $response->assertSee('#99');
    }

    public function test_csv_exports_stream_valid_csv_and_log_audit(): void
    {
        $management = $this->userForRole('manager_hrd');
        $emp = $this->createEmployeeWithDept('Karyawan Export Tester', 'EMP-EXP01');

        Attendance::create([
            'employee_id' => $emp->id,
            'attendance_date' => '2026-10-05',
            'check_in' => '08:00:00',
            'check_out' => '17:00:00',
            'location_status' => 'valid',
            'wifi_verified' => true,
            'ip_address' => '192.168.1.10',
            'face_verified' => true,
        ]);

        $response = $this->actingAs($management)->get(route('reports.attendances.export', [
            'month' => 10,
            'year' => 2026,
        ]));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));

        // Verify audit log recorded
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'report_exported',
            'user_id' => $management->id,
            'entity_type' => 'Report',
        ]);
    }
}
