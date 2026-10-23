<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class SalaryComponent extends Model { protected $fillable = ['employee_id','name','kind','amount_minor','effective_from','effective_until']; protected function casts(): array { return ['effective_from'=>'date','effective_until'=>'date','amount_minor'=>'integer']; } public function employee(): BelongsTo { return $this->belongsTo(Employee::class); } }
