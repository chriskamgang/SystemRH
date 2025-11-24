<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'head_user_id',
        'campus_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relations
     */
    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function head()
    {
        return $this->belongsTo(User::class, 'head_user_id');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}