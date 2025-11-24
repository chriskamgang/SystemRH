<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key_name',
        'value',
        'type',
        'description',
    ];

    /**
     * Helper methods
     */
    public static function get($key, $default = null)
    {
        $setting = self::where('key_name', $key)->first();

        if (!$setting) {
            return $default;
        }

        // Cast selon le type
        return match ($setting->type) {
            'integer' => (int) $setting->value,
            'boolean' => $setting->value === 'true' || $setting->value === '1',
            'json' => json_decode($setting->value, true),
            default => $setting->value,
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

        return true;
    }
}