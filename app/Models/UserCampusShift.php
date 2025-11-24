<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCampusShift extends Model
{
    protected $fillable = [
        'user_id',
        'campus_id',
        'works_morning',
        'works_evening',
    ];

    protected $casts = [
        'works_morning' => 'boolean',
        'works_evening' => 'boolean',
    ];

    /**
     * Relations
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }
}
