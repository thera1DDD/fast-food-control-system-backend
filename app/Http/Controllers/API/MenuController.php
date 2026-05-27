<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Dishes;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    // Получение всех категорий с блюдами и статистикой
    public function getCategories()
    {
        return Category::withDishesStats()->get();
    }

    // Получение блюд для конкретной категории
    public function getDishesByCategory($categoryId)
    {
        $category = Category::withDishes()->where('id', $categoryId)->firstOrFail(); // Получаем категорию по ID
        return response()->json($category); // Возвращаем категорию с блюдами
    }
    public function searchDishes(Request $request)
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = trim($validated['q']);
        $limit = $validated['limit'] ?? 20;

        $dishes = Dishes::query()
            ->where(function ($builder) use ($query) {
                $builder
                    ->where('name', 'like', '%'.$query.'%')
                    ->orWhere('description', 'like', '%'.$query.'%')
                    ->orWhere('ingredients', 'like', '%'.$query.'%');
            })
            ->orderByRaw('CASE WHEN name LIKE ? THEN 0 ELSE 1 END', [$query.'%'])
            ->orderBy('name')
            ->limit($limit)
            ->get();

        return response()->json([
            'query' => $query,
            'dishes' => $dishes,
        ]);
    }
}
