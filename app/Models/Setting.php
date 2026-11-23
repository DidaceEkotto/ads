<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Fillable(['key', 'value', 'type', 'description'])]
class Setting extends Model
{
    private const CACHE_KEY = 'platform_settings_all';

    /**
     * Récupère la valeur typée d'un paramètre avec mise en cache Redis/Cache.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $settings = Cache::get(self::CACHE_KEY);

        if (! is_array($settings)) {
            $settings = self::query()
                ->get(['key', 'value', 'type'])
                ->mapWithKeys(fn (self $item) => [
                    $item->key => [
                        'value' => $item->value,
                        'type' => $item->type,
                    ],
                ])
                ->all();

            Cache::put(self::CACHE_KEY, $settings, now()->addHours(6));
        }

        $setting = $settings[$key] ?? null;
        if (! is_array($setting)) {
            return $default;
        }

        return match ($setting['type']) {
            'integer' => (int) $setting['value'],
            'boolean' => filter_var($setting['value'], FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode((string) $setting['value'], true) ?? $default,
            default => $setting['value'],
        };
    }

    /**
     * Met à jour ou crée un paramètre et invalide le cache.
     */
    public static function setValue(string $key, mixed $value, string $type = 'string', ?string $description = null): self
    {
        $storedValue = match ($type) {
            'json' => json_encode($value, JSON_THROW_ON_ERROR),
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };

        $setting = self::query()->updateOrCreate(
            ['key' => $key],
            array_filter([
                'value' => $storedValue,
                'type' => $type,
                'description' => $description,
            ], static fn ($v) => $v !== null)
        );

        Cache::forget(self::CACHE_KEY);

        return $setting;
    }
}
