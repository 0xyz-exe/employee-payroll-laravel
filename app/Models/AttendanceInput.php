<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AttendanceInput extends Model { protected $fillable = ['employee_id','work_date','present','overtime_minutes','recorded_by']; protected function casts(): array { return ['work_date'=>'date','present'=>'boolean','overtime_minutes'=>'integer']; } public function employee(): BelongsTo { return $this->belongsTo(Employee::class); } }
