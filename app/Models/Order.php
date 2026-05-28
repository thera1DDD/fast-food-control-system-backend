<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $appends = [
        'order_number',
    ];

    protected $fillable = [
        'customer_name',
        'customer_phone',
        'notes',
        'source',
        'fulfillment_type',
        'ordered_at',
        'total_amount',
        'is_paid',
        'paid_at',
        'is_ready',
        'ready_at',
        'order_sound_requested_at',
        'order_sound_played_at',
        'ready_sound_requested_at',
        'ready_sound_played_at',
    ];

    protected $casts = [
        'ordered_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'is_paid' => 'boolean',
        'paid_at' => 'datetime',
        'is_ready' => 'boolean',
        'ready_at' => 'datetime',
        'order_sound_requested_at' => 'datetime',
        'order_sound_played_at' => 'datetime',
        'ready_sound_requested_at' => 'datetime',
        'ready_sound_played_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getOrderNumberAttribute(): int
    {
        return $this->id;
    }
}
