<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PayrollManagementTest extends TestCase
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

    public function test_management_can_open_payroll_index_and_detail(): void
    {
        $management = $this->userForRole('manager_hrd');
        $emp = $this->createEmployeeWithDept('Budi Santoso', 'EMP-101');

        $payroll = Payroll::factory()->create([
            'employee_id' => $emp->id,
            'period_month' => 10,
            'period_year' => 2026,
            'basic_salary' => 8000000,
            'net_salary' => 8000000,
        ]);

        $this->actingAs($management)
            ->get(route('payrolls.index'))
            ->assertOk()
            ->assertSee('Manajemen Payroll')
            ->assertSee('Budi Santoso');

        $this->actingAs($management)
            ->get(route('payrolls.show', $payroll))
            ->assertOk()
            ->assertSee('Detail Payroll Karyawan')
            ->assertSee('Budi Santoso')
            ->assertSee('8.000.000');
    }

    public function test_super_admin_can_open_payroll_index_and_detail(): void
    {
        $admin = $this->userForRole('super_admin');
        $emp = $this->createEmployeeWithDept('Dewi Lestari', 'EMP-102');

        $payroll = Payroll::factory()->create([
            'employee_id' => $emp->id,
            'period_month' => 10,
            'period_year' => 2026,
            'basic_salary' => 9000000,
            'net_salary' => 9000000,
        ]);

        $this->actingAs($admin)
            ->get(route('payrolls.index'))
            ->assertOk()
            ->assertSee('Dewi Lestari');

        $this->actingAs($admin)
            ->get(route('payrolls.show', $payroll))
            ->assertOk()
            ->assertSee('Dewi Lestari')
            ->assertSee('9.000.000');
    }

    public function test_taiwan_account_can_view_payroll_but_cannot_create_or_modify(): void
    {
        $taiwan = $this->userForRole('taiwan_account');
        $emp = $this->createEmployeeWithDept('Tan Wei', 'EMP-103');

        $payroll = Payroll::factory()->create([
            'employee_id' => $emp->id,
            'period_month' => 10,
            'period_year' => 2026,
            'basic_salary' => 10000000,
            'net_salary' => 10000000,
            'status' => 'draft',
        ]);

        // 1. Taiwan can view index
        $indexResponse = $this->actingAs($taiwan)->get(route('payrolls.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Tan Wei');
        $indexResponse->assertDontSee('+ Proses / Buat Payroll');

        // 2. Taiwan can view detail
        $showResponse = $this->actingAs($taiwan)->get(route('payrolls.show', $payroll));
        $showResponse->assertOk();
        $showResponse->assertSee('Tan Wei');
        $showResponse->assertDontSee('Koreksi Payroll');
        $showResponse->assertDontSee('Finalisasi / Generate Slip Gaji');

        // 3. Taiwan can view slip
        $this->actingAs($taiwan)
            ->get(route('payrolls.slip', $payroll))
            ->assertOk()
            ->assertSee('SLIP GAJI KARYAWAN')
            ->assertSee('Tan Wei');

        // 4. Taiwan cannot access create page (403)
        $this->actingAs($taiwan)
            ->get(route('payrolls.create'))
            ->assertForbidden();

        // 5. Taiwan cannot submit payroll (403)
        $this->actingAs($taiwan)
            ->post(route('payrolls.store'), [
                'employee_id' => $emp->id,
                'period_month' => 11,
                'period_year' => 2026,
                'basic_salary' => 10000000,
            ])
            ->assertForbidden();

        // 6. Taiwan cannot access correction page (403)
        $this->actingAs($taiwan)
            ->get(route('payrolls.correction', $payroll))
            ->assertForbidden();

        // 7. Taiwan cannot submit correction (403)
        $this->actingAs($taiwan)
            ->post(route('payrolls.update-correction', $payroll), [
                'correction_reason' => 'Koreksi oleh Taiwan',
                'basic_salary' => 11000000,
            ])
            ->assertForbidden();

        // 8. Taiwan cannot generate / finalize payroll (403)
        $this->actingAs($taiwan)
            ->post(route('payrolls.generate', $payroll))
            ->assertForbidden();

        $this->assertSame('draft', $payroll->fresh()->status);
    }

    public function test_user_without_permission_receives_forbidden(): void
    {
        $userWithoutRole = new User([
            'name' => 'Unauthorized User',
            'email' => 'unauth@aranus.local',
        ]);

        $this->actingAs($userWithoutRole)
            ->get(route('payrolls.index'))
            ->assertForbidden();
    }

    public function test_search_and_filter_functionality_works(): void
    {
        $management = $this->userForRole('manager_hrd');

        $deptProduction = Department::factory()->create(['name' => 'Production Assembly']);
        $deptTech = Department::factory()->create(['name' => 'IT Software']);

        $empProd = $this->createEmployeeWithDept('Bambang Pabrik', 'EMP-701', $deptProduction);
        $empTech = $this->createEmployeeWithDept('Siti Programmer', 'EMP-702', $deptTech);

        Payroll::factory()->create([
            'employee_id' => $empProd->id,
            'period_month' => 8,
            'period_year' => 2026,
            'status' => 'draft',
        ]);

        Payroll::factory()->create([
            'employee_id' => $empTech->id,
            'period_month' => 9,
            'period_year' => 2026,
            'status' => 'generated',
        ]);

        // 1. Search by employee name
        $this->actingAs($management)
            ->get(route('payrolls.index', ['search' => 'Bambang']))
            ->assertOk()
            ->assertSee('Bambang Pabrik')
            ->assertDontSee('Siti Programmer');

        // 2. Search by employee code
        $this->actingAs($management)
            ->get(route('payrolls.index', ['search' => 'EMP-702']))
            ->assertOk()
            ->assertSee('Siti Programmer')
            ->assertDontSee('Bambang Pabrik');

        // 3. Filter by department
        $this->actingAs($management)
            ->get(route('payrolls.index', ['department_id' => $deptProduction->id]))
            ->assertOk()
            ->assertSee('Bambang Pabrik')
            ->assertDontSee('Siti Programmer');

        // 4. Filter by month
        $this->actingAs($management)
            ->get(route('payrolls.index', ['month' => 8]))
            ->assertOk()
            ->assertSee('Bambang Pabrik')
            ->assertDontSee('Siti Programmer');

        // 5. Filter by status
        $this->actingAs($management)
            ->get(route('payrolls.index', ['status' => 'generated']))
            ->assertOk()
            ->assertSee('Siti Programmer')
            ->assertDontSee('Bambang Pabrik');
    }

    public function test_net_salary_calculation_and_components_are_accurate(): void
    {
        $management = $this->userForRole('manager_hrd');
        $emp = $this->createEmployeeWithDept('Kalkulasi Akurat', 'EMP-801');

        $response = $this->actingAs($management)->post(route('payrolls.store'), [
            'employee_id' => $emp->id,
            'period_month' => 10,
            'period_year' => 2026,
            'basic_salary' => 7000000,
            'allowances' => [
                ['name' => 'Tunjangan Transport', 'amount' => 500000, 'description' => 'BBM bulanan'],
                ['name' => 'Tunjangan Makan', 'amount' => 500000, 'description' => 'Uang makan bulanan'],
            ],
            'deductions' => [
                ['name' => 'BPJS Kesehatan', 'amount' => 200000, 'description' => 'Iuran BPJS'],
                ['name' => 'PPh21', 'amount' => 150000, 'description' => 'Pajak PPh21'],
            ],
            'status' => 'draft',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify database: 7000000 + 1000000 - 350000 = 7650000
        $payroll = Payroll::where('employee_id', $emp->id)->where('period_month', 10)->firstOrFail();
        $this->assertEquals(7000000.0, (float) $payroll->basic_salary);
        $this->assertEquals(1000000.0, (float) $payroll->total_allowance);
        $this->assertEquals(350000.0, (float) $payroll->total_deduction);
        $this->assertEquals(7650000.0, (float) $payroll->net_salary);

        // Verify details count
        $this->assertCount(2, $payroll->allowances);
        $this->assertCount(2, $payroll->deductions);

        // Verify summary cards on index count BPJS & PPh21 correctly
        $indexResponse = $this->actingAs($management)->get(route('payrolls.index', ['month' => 10, 'year' => 2026]));
        $indexResponse->assertOk();
        $indexResponse->assertSee('200.000'); // BPJS
        $indexResponse->assertSee('150.000'); // PPh21
    }

    public function test_duplicate_payroll_for_same_employee_and_period_is_rejected(): void
    {
        $management = $this->userForRole('manager_hrd');
        $emp = $this->createEmployeeWithDept('Duplikat Tester', 'EMP-802');

        // Create initial payroll for October 2026
        Payroll::factory()->create([
            'employee_id' => $emp->id,
            'period_month' => 10,
            'period_year' => 2026,
            'basic_salary' => 6000000,
        ]);

        // Attempt to create second payroll for the same employee in October 2026
        $response = $this->actingAs($management)->post(route('payrolls.store'), [
            'employee_id' => $emp->id,
            'period_month' => 10,
            'period_year' => 2026,
            'basic_salary' => 6500000,
        ]);

        $response->assertSessionHasErrors(['employee_id']);
        $this->assertCount(1, Payroll::where('employee_id', $emp->id)->where('period_month', 10)->get());
    }

    public function test_slip_gaji_displays_correct_data(): void
    {
        $management = $this->userForRole('manager_hrd');
        $emp = $this->createEmployeeWithDept('Karyawan Slip', 'EMP-803');

        $payroll = Payroll::factory()->create([
            'employee_id' => $emp->id,
            'period_month' => 10,
            'period_year' => 2026,
            'basic_salary' => 8000000,
            'total_allowance' => 1000000,
            'total_deduction' => 500000,
            'net_salary' => 8500000,
        ]);

        PayrollDetail::factory()->allowance('Tunjangan Kinerja', 1000000)->create(['payroll_id' => $payroll->id]);
        PayrollDetail::factory()->bpjs(300000)->create(['payroll_id' => $payroll->id]);
        PayrollDetail::factory()->pph21(200000)->create(['payroll_id' => $payroll->id]);

        $response = $this->actingAs($management)->get(route('payrolls.slip', $payroll));
        $response->assertOk();
        $response->assertSee('SLIP GAJI KARYAWAN');
        $response->assertSee('Karyawan Slip');
        $response->assertSee('EMP-803');
        $response->assertSee('8.000.000');
        $response->assertSee('1.000.000');
        $response->assertSee('8.500.000');
    }

    public function test_department_summary_aggregates_correctly(): void
    {
        $management = $this->userForRole('manager_hrd');

        $deptSales = Department::factory()->create(['name' => 'Commercial Sales']);
        $emp1 = $this->createEmployeeWithDept('Sales A', 'SLS-01', $deptSales);
        $emp2 = $this->createEmployeeWithDept('Sales B', 'SLS-02', $deptSales);

        Payroll::factory()->create([
            'employee_id' => $emp1->id,
            'period_month' => 10,
            'period_year' => 2026,
            'basic_salary' => 5000000,
            'total_allowance' => 1000000,
            'total_deduction' => 500000,
            'net_salary' => 5500000,
        ]);

        Payroll::factory()->create([
            'employee_id' => $emp2->id,
            'period_month' => 10,
            'period_year' => 2026,
            'basic_salary' => 6000000,
            'total_allowance' => 1500000,
            'total_deduction' => 500000,
            'net_salary' => 7000000,
        ]);

        $response = $this->actingAs($management)->get(route('payrolls.department-summary', [
            'month' => 10,
            'year' => 2026,
        ]));

        $response->assertOk();
        $response->assertSee('Commercial Sales');
        $response->assertSee('2 Orang');
        $response->assertSee('11.000.000'); // 5M + 6M basic
        $response->assertSee('12.500.000'); // 5.5M + 7M net
    }

    public function test_payroll_correction_requires_reason_and_recalculates_net_salary(): void
    {
        $management = $this->userForRole('manager_hrd');
        $emp = $this->createEmployeeWithDept('Koreksi Tester', 'EMP-804');

        $payroll = Payroll::factory()->create([
            'employee_id' => $emp->id,
            'period_month' => 10,
            'period_year' => 2026,
            'basic_salary' => 5000000,
            'total_allowance' => 0,
            'total_deduction' => 0,
            'net_salary' => 5000000,
        ]);

        // 1. Correction without reason fails
        $failResponse = $this->actingAs($management)->post(route('payrolls.update-correction', $payroll), [
            'correction_reason' => '',
            'basic_salary' => 5500000,
        ]);
        $failResponse->assertSessionHasErrors(['correction_reason']);
        $this->assertEquals(5000000.0, (float) $payroll->fresh()->net_salary);

        // 2. Correction with reason succeeds and recalculates net salary
        $successResponse = $this->actingAs($management)->post(route('payrolls.update-correction', $payroll), [
            'correction_reason' => 'Penyesuaian kenaikan gaji pokok resmi',
            'basic_salary' => 6000000,
            'allowances' => [
                ['name' => 'Tunjangan Baru', 'amount' => 1000000],
            ],
            'deductions' => [
                ['name' => 'Potongan Baru', 'amount' => 500000],
            ],
        ]);

        $successResponse->assertRedirect(route('payrolls.show', $payroll));
        $fresh = $payroll->fresh();
        $this->assertEquals(6000000.0, (float) $fresh->basic_salary);
        $this->assertEquals(1000000.0, (float) $fresh->total_allowance);
        $this->assertEquals(500000.0, (float) $fresh->total_deduction);
        $this->assertEquals(6500000.0, (float) $fresh->net_salary);

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payroll_corrected',
            'user_id' => $management->id,
            'entity_type' => Payroll::class,
            'entity_id' => $payroll->id,
        ]);
    }

    public function test_generate_payroll_updates_status_and_logs_audit(): void
    {
        $management = $this->userForRole('manager_hrd');
        $emp = $this->createEmployeeWithDept('Generate Tester', 'EMP-805');

        $payroll = Payroll::factory()->create([
            'employee_id' => $emp->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($management)->post(route('payrolls.generate', $payroll));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $fresh = $payroll->fresh();
        $this->assertSame('generated', $fresh->status);
        $this->assertNotNull($fresh->generated_at);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payroll_generated',
            'user_id' => $management->id,
            'entity_type' => Payroll::class,
            'entity_id' => $payroll->id,
        ]);
    }

    public function test_transaction_rolls_back_if_exception_occurs(): void
    {
        $management = $this->userForRole('manager_hrd');
        $emp = $this->createEmployeeWithDept('Rollback Tester', 'EMP-806');

        $initialCount = Payroll::count();

        try {
            DB::transaction(function () use ($emp) {
                Payroll::create([
                    'employee_id' => $emp->id,
                    'period_month' => 12,
                    'period_year' => 2026,
                    'basic_salary' => 5000000,
                    'total_allowance' => 0,
                    'total_deduction' => 0,
                    'net_salary' => 5000000,
                    'status' => 'draft',
                ]);

                throw new Exception('Simulated database error during transaction.');
            });
        } catch (Exception $e) {
            // expected
        }

        // Must remain rolled back
        $this->assertSame($initialCount, Payroll::count());
    }

    public function test_evaluation_page_displays_reference_data(): void
    {
        $management = $this->userForRole('manager_hrd');
        $emp = $this->createEmployeeWithDept('Eval Tester', 'EMP-807');

        Payroll::factory()->create([
            'employee_id' => $emp->id,
            'period_month' => 10,
            'period_year' => 2026,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($management)->get(route('payrolls.evaluation', [
            'month' => 10,
            'year' => 2026,
        ]));

        $response->assertOk();
        $response->assertSee('Evaluasi Pra-Finalisasi Payroll');
        $response->assertSee('Eval Tester');
        $response->assertSee('Hari');
        $response->assertSee('Jam');
    }
}
