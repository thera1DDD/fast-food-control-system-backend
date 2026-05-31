<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EmployeeAttendance;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EmployeeAttendanceController extends Controller
{
    public function checkIn(Request $request)
    {
        $employee = $request->user();
        $now = now();
        $workDate = $now->toDateString();
        $scheduledStart = Carbon::parse($workDate.' 09:00:00', config('app.timezone'));
        $lateMinutes = max(0, $scheduledStart->diffInMinutes($now, false));
        $latePenaltyUnits = (int) ceil($lateMinutes / 5);
        $dailySalary = (float) $employee->daily_salary;
        $penaltyRate = (float) $employee->penalty_rate_per_half_hour;
        $penaltyAmount = round($dailySalary * (($penaltyRate * $latePenaltyUnits) / 100), 2);
        $isNew = false;

        $attendance = EmployeeAttendance::query()->firstOrNew([
            'employee_id' => $employee->id,
            'work_date' => $workDate,
        ]);

        if (! $attendance->exists) {
            $isNew = true;
        }

        if ($attendance->checked_in_at) {
            return response()->json([
                'message' => 'Check-in already exists for today.',
                'attendance' => $attendance,
            ]);
        }

        $attendance->fill([
            'checked_in_at' => $now,
            'scheduled_start_at' => $scheduledStart,
            'late_minutes' => $lateMinutes,
            'late_half_hours' => $latePenaltyUnits,
            'penalty_rate_snapshot' => $penaltyRate,
            'daily_salary_snapshot' => $dailySalary,
            'penalty_amount' => $penaltyAmount,
        ]);
        $attendance->save();

        return response()->json([
            'message' => $isNew ? 'Check-in saved.' : 'Check-in updated.',
            'attendance' => $attendance,
            'salary_due' => max($dailySalary - $penaltyAmount, 0),
        ], $isNew ? 201 : 200);
    }
}
