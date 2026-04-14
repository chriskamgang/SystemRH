<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'file_path',
        'file_type',
        'file_size',
    ];

    /**
     * Relation avec l'utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Formatter la taille du fichier
     */
    public function getFormattedSizeAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Obtenir l'icône selon le type de fichier
     */
    public function getIconAttribute()
    {
        if (str_contains($this->file_type, 'pdf')) {
            return 'fa-file-pdf text-red-500';
        }
        if (str_contains($this->file_type, 'image')) {
            return 'fa-file-image text-blue-500';
        }
        if (str_contains($this->file_type, 'word') || str_contains($this->file_type, 'officedocument.wordprocessingml')) {
            return 'fa-file-word text-blue-700';
        }
        if (str_contains($this->file_type, 'excel') || str_contains($this->file_type, 'officedocument.spreadsheetml') || str_contains($this->file_type, 'sheet')) {
            return 'fa-file-excel text-green-600';
        }
        return 'fa-file text-gray-500';
    }
}
