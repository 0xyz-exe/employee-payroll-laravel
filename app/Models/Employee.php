<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Employee extends Model { protected $fillable = ['employee_number','first_name','last_name','email','user_id','department_id','job_position_id','hired_at','terminated_at','status']; protected function casts(): array { return ['hired_at'=>'date','terminated_at'=>'date']; } public function user(): BelongsTo { return $this->belongsTo(User::class); } public function department(): BelongsTo { return $this->belongsTo(Department::class); } public function position(): BelongsTo { return $this->belongsTo(JobPosition::class,'job_position_id'); } public function components(): HasMany { return $this->hasMany(SalaryComponent::class); } public function entries(): HasMany { return $this->hasMany(PayrollEntry::class); } public function attendance(): HasMany { return $this->hasMany(AttendanceInput::class); } }
