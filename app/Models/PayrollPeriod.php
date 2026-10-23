<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class PayrollPeriod extends Model { protected $fillable = ['starts_on','ends_on','status','created_by','approved_by','finalized_by','approved_at','finalized_at']; protected function casts(): array { return ['starts_on'=>'date','ends_on'=>'date','approved_at'=>'datetime','finalized_at'=>'datetime']; } public function entries(): HasMany { return $this->hasMany(PayrollEntry::class); } public function creator(): BelongsTo { return $this->belongsTo(User::class,'created_by'); } public function audits(): HasMany { return $this->hasMany(PayrollAudit::class); } }
