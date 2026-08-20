<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'description',
    ];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        if (app()->bound('company_settings_runtime_cache')) {
            $cache = app('company_settings_runtime_cache');
            if (is_array($cache) && array_key_exists($key, $cache)) {
                return $cache[$key];
            }
        } else {
            $cache = [];
        }

        $setting = static::where('key', $key)->first();
        $value = $setting ? $setting->value : $default;
        $cache[$key] = $value;
        app()->instance('company_settings_runtime_cache', $cache);

        return $value;
    }

    public static function setValue(string $key, mixed $value, ?string $description = null): static
    {
        if (app()->bound('company_settings_runtime_cache')) {
            $cache = app('company_settings_runtime_cache');
            if (is_array($cache)) {
                $cache[$key] = (string) $value;
                app()->instance('company_settings_runtime_cache', $cache);
            }
        }

        return static::updateOrCreate(
            ['key' => $key],
            array_filter([
                'value' => (string) $value,
                'description' => $description,
            ], fn ($v) => $v !== null)
        );
    }

    public static function clearRuntimeCache(): void
    {
        if (app()->bound('company_settings_runtime_cache')) {
            app()->instance('company_settings_runtime_cache', []);
        }
    }
}

