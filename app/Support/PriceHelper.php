<?php

namespace App\Support;

class PriceHelper
{
    public static function normalize(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return round((float) $value, 2);
        }

        $value = trim((string) $value);

        if ($value === '') {
            return 0.0;
        }

        $normalized = preg_replace('/[^0-9,.\-]/', '', $value) ?? '0';

        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            $normalized = str_replace(',', '', $normalized);
        } else {
            $normalized = str_replace(',', '.', $normalized);
        }

        return round((float) $normalized, 2);
    }
}
