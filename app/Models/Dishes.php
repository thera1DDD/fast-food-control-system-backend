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
        $value = trim((string) $value);

        if ($value === '') {
            return $value;
        }

        if (preg_match('/(dishes|categories)\\\\?\/[^"\]]+/', $value, $matches)) {
            $value = str_replace('\/', '/', $matches[0]);
        } else {
            $decoded = json_decode($value, true);

            if (is_array($decoded) && isset($decoded[0])) {
                $value = (string) $decoded[0];
            }
        }

        if (preg_match('/^https?:\/\//i', $value)) {
            return $value;
        }

        $value = ltrim(str_replace('\\', '/', $value), '/');

        while (str_starts_with($value, 'storage/')) {
            $value = substr($value, strlen('storage/'));
        }

        return 'storage/'.$value;
    }
}
