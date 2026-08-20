<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class KioskDevice extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'campus_id',
        'name',
        'device_token',
        'is_active',
        'last_seen_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public static function generateToken(): string
    {
        do {
            $token = Str::random(48);
        } while (self::where('device_token', $token)->exists());

        return $token;
    }
}
