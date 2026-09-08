<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationApp extends Model
{
    use HasFactory;

    protected $table = 'notifications_app';

    protected $fillable = ['user_id', 'type', 'titre', 'message', 'donnees', 'lue_le'];

    protected function casts(): array
    {
        return [
            'donnees' => 'array',
            'lue_le' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
