<?php

namespace Tests\Feature;

use App\Models\AttendanceInput;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JobPosition;
use App\Models\PayrollEntry;
use App\Models\PayrollPeriod;
use App\Models\SalaryComponent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role): User
    {
        $user=User::factory()->create(); $user->forceFill(['role'=>$role])->save(); return $user;
    }

    private function employee(string $number='E-100', ?User $user=null): Employee
    {
        $department=Department::firstOrCreate(['name'=>'Engineering']);
        $position=JobPosition::firstOrCreate(['department_id'=>$department->id,'title'=>'Developer']);
        $employee=Employee::create(['employee_number'=>$number,'first_name'=>'Sample','last_name'=>'Person','email'=>strtolower($number).'@example.test','user_id'=>$user?->id,'department_id'=>$department->id,'job_position_id'=>$position->id,'hired_at'=>'2020-01-01','status'=>'active']);
        SalaryComponent::create(['employee_id'=>$employee->id,'name'=>'Base','kind'=>'base','amount_minor'=>100000,'effective_from'=>'2020-01-01']);
        SalaryComponent::create(['employee_id'=>$employee->id,'name'=>'Meal','kind'=>'allowance','amount_minor'=>5000,'effective_from'=>'2020-01-01']);
        SalaryComponent::create(['employee_id'=>$employee->id,'name'=>'Advance','kind'=>'deduction','amount_minor'=>2000,'effective_from'=>'2020-01-01']);
        return $employee;
    }

    public function test_employee_cannot_access_hr_or_payroll_management(): void
    {
        $this->actingAs($this->account('employee'));
        $this->get(route('employees.index'))->assertForbidden();
        $this->get(route('payroll.index'))->assertForbidden();
        $this->post(route('payroll.store'),['starts_on'=>'2026-10-01','ends_on'=>'2026-10-31'])->assertForbidden();
    }

    public function test_payroll_draft_is_calculated_and_audited_from_salary_and_attendance(): void
    {
        $creator=$this->account('hr');
        $employee=$this->employee();
        AttendanceInput::create(['employee_id'=>$employee->id,'work_date'=>'2026-10-02','present'=>true,'overtime_minutes'=>60,'recorded_by'=>$creator->id]);
        config(['payroll.overtime_minor_per_hour'=>100]);
        $this->actingAs($creator)->post(route('payroll.store'),['starts_on'=>'2026-10-01','ends_on'=>'2026-10-31'])->assertRedirect();
        $period=PayrollPeriod::firstOrFail();
        $entry=$period->entries()->firstOrFail();
        $this->assertSame('draft',$period->status);
        $this->assertSame(1, $entry->attendance_days);
        $this->assertSame(103100,$entry->net_minor);
        $this->assertDatabaseHas('payroll_audits',['payroll_period_id'=>$period->id,'action'=>'payroll.draft_created']);
    }

    public function test_other_user_must_approve_and_only_approved_payroll_can_finalize(): void
    {
        $creator=$this->account('hr'); $approver=$this->account('payroll'); $finalizer=$this->account('admin'); $this->employee();
        $this->actingAs($creator)->post(route('payroll.store'),['starts_on'=>'2026-10-01','ends_on'=>'2026-10-31'])->assertRedirect(); $period=PayrollPeriod::firstOrFail();
        $this->actingAs($creator)->post(route('payroll.approve',$period))->assertForbidden();
        $this->actingAs($approver)->post(route('payroll.finalize',$period))->assertStatus(409);
        $this->actingAs($approver)->post(route('payroll.approve',$period))->assertRedirect();
        $this->actingAs($finalizer)->post(route('payroll.finalize',$period))->assertRedirect();
        $this->assertSame('finalized',$period->fresh()->status);
        $this->assertDatabaseHas('payroll_audits',['payroll_period_id'=>$period->id,'action'=>'payroll.approved']);
        $this->assertDatabaseHas('payroll_audits',['payroll_period_id'=>$period->id,'action'=>'payroll.finalized']);
    }

    public function test_employee_can_only_view_own_finalized_payslip(): void
    {
        $ownUser=$this->account('employee'); $otherUser=$this->account('employee'); $employee=$this->employee('E-OWN',$ownUser); $otherEmployee=$this->employee('E-OTHER',$otherUser);
        $creator=$this->account('hr'); $period=PayrollPeriod::create(['starts_on'=>'2026-10-01','ends_on'=>'2026-10-31','status'=>'finalized','created_by'=>$creator->id,'finalized_by'=>$creator->id,'finalized_at'=>now()]);
        $entry=$period->entries()->create(['employee_id'=>$employee->id,'attendance_days'=>1,'overtime_minutes'=>0,'base_minor'=>100000,'allowances_minor'=>0,'deductions_minor'=>0,'overtime_minor'=>0,'gross_minor'=>100000,'net_minor'=>100000,'calculation_snapshot'=>[]]);
        $this->actingAs($ownUser)->get(route('payslips.show',[$period,$entry]))->assertOk();
        $otherEntry=$period->entries()->create(['employee_id'=>$otherEmployee->id,'attendance_days'=>1,'overtime_minutes'=>0,'base_minor'=>100000,'allowances_minor'=>0,'deductions_minor'=>0,'overtime_minor'=>0,'gross_minor'=>100000,'net_minor'=>100000,'calculation_snapshot'=>[]]);
        $this->actingAs($ownUser)->get(route('payroll.payslip',[$period,$otherEntry]))->assertForbidden();
    }

    public function test_hr_can_manage_employee_compensation_and_audited_attendance(): void
    {
        $hr=$this->account('hr');
        $department=Department::create(['name'=>'Operations']);
        $position=JobPosition::create(['department_id'=>$department->id,'title'=>'Associate']);
        $this->actingAs($hr)->post(route('employees.store'),[
            'employee_number'=>'E-NEW','first_name'=>'Alex','last_name'=>'Example','email'=>'alex@example.test',
            'department_id'=>$department->id,'job_position_id'=>$position->id,'hired_at'=>'2026-01-01','base_minor'=>120000,
        ])->assertRedirect(route('employees.index'));
        $employee=Employee::where('employee_number','E-NEW')->firstOrFail();
        $this->actingAs($hr)->post(route('employees.components.store',$employee),[
            'name'=>'New base','kind'=>'base','amount_minor'=>150000,'effective_from'=>'2026-11-01',
        ])->assertRedirect();
        $this->assertDatabaseHas('salary_components',['employee_id'=>$employee->id,'kind'=>'base','amount_minor'=>120000,'effective_until'=>'2026-10-31 00:00:00']);
        $this->assertDatabaseHas('salary_components',['employee_id'=>$employee->id,'kind'=>'base','amount_minor'=>150000,'effective_from'=>'2026-11-01 00:00:00']);
        $this->actingAs($hr)->post(route('employees.attendance.store',$employee),[
            'work_date'=>'2026-10-02','present'=>0,'overtime_minutes'=>30,
        ])->assertStatus(422);
        $this->actingAs($hr)->post(route('employees.attendance.store',$employee),[
            'work_date'=>'2026-10-02','present'=>1,'overtime_minutes'=>90,
        ])->assertRedirect();
        $this->assertDatabaseHas('payroll_audits',['employee_id'=>$employee->id,'action'=>'attendance.saved']);
        $this->actingAs($hr)->get(route('employees.edit',$employee))->assertOk();
    }

    public function test_draft_cannot_be_exported_and_period_is_unique(): void
    {
        $hr=$this->account('payroll'); $this->employee(); $this->actingAs($hr)->post(route('payroll.store'),['starts_on'=>'2026-10-01','ends_on'=>'2026-10-31'])->assertRedirect(); $period=PayrollPeriod::firstOrFail();
        $this->actingAs($hr)->get(route('payroll.export',$period))->assertStatus(409);
        $this->actingAs($hr)->post(route('payroll.store'),['starts_on'=>'2026-10-01','ends_on'=>'2026-10-31'])->assertStatus(422);
    }
}
