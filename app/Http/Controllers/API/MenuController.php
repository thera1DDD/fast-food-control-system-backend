<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
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
}
