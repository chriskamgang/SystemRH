<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'email',
        'phone',
        'address',
        'city',
        'country',
        'sector',
        'subscription_plan',
        'max_employees',
        'is_active',
        'subscription_expires_at',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings' => 'array',
            'subscription_expires_at' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($company) {
            if (empty($company->slug)) {
                $company->slug = Str::slug($company->name);
            }
        });
    }

    // Relations
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    public function campuses()
    {
        return $this->hasMany(Campus::class);
    }

    public function roles()
    {
        return $this->hasMany(Role::class);
    }

    // Helpers
    public function employeeCount(): int
    {
        return $this->users()->where('is_active', true)->count();
    }

    public function hasReachedEmployeeLimit(): bool
    {
        return $this->employeeCount() >= $this->max_employees;
    }

    public function isSubscriptionActive(): bool
    {
        if (!$this->subscription_expires_at) {
            return true; // Pas de date = illimite
        }
        return $this->subscription_expires_at->isFuture();
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo) return null;
        return asset('storage/' . $this->logo);
    }
}
