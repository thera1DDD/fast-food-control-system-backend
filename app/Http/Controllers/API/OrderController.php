<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Dishes;
use App\Models\Order;
use App\Support\PriceHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function current(Request $request)
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'ready' => ['nullable', 'boolean'],
        ]);

        $date = $validated['date'] ?? now()->toDateString();

        $query = Order::query()
            ->with('items')
            ->whereDate('ordered_at', $date)
            ->orderByDesc('ordered_at');

        if (array_key_exists('ready', $validated)) {
            $query->where('is_ready', (bool) $validated['ready']);
        }

        $orders = $query->get();

        return response()->json([
            'date' => $date,
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
            'ordered_at' => ['nullable', 'date'],
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
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ];

            $totalAmount += $lineTotal;
        }

        $order = DB::transaction(function () use ($validated, $preparedItems, $totalAmount) {
            $order = Order::query()->create([
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'source' => $validated['source'] ?? 'website',
                'ordered_at' => $validated['ordered_at'] ?? now(),
                'total_amount' => round($totalAmount, 2),
                'is_ready' => false,
            ]);

            $order->items()->createMany($preparedItems);

            return $order->load('items');
        });

        return response()->json([
            'message' => 'Order saved successfully.',
            'order' => $order,
        ], 201);
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
}
