<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group'];

    protected $casts = [
        'value' => 'string',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('kelvcmc.settings'));
        static::deleted(fn () => Cache::forget('kelvcmc.settings'));
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $settings = Cache::rememberForever('kelvcmc.settings', fn () => self::pluck('value', 'key')->all());

        $value = $settings[$key] ?? null;

        if ($value === null) {
            return $default;
        }

        return match (true) {
            in_array($value, ['true', 'false'], true) => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            is_numeric($value) => $value + 0,
            default => $value,
        };
    }

    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        self::updateOrCreate(
            ['key' => $key],
            ['value' => is_bool($value) ? ($value ? 'true' : 'false') : (string) $value, 'group' => $group]
        );

        Cache::forget('kelvcmc.settings');
    }
}
