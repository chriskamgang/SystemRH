<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToCompany;

class JobPosition extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = ['company_id', 'name', 'description'];

    /**
     * Liste des employés occupant ce poste
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
