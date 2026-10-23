<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PayrollAudit extends Model { public $timestamps = false; public function actor(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(User::class,'actor_id'); } protected $fillable = ['payroll_period_id','employee_id','actor_id','action','details']; protected function casts(): array { return ['details'=>'array','created_at'=>'datetime']; } }
