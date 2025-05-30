<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalAccount extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'account_number',
        'account_name',
        'account_type',
        'account_position',
        'is_sub_account',
        'parent_account_id',
        'opening_balance',
        'opening_balance_date',
        'is_active',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'opening_balance_date' => 'date',
        'is_sub_account' => 'boolean',
        'is_active' => 'boolean',
    ];

    // // Accessor untuk balance yang mengikuti opening_balance
    // public function getBalanceAttribute()
    // {
    //     // Jika ada perhitungan balance berdasarkan transaksi, 
    //     // bisa ditambahkan di sini. Untuk sementara menggunakan opening_balance
    //     return $this->opening_balance ?? 0;
    // }

    public function parentAccount(): BelongsTo
    {
        return $this->belongsTo(JournalAccount::class, 'parent_account_id');
    }

    public function subAccounts(): HasMany
    {
        return $this->hasMany(JournalAccount::class, 'parent_account_id');
    }

    public function monthlyBalances()
    {
        return $this->hasMany(JournalAccountMonthlyBalance::class);
    }

    //Untuk mendapatkan saldo bulanan terkini dari akun jurnal ke JournalAccountMonthlyBalance
    public function getCurrentMonthBalance()
    {
        $year = now()->year;
        $month = now()->month;
        
        return $this->monthlyBalances()
            ->where('year', $year)
            ->where('month', $month)
            ->first();
    }

    public function savingPayments()
    {
        return $this->hasManyThrough(
            SavingPayment::class,
            SavingProduct::class,
            'journal_account_balance_id', // Foreign key on SavingProduct table
            'saving_id', // Foreign key on SavingPayment table
            'id', // Local key on JournalAccount table
            'id' // Local key on SavingProduct table
        )->whereHas('savingAccount', function ($query) {
            $query->whereHas('savingProduct', function ($q) {
                $q->where('journal_account_balance_id', $this->id);
            });
        });
    }

    // // Method untuk menghitung balance yang lebih kompleks (opsional)
    // public function calculateBalance()
    // {
    //     // Ini bisa dikembangkan untuk menghitung balance berdasarkan:
    //     // - Opening balance
    //     // - Debit transactions
    //     // - Credit transactions
    //     // - dll
        
    //     return $this->opening_balance ?? 0;
    // }
}