<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id(); $table->string('name')->unique(); $table->timestamps();
        });
        Schema::create('job_positions', function (Blueprint $table) {
            $table->id(); $table->foreignId('department_id')->constrained()->cascadeOnDelete(); $table->string('title'); $table->timestamps(); $table->unique(['department_id','title']);
        });
        Schema::create('employees', function (Blueprint $table) {
            $table->id(); $table->string('employee_number')->unique(); $table->string('first_name'); $table->string('last_name'); $table->string('email')->unique(); $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete(); $table->foreignId('department_id')->constrained()->restrictOnDelete(); $table->foreignId('job_position_id')->constrained()->restrictOnDelete(); $table->date('hired_at'); $table->date('terminated_at')->nullable(); $table->string('status', 20)->default('active')->index(); $table->timestamps();
        });
        Schema::create('salary_components', function (Blueprint $table) {
            $table->id(); $table->foreignId('employee_id')->constrained()->cascadeOnDelete(); $table->string('name'); $table->string('kind', 20); $table->unsignedBigInteger('amount_minor'); $table->date('effective_from'); $table->date('effective_until')->nullable(); $table->timestamps(); $table->index(['employee_id','effective_from','effective_until']);
        });
        Schema::create('attendance_inputs', function (Blueprint $table) {
            $table->id(); $table->foreignId('employee_id')->constrained()->cascadeOnDelete(); $table->date('work_date'); $table->boolean('present')->default(true); $table->unsignedInteger('overtime_minutes')->default(0); $table->foreignId('recorded_by')->constrained('users'); $table->timestamps(); $table->unique(['employee_id','work_date']);
        });
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id(); $table->date('starts_on'); $table->date('ends_on'); $table->string('status', 20)->default('draft')->index(); $table->foreignId('created_by')->constrained('users'); $table->foreignId('approved_by')->nullable()->constrained('users'); $table->foreignId('finalized_by')->nullable()->constrained('users'); $table->timestamp('approved_at')->nullable(); $table->timestamp('finalized_at')->nullable(); $table->timestamps(); $table->unique(['starts_on','ends_on']);
        });
        Schema::create('payroll_entries', function (Blueprint $table) {
            $table->id(); $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete(); $table->foreignId('employee_id')->constrained(); $table->unsignedInteger('attendance_days')->default(0); $table->unsignedInteger('overtime_minutes')->default(0); $table->unsignedBigInteger('base_minor'); $table->unsignedBigInteger('allowances_minor'); $table->unsignedBigInteger('deductions_minor'); $table->unsignedBigInteger('overtime_minor'); $table->unsignedBigInteger('gross_minor'); $table->unsignedBigInteger('net_minor'); $table->json('calculation_snapshot'); $table->timestamps(); $table->unique(['payroll_period_id','employee_id']);
        });
        Schema::create('payroll_audits', function (Blueprint $table) {
            $table->id(); $table->foreignId('payroll_period_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('actor_id')->constrained('users'); $table->string('action', 50); $table->json('details')->nullable(); $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_audits'); Schema::dropIfExists('payroll_entries'); Schema::dropIfExists('payroll_periods'); Schema::dropIfExists('attendance_inputs'); Schema::dropIfExists('salary_components'); Schema::dropIfExists('employees'); Schema::dropIfExists('job_positions'); Schema::dropIfExists('departments');
    }
};
