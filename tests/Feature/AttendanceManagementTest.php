<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceManagementTest extends TestCase
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
            ['name' => 'Operasional'],
            ['status' => 'active']
        );

        $position = Position::query()->firstOrCreate(
            ['department_id' => $department->id, 'name' => 'Staf Operasional'],
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

    public function test_super_admin_can_view_attendance_index_and_detail(): void
    {
        $admin = $this->userForRole('super_admin');
        $employee = $this->createEmployeeWithDept('Budi Prakoso', 'EMP-001');

        $attendance = Attendance::factory()->create([
            'employee_id' => $employee->id,
            'attendance_date' => now()->format('Y-m-d'),
        ]);

        // 1. Can view index
        $indexResponse = $this->actingAs($admin)->get(route('attendances.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Absensi Karyawan');
        $indexResponse->assertSee('Budi Prakoso');

        // 2. Can view detail
        $showResponse = $this->actingAs($admin)->get(route('attendances.show', $attendance));
        $showResponse->assertOk();
        $showResponse->assertSee('Detail Absensi Karyawan');
        $showResponse->assertSee('Budi Prakoso');
        $showResponse->assertSee('EMP-001');
    }

    public function test_management_can_view_attendance_index_and_detail(): void
    {
        $management = $this->userForRole('manager_hrd');
        $employee = $this->createEmployeeWithDept('Siti Rahma', 'EMP-002');

        $attendance = Attendance::factory()->create([
            'employee_id' => $employee->id,
            'attendance_date' => now()->format('Y-m-d'),
        ]);

        $this->actingAs($management)
            ->get(route('attendances.index'))
            ->assertOk()
            ->assertSee('Siti Rahma');

        $this->actingAs($management)
            ->get(route('attendances.show', $attendance))
            ->assertOk()
            ->assertSee('Siti Rahma');
    }

    public function test_taiwan_account_can_view_attendance_read_only_and_cannot_mutate(): void
    {
        $taiwan = $this->userForRole('taiwan_account');
        $employee = $this->createEmployeeWithDept('Tan Wei', 'EMP-003');

        $attendance = Attendance::factory()->create([
            'employee_id' => $employee->id,
            'attendance_date' => now()->format('Y-m-d'),
        ]);

        // 1. Taiwan CAN view index
        $this->actingAs($taiwan)
            ->get(route('attendances.index'))
            ->assertOk()
            ->assertSee('Tan Wei');

        // 2. Taiwan CAN view detail
        $this->actingAs($taiwan)
            ->get(route('attendances.show', $attendance))
            ->assertOk()
            ->assertSee('Tan Wei');

        // 3. Taiwan CANNOT submit any mutation (e.g. POST / PUT / DELETE)
        $this->actingAs($taiwan)
            ->post('/attendances', [
                'employee_id' => $employee->id,
                'attendance_date' => now()->format('Y-m-d'),
            ])
            ->assertStatus(405); // Route is not defined / Method not allowed

        $this->actingAs($taiwan)
            ->delete(route('attendances.show', $attendance))
            ->assertStatus(405);
    }

    public function test_user_without_view_attendance_permission_receives_forbidden(): void
    {
        $userWithoutPermission = new User([
            'name' => 'No Access User',
            'email' => 'noaccess@aranus.local',
        ]);

        $this->actingAs($userWithoutPermission)
            ->get(route('attendances.index'))
            ->assertForbidden();
    }

    public function test_search_and_filter_attendance(): void
    {
        $admin = $this->userForRole('super_admin');

        $deptIT = Department::factory()->create(['name' => 'Information Tech']);
        $deptHR = Department::factory()->create(['name' => 'Human Resources']);

        $empIT = $this->createEmployeeWithDept('Ahmad IT', 'EMP-111', $deptIT);
        $empHR = $this->createEmployeeWithDept('Bella HR', 'EMP-222', $deptHR);

        $today = now()->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');

        // On-time check-in today
        $attIT = Attendance::factory()->create([
            'employee_id' => $empIT->id,
            'attendance_date' => $today,
            'check_in' => $today.' 08:00:00',
        ]);

        // Late check-in today
        $attHR = Attendance::factory()->late()->create([
            'employee_id' => $empHR->id,
            'attendance_date' => $today,
        ]);

        // Yesterday check-in
        $attYesterday = Attendance::factory()->create([
            'employee_id' => $empIT->id,
            'attendance_date' => $yesterday,
        ]);

        // 1. Filter by date: today
        $this->actingAs($admin)
            ->get(route('attendances.index', ['date' => $today]))
            ->assertOk()
            ->assertSee('Ahmad IT')
            ->assertSee('Bella HR');

        // 2. Filter by search: employee code
        $this->actingAs($admin)
            ->get(route('attendances.index', ['date' => $today, 'search' => 'EMP-111']))
            ->assertOk()
            ->assertSee('Ahmad IT')
            ->assertDontSee('Bella HR');

        // 3. Filter by department
        $this->actingAs($admin)
            ->get(route('attendances.index', ['date' => $today, 'department_id' => $deptHR->id]))
            ->assertOk()
            ->assertSee('Bella HR')
            ->assertDontSee('Ahmad IT');

        // 4. Filter by status: telat
        $this->actingAs($admin)
            ->get(route('attendances.index', ['date' => $today, 'status' => 'telat']))
            ->assertOk()
            ->assertSee('Bella HR')
            ->assertDontSee('Ahmad IT');

        // 5. Filter by status: hadir
        $this->actingAs($admin)
            ->get(route('attendances.index', ['date' => $today, 'status' => 'hadir']))
            ->assertOk()
            ->assertSee('Ahmad IT')
            ->assertDontSee('Bella HR');
    }

    public function test_summary_metrics_calculation(): void
    {
        $admin = $this->userForRole('super_admin');
        $today = now()->format('Y-m-d');

        $emp1 = $this->createEmployeeWithDept('Emp Hadir', 'E-01');
        $emp2 = $this->createEmployeeWithDept('Emp Telat', 'E-02');
        $emp3 = $this->createEmployeeWithDept('Emp WFH', 'E-03');
        $emp4 = $this->createEmployeeWithDept('Emp Absen', 'E-04');

        // 1 On-time
        Attendance::factory()->create([
            'employee_id' => $emp1->id,
            'attendance_date' => $today,
            'check_in' => $today.' 08:10:00',
        ]);

        // 1 Late
        Attendance::factory()->late()->create([
            'employee_id' => $emp2->id,
            'attendance_date' => $today,
        ]);

        // 1 WFH
        Attendance::factory()->wfh()->create([
            'employee_id' => $emp3->id,
            'attendance_date' => $today,
        ]);

        // emp4 does not check in -> Absen!

        $response = $this->actingAs($admin)->get(route('attendances.index', ['date' => $today]));
        $response->assertOk();

        // Check summary view data
        $summary = $response->viewData('summary');
        $this->assertSame(1, $summary['hadir']);
        $this->assertSame(1, $summary['telat']);
        $this->assertSame(1, $summary['wfh']);
        $this->assertSame(1, $summary['absen']);
    }

    public function test_detail_page_handles_photo_presence_and_absence(): void
    {
        $admin = $this->userForRole('super_admin');
        $emp1 = $this->createEmployeeWithDept('Foto Tester 1', 'E-PHOTO1');
        $emp2 = $this->createEmployeeWithDept('Foto Tester 2', 'E-PHOTO2');

        // Without photo
        $attWithoutPhoto = Attendance::factory()->create([
            'employee_id' => $emp1->id,
            'photo_path' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('attendances.show', $attWithoutPhoto))
            ->assertOk()
            ->assertSee('Bukti foto tidak tersedia.');

        // With photo
        $attWithPhoto = Attendance::factory()->withPhoto()->create([
            'employee_id' => $emp2->id,
        ]);

        $this->actingAs($admin)
            ->get(route('attendances.show', $attWithPhoto))
            ->assertOk()
            ->assertSee('attendances/test_photo.jpg');
    }

    public function test_export_csv_streams_valid_attendance_data(): void
    {
        $admin = $this->userForRole('super_admin');
        $today = now()->format('Y-m-d');
        $emp = $this->createEmployeeWithDept('Export User', 'E-EXP');

        Attendance::factory()->create([
            'employee_id' => $emp->id,
            'attendance_date' => $today,
            'check_in' => $today.' 08:05:00',
        ]);

        $response = $this->actingAs($admin)->get(route('attendances.export', ['date' => $today]));
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        // Capture streamed response content
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('E-EXP', $content);
        $this->assertStringContainsString('Export User', $content);
        $this->assertStringContainsString('Tanggal', $content);
    }
}
