<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\PayrollAudit;
use App\Models\PayrollEntry;
use App\Models\PayrollPeriod;
use App\Services\PayrollCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollRunController extends Controller
{
    public function index() { return view('payroll.index', ['periods'=>PayrollPeriod::with('creator')->withCount('entries')->latest('starts_on')->paginate(12)]); }
    public function store(Request $request, PayrollCalculator $calculator)
    {
        $data=$request->validate(['starts_on'=>['required','date'],'ends_on'=>['required','date','after_or_equal:starts_on']]); abort_if(Carbon::parse($data['starts_on'])->diffInDays(Carbon::parse($data['ends_on']))>366,422,'Payroll periods cannot exceed 366 days.'); abort_if(PayrollPeriod::where('starts_on','<=',$data['ends_on'])->where('ends_on','>=',$data['starts_on'])->exists(),422,'Payroll period overlaps an existing period.'); $actor=$request->user();
        $period=DB::transaction(function() use($data,$actor,$calculator){ $period=PayrollPeriod::create([...$data,'status'=>'draft','created_by'=>$actor->id]); $this->calculate($period,$calculator); PayrollAudit::create(['payroll_period_id'=>$period->id,'actor_id'=>$actor->id,'action'=>'payroll.draft_created','details'=>['entry_count'=>$period->entries()->count()]]); return $period; });
        return redirect()->route('payroll.show',$period)->with('success','Payroll draft calculated and saved.');
    }
    public function show(PayrollPeriod $period)
    {
        abort_unless(in_array(request()->user()->role,['admin','hr','payroll'],true),403); $period->load(['creator','entries.employee.department','entries.employee.position','audits.actor']); return view('payroll.show',compact('period'));
    }
    public function recalculate(Request $request, PayrollPeriod $period, PayrollCalculator $calculator)
    {
        DB::transaction(function() use($period,$calculator,$request){ $locked=PayrollPeriod::whereKey($period->id)->lockForUpdate()->firstOrFail(); abort_unless($locked->status==='draft',409); $locked->entries()->delete(); $this->calculate($locked,$calculator); PayrollAudit::create(['payroll_period_id'=>$locked->id,'actor_id'=>$request->user()->id,'action'=>'payroll.recalculated']); }); return back()->with('success','Draft recalculated from current salary and attendance inputs.');
    }
    public function approve(Request $request, PayrollPeriod $period)
    {
        DB::transaction(function() use($period,$request){ $locked=PayrollPeriod::whereKey($period->id)->lockForUpdate()->firstOrFail(); abort_unless($locked->status==='draft' && $locked->created_by!==$request->user()->id,409,'A different authorized user must approve this draft.'); $locked->update(['status'=>'approved','approved_by'=>$request->user()->id,'approved_at'=>now()]); PayrollAudit::create(['payroll_period_id'=>$locked->id,'actor_id'=>$request->user()->id,'action'=>'payroll.approved']); }); return back()->with('success','Payroll approved.');
    }
    public function finalize(Request $request, PayrollPeriod $period)
    {
        DB::transaction(function() use($period,$request){ $locked=PayrollPeriod::whereKey($period->id)->lockForUpdate()->firstOrFail(); abort_unless($locked->status==='approved',409,'Only approved payroll can be finalized.'); $locked->update(['status'=>'finalized','finalized_by'=>$request->user()->id,'finalized_at'=>now()]); PayrollAudit::create(['payroll_period_id'=>$locked->id,'actor_id'=>$request->user()->id,'action'=>'payroll.finalized']); }); return back()->with('success','Payroll finalized. Finalized entries are immutable.');
    }
    public function export(PayrollPeriod $period): StreamedResponse
    {
        abort_unless($period->status==='finalized',409,'Only finalized payroll can be exported.');
        return response()->streamDownload(function() use($period){ $out=fopen('php://output','w'); fputcsv($out,['Employee number','Name','Attendance days','Overtime minutes','Base minor units','Allowances minor units','Deductions minor units','Overtime minor units','Gross minor units','Net minor units']); $period->entries()->orderBy('employee_id')->chunk(500,function($entries) use($out){ foreach($entries as $entry){$person=$entry->calculation_snapshot['employee'];$safe=fn($v)=>preg_match('/^[=+@\\-\\t\\r]/',(string)$v)?"'".$v:$v;fputcsv($out,[$safe($person['employee_number']),$safe($person['name']),$entry->attendance_days,$entry->overtime_minutes,$entry->base_minor,$entry->allowances_minor,$entry->deductions_minor,$entry->overtime_minor,$entry->gross_minor,$entry->net_minor]);} }); fclose($out); },'payroll-'.$period->starts_on->format('Y-m-d').'.csv',['Content-Type'=>'text/csv; charset=UTF-8']);
    }
    public function mine(Request $request)
    {
        $employee=Employee::where('user_id',$request->user()->id)->firstOrFail();
        $entries=$employee->entries()->whereHas('period',fn($q)=>$q->where('status','finalized'))->with('period')->latest()->paginate(12);
        return view('payroll.mine',compact('entries'));
    }
    public function payslip(Request $request, PayrollPeriod $period, PayrollEntry $entry)
    {
        abort_unless($entry->payroll_period_id===$period->id && $period->status==='finalized',404); $employee=$entry->employee; abort_unless(in_array($request->user()->role,['admin','hr','payroll'],true) || $employee->user_id===$request->user()->id,403); return view('payroll.payslip',compact('period','entry','employee'));
    }
    private function calculate(PayrollPeriod $period, PayrollCalculator $calculator): void
    {
        $employees=Employee::with(['department','position','components'=>fn($q)=>$q->where('effective_from','<=',$period->ends_on)->where(fn($q)=>$q->whereNull('effective_until')->orWhere('effective_until','>=',$period->starts_on)),'attendance'=>fn($q)=>$q->whereBetween('work_date',[$period->starts_on,$period->ends_on])])->where('status','active')->whereDate('hired_at','<=',$period->ends_on)->where(fn($q)=>$q->whereNull('terminated_at')->orWhereDate('terminated_at','>=',$period->starts_on))->orderBy('id')->get();
        abort_if($employees->isEmpty(),422,'No active employees are eligible for this period.');
        foreach($employees as $employee){ $components=$employee->components; $base=(int)($components->where('kind','base')->sortByDesc('effective_from')->first()?->amount_minor??0); if($base<1) abort(422,"Employee {$employee->employee_number} needs an effective base salary before payroll can be calculated."); $allowances=(int)$components->where('kind','allowance')->sum('amount_minor'); $deductions=(int)$components->where('kind','deduction')->sum('amount_minor'); $days=$employee->attendance->where('present',true)->count(); $minutes=(int)$employee->attendance->sum('overtime_minutes'); $input=['base_minor'=>$base,'allowances_minor'=>$allowances,'deductions_minor'=>$deductions,'attendance_days'=>$days,'overtime_minutes'=>$minutes]; $totals=$calculator->calculate($input,(int)config('payroll.overtime_minor_per_hour')); $period->entries()->create([...$input,'employee_id'=>$employee->id,...$totals,'calculation_snapshot'=>['formula'=>'net = max(0, base + allowances + configured_overtime - deductions)','employee'=>['employee_number'=>$employee->employee_number,'name'=>$employee->first_name.' '.$employee->last_name,'department'=>$employee->department->name,'position'=>$employee->position->title],'components'=>$components->map(fn($c)=>['name'=>$c->name,'kind'=>$c->kind,'amount_minor'=>(int)$c->amount_minor])->values(),'overtime_minor_per_hour'=>(int)config('payroll.overtime_minor_per_hour')]]); }
    }
}
