<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dishes extends Model
{
    use HasFactory;
    protected $fillable =

        [
            'name',
            'description',
            'image',
            'ingredients',
            'kcal',
            'gram',
            'price',
            'category_id',
        ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Добавляем accessor для изображения
    public function getImageAttribute($value)
    {
        return 'storage/' . $value;
    }
}
