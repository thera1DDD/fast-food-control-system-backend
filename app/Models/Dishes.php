<?php

namespace App\Models;

use App\Models\Concerns\HasStorageImage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dishes extends Model
{
    use HasFactory;
    use HasStorageImage;
    protected $fillable =

        [
            'name',
            'description',
            'image',
            'ingredients',
            'kcal',
            'gram',
            'price',
            'preparation_area',
            'category_id',
        ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Добавляем accessor для изображения
    public function getImageAttribute($value)
    {
        return $this->storageImageUrl($value);
    }
}
