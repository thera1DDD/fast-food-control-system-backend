<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    protected $fillable =

        [
            'name',
            'description',
            'image',
        ];


    public function dishes(){
        return $this->hasMany(Dishes::class,'category_id');
    }

    public static function withDishesStats()
    {
        return self::query()
            ->withCount('dishes')
            ->withMin('dishes', 'price');
    }

    // App\Models\Category.php

    public static function withDishes()
    {
        return self::query()
            ->with('dishes') // Эджер-загрузка блюд
            ->withCount('dishes')
            ->withMin('dishes', 'price')
            ->withMax('dishes', 'price');
    }

    // Добавляем accessor для изображения
    public function getImageAttribute($value)
    {
        return 'storage/' . $value;
    }
}
