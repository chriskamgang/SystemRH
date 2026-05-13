<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CnpsRecord extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'cnps_number', 'registration_date', 'status'];

    protected $casts = ['registration_date' => 'date'];

    public function user()
    {
        return $this->belongsTo(User::class)->withoutGlobalScopes();
    }
}
