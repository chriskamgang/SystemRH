<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use App\Models\Traits\BelongsToCompany;

class Setting extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'key_name',
        'value',
        'type',
        'description',
    ];

    private static array $localCache = [];

    /**
     * Charger tous les settings en une seule requete et les garder en cache
     */
    private static function loadAll(): array
    {
        if (!empty(self::$localCache)) {
            return self::$localCache;
        }

        self::$localCache = Cache::remember('settings_all', 3600, function () {
            return self::all()->keyBy('key_name')->toArray();
        });

        return self::$localCache;
    }

    /**
     * Helper methods
     */
    public static function get($key, $default = null)
    {
        $settings = self::loadAll();

        if (!isset($settings[$key])) {
            return $default;
        }

        $setting = $settings[$key];

        return match ($setting['type'] ?? 'string') {
            'integer' => (int) $setting['value'],
            'boolean' => $setting['value'] === 'true' || $setting['value'] === '1',
            'json' => json_decode($setting['value'], true),
            default => $setting['value'],
        };
    }

    public static function set($key, $value)
    {
        $setting = self::where('key_name', $key)->first();

        if ($setting) {
            $setting->update(['value' => $value]);
        } else {
            self::create([
                'key_name' => $key,
                'value' => $value,
                'type' => 'string',
            ]);
        }

        // Invalider le cache
        Cache::forget('settings_all');
        self::$localCache = [];

        return true;
    }
}
