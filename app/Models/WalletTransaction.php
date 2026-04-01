<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    protected $fillable = [
        'wallet_id',
        'user_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'description',
        'reference',
        'elgiopay_payout_id',
        'elgiopay_status',
        'transfer_phone',
        'transfer_method',
        'source_type',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:0',
            'balance_before' => 'decimal:0',
            'balance_after' => 'decimal:0',
        ];
    }

    // Relations
    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
