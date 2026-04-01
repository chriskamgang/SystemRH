<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Wallet extends Model
{
    protected $fillable = [
        'user_id',
        'balance',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:0',
        ];
    }

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /**
     * Créditer le portefeuille
     */
    public function credit($amount, $description, $sourceType, $reference = null)
    {
        return DB::transaction(function () use ($amount, $description, $sourceType, $reference) {
            $this->lockForUpdate();
            $this->refresh();

            $balanceBefore = $this->balance;
            $balanceAfter = $balanceBefore + $amount;

            $this->update(['balance' => $balanceAfter]);

            return $this->transactions()->create([
                'user_id' => $this->user_id,
                'type' => 'credit',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => $description,
                'source_type' => $sourceType,
                'reference' => $reference ?? 'WLT-' . strtoupper(Str::random(10)),
            ]);
        });
    }

    /**
     * Débiter le portefeuille
     */
    public function debit($amount, $description, $sourceType, $reference = null)
    {
        return DB::transaction(function () use ($amount, $description, $sourceType, $reference) {
            $this->lockForUpdate();
            $this->refresh();

            if ($this->balance < $amount) {
                throw new \Exception('Solde insuffisant.');
            }

            $balanceBefore = $this->balance;
            $balanceAfter = $balanceBefore - $amount;

            $this->update(['balance' => $balanceAfter]);

            return $this->transactions()->create([
                'user_id' => $this->user_id,
                'type' => 'debit',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => $description,
                'source_type' => $sourceType,
                'reference' => $reference ?? 'WLT-' . strtoupper(Str::random(10)),
            ]);
        });
    }
}
