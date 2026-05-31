<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EmployeeReportController extends Controller
{
    public function daily(Request $request)
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'from' => ['nullable', 'date_format:H:i'],
            'to' => ['nullable', 'date_format:H:i'],
        ]);

        $date = $validated['date'] ?? now()->toDateString();
        $from = $validated['from'] ?? '09:00';
        $to = $validated['to'] ?? '22:00';

        $startAt = Carbon::parse($date.' '.$from, config('app.timezone'));
        $endAt = Carbon::parse($date.' '.$to, config('app.timezone'));

        $orders = Order::query()
            ->with('items')
            ->whereBetween('ordered_at', [$startAt, $endAt])
            ->orderBy('ordered_at')
            ->get();

        $attendances = EmployeeAttendance::query()
            ->whereDate('work_date', $date)
            ->get()
            ->keyBy('employee_id');

        $employees = Employee::query()
            ->orderBy('name')
            ->get()
            ->map(function (Employee $employee) use ($attendances) {
                $attendance = $attendances->get($employee->id);
                $baseSalary = $attendance ? (float) $attendance->daily_salary_snapshot : 0;
                $penaltyAmount = $attendance ? (float) $attendance->penalty_amount : 0;
                $salaryDue = max($baseSalary - $penaltyAmount, 0);

                return [
                    'employee_id' => $employee->id,
                    'name' => $employee->name,
                    'status' => $attendance ? ($attendance->late_minutes > 0 ? 'late' : 'on_time') : 'absent',
                    'checked_in_at' => $attendance?->checked_in_at,
                    'late_minutes' => $attendance?->late_minutes ?? 0,
                    'late_half_hours' => $attendance?->late_half_hours ?? 0,
                    'penalty_rate_per_half_hour' => $attendance?->penalty_rate_snapshot ?? $employee->penalty_rate_per_half_hour,
                    'daily_salary' => $attendance?->daily_salary_snapshot ?? $employee->daily_salary,
                    'penalty_amount' => round($penaltyAmount, 2),
                    'salary_due' => round($salaryDue, 2),
                ];
            })
            ->values();

        $totalRevenue = round((float) $orders->sum('total_amount'), 2);
        $totalPenaltyAmount = round((float) $employees->sum('penalty_amount'), 2);
        $totalSalaryDue = round((float) $employees->sum('salary_due'), 2);
        $soldItems = $orders->flatMap->items;
        $itemsSold = (int) $soldItems->sum('quantity');
        $kitchenItems = $soldItems->where('preparation_area', 'kitchen');
        $barItems = $soldItems->where('preparation_area', 'bar');
        $kitchenTotalAmount = round((float) $kitchenItems->sum('line_total'), 2);
        $barTotalAmount = round((float) $barItems->sum('line_total'), 2);
        $kitchenItemsSold = (int) $kitchenItems->sum('quantity');
        $barItemsSold = (int) $barItems->sum('quantity');
        $cafeOrders = $orders->where('fulfillment_type', 'cafe')->values();
        $pickupOrders = $orders->where('fulfillment_type', 'pickup')->values();
        $deliveryOrders = $orders->where('fulfillment_type', 'delivery')->values();

        return response()->json([
            'date' => $date,
            'period' => [
                'from' => $from,
                'to' => $to,
                'start_at' => $startAt->toDateTimeString(),
                'end_at' => $endAt->toDateTimeString(),
            ],
            'sales' => [
                'orders_count' => $orders->count(),
                'items_sold' => $itemsSold,
                'total_amount' => $totalRevenue,
                'by_fulfillment_type' => [
                    'cafe' => [
                        'orders_count' => $cafeOrders->count(),
                        'total_amount' => round((float) $cafeOrders->sum('total_amount'), 2),
                        'orders' => $cafeOrders,
                    ],
                    'pickup' => [
                        'orders_count' => $pickupOrders->count(),
                        'total_amount' => round((float) $pickupOrders->sum('total_amount'), 2),
                        'orders' => $pickupOrders,
                    ],
                    'delivery' => [
                        'orders_count' => $deliveryOrders->count(),
                        'total_amount' => round((float) $deliveryOrders->sum('total_amount'), 2),
                        'orders' => $deliveryOrders,
                    ],
                ],
                'by_preparation_area' => [
                    'kitchen' => [
                        'items_sold' => $kitchenItemsSold,
                        'total_amount' => $kitchenTotalAmount,
                    ],
                    'bar' => [
                        'items_sold' => $barItemsSold,
                        'total_amount' => $barTotalAmount,
                    ],
                ],
                'orders' => $orders,
            ],
            'payroll' => [
                'employees_count' => $employees->count(),
                'total_penalty_amount' => $totalPenaltyAmount,
                'total_salary_due' => $totalSalaryDue,
            ],
            'employees' => $employees,
        ]);
    }
}
