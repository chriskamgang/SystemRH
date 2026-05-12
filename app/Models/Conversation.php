<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToCompany;

class Conversation extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = ['company_id', 'subject', 'is_group'];

    protected $casts = ['is_group' => 'boolean'];

    public function participants()
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function unreadCountFor($userId)
    {
        $participant = $this->participants()->where('user_id', $userId)->first();
        $lastRead = $participant?->pivot?->last_read_at;

        $query = $this->messages()->where('sender_id', '!=', $userId);
        if ($lastRead) {
            $query->where('created_at', '>', $lastRead);
        }
        return $query->count();
    }
}
