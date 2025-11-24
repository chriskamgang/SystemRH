<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceUsage extends Model
{
    protected $table = 'device_usage';

    protected $fillable = [
        'device_id',
        'user_id',
        'usage_date',
        'device_model',
        'device_os',
    ];

    protected $casts = [
        'usage_date' => 'date',
    ];

    // Relation avec User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
