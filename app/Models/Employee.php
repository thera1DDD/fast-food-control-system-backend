<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Employee extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'password',
        'penalty_rate_per_half_hour',
        'daily_salary',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
        'penalty_rate_per_half_hour' => 'decimal:2',
        'daily_salary' => 'decimal:2',
    ];

    public function attendances()
    {
        return $this->hasMany(EmployeeAttendance::class);
    }
}
