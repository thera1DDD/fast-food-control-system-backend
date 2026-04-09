<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('work_date');
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('scheduled_start_at')->nullable();
            $table->unsignedInteger('late_minutes')->default(0);
            $table->unsignedInteger('late_half_hours')->default(0);
            $table->decimal('penalty_rate_snapshot', 5, 2)->default(0);
            $table->decimal('daily_salary_snapshot', 10, 2)->default(0);
            $table->decimal('penalty_amount', 10, 2)->default(0);
            $table->timestamps();

            $table->unique(['employee_id', 'work_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_attendances');
    }
};
