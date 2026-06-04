<?php

namespace App\Models\Concerns;

trait HasStorageImage
{
    protected function storageImageUrl($value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        if (is_array($decoded) && isset($decoded[0])) {
            $value = (string) $decoded[0];
        } elseif (preg_match('/(dishes|categories|products|users)\\\\?\/[^"\]]+/', $value, $matches)) {
            $value = str_replace('\/', '/', $matches[0]);
        }

        if (preg_match('/^https?:\/\//i', $value)) {
            return $value;
        }

        $value = ltrim(str_replace('\\', '/', $value), '/');

        while (str_starts_with($value, 'storage/')) {
            $value = substr($value, strlen('storage/'));
        }

        return url('storage/'.$value);
    }
}
