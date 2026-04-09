<?php

namespace Tests\Feature;

use App\Models\Dishes;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class EmployeeOrderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_login_check_in_and_view_daily_report(): void
    {
        $employee = Employee::query()->create([
            'name' => 'Ivan',
            'password' => 'secret123',
            'penalty_rate_per_half_hour' => 10,
            'daily_salary' => 3000,
        ]);

        $dish = Dishes::query()->create([
            'name' => 'Soup',
            'price' => '250',
        ]);

        $loginResponse = $this->postJson('/api/employee/login', [
            'name' => 'Ivan',
            'password' => 'secret123',
        ]);

        $loginResponse
            ->assertOk()
            ->assertJsonPath('employee.id', $employee->id);

        $token = $loginResponse->json('token');

        $this->postJson('/api/orders', [
            'customer_name' => 'Customer',
            'items' => [
                [
                    'dish_id' => $dish->id,
                    'quantity' => 2,
                ],
            ],
        ])->assertCreated()
            ->assertJsonPath('order.total_amount', '500.00');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/employee/attendance/check-in')
            ->assertCreated()
            ->assertJsonPath('attendance.employee_id', $employee->id);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/employee/reports/daily?date='.now()->toDateString())
            ->assertOk()
            ->assertJsonPath('sales.orders_count', 1)
            ->assertJsonPath('sales.total_amount', 500)
            ->assertJsonPath('employees.0.name', 'Ivan');
    }

    public function test_employee_can_view_current_orders_and_mark_order_ready_with_announcement(): void
    {
        $employee = Employee::query()->create([
            'name' => 'Anna',
            'password' => 'secret123',
            'penalty_rate_per_half_hour' => 8,
            'daily_salary' => 2800,
        ]);

        $dish = Dishes::query()->create([
            'name' => 'Pizza',
            'price' => '450',
        ]);

        $token = $this->postJson('/api/employee/login', [
            'name' => 'Anna',
            'password' => 'secret123',
        ])->json('token');

        $orderId = $this->postJson('/api/orders', [
            'items' => [
                [
                    'dish_id' => $dish->id,
                    'quantity' => 1,
                ],
            ],
        ])->assertCreated()
            ->json('order.id');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/employee/orders/current?date='.now()->toDateString())
            ->assertOk()
            ->assertJsonPath('orders.0.id', $orderId)
            ->assertJsonPath('orders.0.is_ready', false)
            ->assertJsonPath('orders.0.order_number', $orderId);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/employee/orders/'.$orderId.'/ready')
            ->assertOk()
            ->assertJsonPath('order.is_ready', true)
            ->assertJsonPath('announcement.text', 'Заказ номер '.$orderId.' готов');

        $this->getJson('/api/orders/ready-announcements')
            ->assertOk()
            ->assertJsonPath('announcements.0.order_id', $orderId)
            ->assertJsonPath('announcements.0.text', 'Заказ номер '.$orderId.' готов');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/employee/orders/'.$orderId.'/announcement-played')
            ->assertOk();

        $this->getJson('/api/orders/ready-announcements')
            ->assertOk()
            ->assertJsonCount(0, 'announcements');
    }

    public function test_employee_logout_invalidates_token(): void
    {
        $employee = Employee::query()->create([
            'name' => 'Oleg',
            'password' => 'secret123',
            'penalty_rate_per_half_hour' => 5,
            'daily_salary' => 2500,
        ]);

        $token = $employee->createToken('employee-panel')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/employee/logout')
            ->assertOk();

        $this->assertDatabaseCount((new PersonalAccessToken())->getTable(), 0);
    }
}
