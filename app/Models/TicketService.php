<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToCompany;

class TicketService extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'icon',
        'color',
        'is_active',
        'sort_order',
        'department_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * The department this service is linked to (optional).
     */
    public function department()
    {
        return $this->belongsTo(\App\Models\Department::class);
    }

    /**
     * Scope: only active services, ordered by sort_order.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
