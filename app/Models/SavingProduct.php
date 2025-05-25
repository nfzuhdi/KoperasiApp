<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SavingProduct extends Model
{
    protected $table = 'saving_products';

    protected $fillable = [
        'code',
        'savings_product_name',
        'description',
        'savings_type',
        'min_deposit',
        'max_deposit',
        'admin_fee',
        'penalty_fee',
        'is_withdrawable',
        'is_mandatory_routine',
        'deposit_period',
        'contract_type',
        'tenor_months',
        'monthly_deposit',
        'closing_fee',
        'early_withdrawal_penalty',
        'profit_sharing_type',
        'profit_sharing_amount',
        'member_ratio',
        'koperasi_ratio',
        'journal_account_deposit_debit_id',
        'journal_account_deposit_credit_id',
        'journal_account_withdrawal_debit_id',
        'journal_account_withdrawal_credit_id',
        'journal_account_profitsharing_debit_id',
        'journal_account_profitsharing_credit_id',
        'journal_account_profit_debit_id',
        'journal_account_profit_credit_id'
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

    // Journal account relationships

    // Relasi untuk akun-akun jurnal
    public function depositDebitAccount(): BelongsTo
    {
        return $this->belongsTo(JournalAccount::class, 'journal_account_deposit_debit_id');
    }

    public function depositCreditAccount(): BelongsTo
    {
        return $this->belongsTo(JournalAccount::class, 'journal_account_deposit_credit_id');
    }

    public function withdrawalDebitAccount(): BelongsTo
    {
        return $this->belongsTo(JournalAccount::class, 'journal_account_withdrawal_debit_id');
    }

    public function withdrawalCreditAccount(): BelongsTo
    {
        return $this->belongsTo(JournalAccount::class, 'journal_account_withdrawal_credit_id');
    }

    public function profitSharingDebitAccount(): BelongsTo
    {
        return $this->belongsTo(JournalAccount::class, 'journal_account_profitsharing_debit_id');
    }

    public function profitSharingCreditAccount(): BelongsTo
    {
        return $this->belongsTo(JournalAccount::class, 'journal_account_profitsharing_credit_id');
    }

    public function profitDebitAccount(): BelongsTo
    {
        return $this->belongsTo(JournalAccount::class, 'journal_account_profit_debit_id');
    }

    public function profitCreditAccount(): BelongsTo
    {
        return $this->belongsTo(JournalAccount::class, 'journal_account_profit_credit_id');
    }
}


