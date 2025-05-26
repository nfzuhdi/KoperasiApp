<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanProduct extends Model
{
    protected $fillable = [
        'name',
        'code',
        'contract_type',
        'min_amount',
        'max_amount',
        'min_rate',
        'max_rate',
        'usage_purposes',
        'tenor_months',
        'admin_fee',
        'journal_account_balance_debit_id',
        'journal_account_balance_credit_id',
        'journal_account_income_debit_id',
        'journal_account_income_credit_id',
        'journal_account_payment_debit_id',
        'journal_account_payment_credit_id',
        'journal_account_fine_debit_id',
        'journal_account_fine_credit_id',
    ];

    protected $casts = [
        'usage_purposes' => 'array',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'min_rate' => 'decimal:2',
        'max_rate' => 'decimal:2',
        'admin_fee'=> 'decimal:2',
    ];

    // Auto-generate code before creating
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->code)) {
                $last = self::orderBy('id', 'desc')->first();
                $nextNumber = $last ? intval($last->code) + 1 : 1;
                $model->code = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    // Journal account relationships for balance (modal)
    public function balanceDebitAccount(): BelongsTo
    {
        return $this->belongsTo(JournalAccount::class, 'journal_account_balance_debit_id');
    }

    public function balanceCreditAccount(): BelongsTo
    {
        return $this->belongsTo(JournalAccount::class, 'journal_account_balance_credit_id');
    }

    // Journal account relationships for income (pendapatan)
    public function incomeDebitAccount(): BelongsTo
    {
        return $this->belongsTo(JournalAccount::class, 'journal_account_income_debit_id');
    }

    public function incomeCreditAccount(): BelongsTo
    {
        return $this->belongsTo(JournalAccount::class, 'journal_account_income_credit_id');
    }
}

