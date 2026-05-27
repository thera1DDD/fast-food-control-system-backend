<?php

use App\Http\Controllers\API\EmployeeAttendanceController;
use App\Http\Controllers\API\EmployeeAuthController;
use App\Http\Controllers\API\EmployeeReportController;
use App\Http\Controllers\API\MenuController;
use App\Http\Controllers\API\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::prefix('categories')->group(function () {
    Route::get('/', [MenuController::class, 'getCategories']);

});
Route::prefix('dishes')->group(function () {
    Route::get('search', [MenuController::class, 'searchDishes']);
    Route::get('{categoryId}', [MenuController::class, 'getDishesByCategory']);
});

Route::post('/orders', [OrderController::class, 'store']);
Route::get('/orders/incoming-announcements', [OrderController::class, 'incomingAnnouncements']);
Route::get('/orders/ready-announcements', [OrderController::class, 'readyAnnouncements']);

Route::prefix('employee')->group(function () {
    Route::post('/login', [EmployeeAuthController::class, 'login']);

    Route::middleware('employee.auth')->group(function () {
        Route::get('/me', [EmployeeAuthController::class, 'me']);
        Route::post('/logout', [EmployeeAuthController::class, 'logout']);
        Route::post('/attendance/check-in', [EmployeeAttendanceController::class, 'checkIn']);
        Route::get('/reports/daily', [EmployeeReportController::class, 'daily']);
        Route::get('/orders/current', [OrderController::class, 'current']);
        Route::get('/orders/daily', [OrderController::class, 'daily']);
        Route::delete('/orders', [OrderController::class, 'clear']);
        Route::patch('/orders/{order}/paid', [OrderController::class, 'markPaid']);
        Route::patch('/orders/{order}/unpaid', [OrderController::class, 'markUnpaid']);
        Route::patch('/orders/{order}/ready', [OrderController::class, 'markReady']);
        Route::patch('/orders/{order}/not-ready', [OrderController::class, 'markNotReady']);
        Route::post('/orders/{order}/incoming-announcement-played', [OrderController::class, 'markIncomingAnnouncementPlayed']);
        Route::post('/orders/{order}/announcement-played', [OrderController::class, 'markAnnouncementPlayed']);
    });
});
