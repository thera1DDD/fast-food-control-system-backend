<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Dishes;
use App\Models\Order;
use App\Support\PriceHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OrderController extends Controller
{
    public function current(Request $request)
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'ready' => ['nullable', 'boolean'],
            'paid' => ['nullable', 'boolean'],
        ]);

        $date = $validated['date'] ?? now()->toDateString();

        $query = Order::query()
            ->with('items')
            ->whereDate('ordered_at', $date)
            ->orderByDesc('ordered_at');

        if (array_key_exists('ready', $validated)) {
            $query->where('is_ready', (bool) $validated['ready']);
        }

        if (array_key_exists('paid', $validated)) {
            $query->where('is_paid', (bool) $validated['paid']);
        }

        $orders = $query->get();

        return response()->json([
            'date' => $date,
            'summary' => [
                'orders_count' => $orders->count(),
                'paid_count' => $orders->where('is_paid', true)->count(),
                'unpaid_count' => $orders->where('is_paid', false)->count(),
                'delivery_count' => $orders->where('fulfillment_type', 'delivery')->count(),
                'pickup_count' => $orders->where('fulfillment_type', 'pickup')->count(),
            ],
            'orders' => $orders,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'source' => ['nullable', 'string', 'max:50'],
            'fulfillment_type' => ['nullable', 'string', 'in:pickup,delivery'],
            'ordered_at' => ['nullable', 'date'],
            'is_paid' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.dish_id' => ['required', 'integer', 'exists:dishes,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $dishIds = collect($validated['items'])
            ->pluck('dish_id')
            ->unique()
            ->values();

        $dishes = Dishes::query()
            ->whereIn('id', $dishIds)
            ->get()
            ->keyBy('id');

        $preparedItems = [];
        $totalAmount = 0;

        foreach ($validated['items'] as $item) {
            $dish = $dishes->get($item['dish_id']);
            $unitPrice = PriceHelper::normalize($dish?->getRawOriginal('price'));
            $quantity = (int) $item['quantity'];
            $lineTotal = round($unitPrice * $quantity, 2);

            $preparedItems[] = [
                'dish_id' => $dish->id,
                'dish_name' => $dish->name,
                'preparation_area' => $dish->preparation_area ?: 'kitchen',
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ];

            $totalAmount += $lineTotal;
        }

        $order = DB::transaction(function () use ($validated, $preparedItems, $totalAmount) {
            $isPaid = (bool) ($validated['is_paid'] ?? false);
            $now = now();

            $order = Order::query()->create([
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'source' => $validated['source'] ?? 'website',
                'fulfillment_type' => $validated['fulfillment_type'] ?? 'pickup',
                'ordered_at' => $validated['ordered_at'] ?? $now,
                'total_amount' => round($totalAmount, 2),
                'is_paid' => $isPaid,
                'paid_at' => $isPaid ? $now : null,
                'is_ready' => false,
                'order_sound_requested_at' => $now,
                'order_sound_played_at' => null,
            ]);

            $order->items()->createMany($preparedItems);

            return $order->load('items');
        });

        return response()->json([
            'message' => 'Order saved successfully.',
            'order' => $order,
            'notification' => [
                'type' => 'new_order',
                'sound' => 'loud',
                'text' => 'New order #'.$order->order_number,
            ],
        ], 201);
    }

    public function daily(Request $request)
    {
        return $this->current($request);
    }

    public function markReady(Order $order)
    {
        $now = now();

        $order->forceFill([
            'is_ready' => true,
            'ready_at' => $now,
            'ready_sound_requested_at' => $now,
            'ready_sound_played_at' => null,
        ])->save();

        return response()->json([
            'message' => 'Order marked as ready.',
            'order' => $order->load('items'),
            'announcement' => [
                'text' => 'Заказ номер '.$order->order_number.' готов',
            ],
        ]);
    }

    public function markNotReady(Order $order)
    {
        $order->forceFill([
            'is_ready' => false,
            'ready_at' => null,
            'ready_sound_requested_at' => null,
            'ready_sound_played_at' => null,
        ])->save();

        return response()->json([
            'message' => 'Order marked as not ready.',
            'order' => $order->load('items'),
        ]);
    }

    public function markPaid(Order $order)
    {
        $order->forceFill([
            'is_paid' => true,
            'paid_at' => now(),
        ])->save();

        return response()->json([
            'message' => 'Order marked as paid.',
            'order' => $order->load('items'),
        ]);
    }

    public function markUnpaid(Order $order)
    {
        $order->forceFill([
            'is_paid' => false,
            'paid_at' => null,
        ])->save();

        return response()->json([
            'message' => 'Order marked as unpaid.',
            'order' => $order->load('items'),
        ]);
    }

    public function incomingAnnouncements()
    {
        $orders = Order::query()
            ->whereNotNull('order_sound_requested_at')
            ->whereNull('order_sound_played_at')
            ->orderBy('order_sound_requested_at')
            ->get()
            ->map(function (Order $order) {
                return [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'type' => 'new_order',
                    'sound' => 'loud',
                    'text' => 'New order #'.$order->order_number,
                    'ordered_at' => $order->ordered_at,
                ];
            })
            ->values();

        return response()->json([
            'announcements' => $orders,
        ]);
    }

    public function markIncomingAnnouncementPlayed(Order $order)
    {
        $order->forceFill([
            'order_sound_played_at' => now(),
        ])->save();

        return response()->json([
            'message' => 'Incoming order announcement marked as played.',
            'order' => $order,
        ]);
    }

    public function readyAnnouncements()
    {
        $orders = Order::query()
            ->where('is_ready', true)
            ->whereNotNull('ready_sound_requested_at')
            ->whereNull('ready_sound_played_at')
            ->orderBy('ready_sound_requested_at')
            ->get()
            ->map(function (Order $order) {
                return [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'text' => 'Заказ номер '.$order->order_number.' готов',
                    'ready_at' => $order->ready_at,
                ];
            })
            ->values();

        return response()->json([
            'announcements' => $orders,
        ]);
    }

    public function markAnnouncementPlayed(Order $order)
    {
        if ($order->is_ready) {
            $order->forceFill([
                'ready_sound_played_at' => now(),
            ])->save();
        }

        return response()->json([
            'message' => 'Announcement marked as played.',
            'order' => $order,
        ]);
    }

    public function destroy(Order $order)
    {
        $order->delete();

        return response()->json([
            'message' => 'Order deleted.',
        ]);
    }

    public function clear()
    {
        Schema::disableForeignKeyConstraints();

        try {
            DB::table('order_items')->truncate();
            DB::table('orders')->truncate();
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        return response()->json([
            'message' => 'All orders cleared.',
        ]);
    }
}
