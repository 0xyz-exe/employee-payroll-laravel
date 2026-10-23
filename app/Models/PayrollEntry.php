<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class PayrollEntry extends Model { protected $fillable = ['payroll_period_id','employee_id','attendance_days','overtime_minutes','base_minor','allowances_minor','deductions_minor','overtime_minor','gross_minor','net_minor','calculation_snapshot']; protected function casts(): array { return ['calculation_snapshot'=>'array']; } public function period(): BelongsTo { return $this->belongsTo(PayrollPeriod::class,'payroll_period_id'); } public function employee(): BelongsTo { return $this->belongsTo(Employee::class); } }
