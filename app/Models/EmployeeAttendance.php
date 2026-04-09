<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'work_date',
        'checked_in_at',
        'scheduled_start_at',
        'late_minutes',
        'late_half_hours',
        'penalty_rate_snapshot',
        'daily_salary_snapshot',
        'penalty_amount',
    ];

    protected $casts = [
        'work_date' => 'date',
        'checked_in_at' => 'datetime',
        'scheduled_start_at' => 'datetime',
        'late_minutes' => 'integer',
        'late_half_hours' => 'integer',
        'penalty_rate_snapshot' => 'decimal:2',
        'daily_salary_snapshot' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
