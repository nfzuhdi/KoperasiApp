<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        'is_withdrawable',
        'is_mandatory_routine',
        'deposit_period',
        'contract_type',
        'minimal_balance',
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
        'journal_account_penalty_debit_id',
        'journal_account_penalty_credit_id'
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

    /**
     * Get the deposit credit account associated with the saving product.
     */
    public function depositCreditAccount()
    {
        return $this->belongsTo(JournalAccount::class, 'journal_account_deposit_credit_id');
    }

    /**
     * Get the withdrawal debit account associated with the saving product.
     */
    public function withdrawalDebitAccount()
    {
        return $this->belongsTo(JournalAccount::class, 'journal_account_withdrawal_debit_id');
    }

    /**
     * Get the withdrawal credit account associated with the saving product.
     */
    public function withdrawalCreditAccount()
    {
        return $this->belongsTo(JournalAccount::class, 'journal_account_withdrawal_credit_id');
    }

    /**
     * Get the profit sharing debit account associated with the saving product.
     */
    public function profitSharingDebitAccount()
    {
        return $this->belongsTo(JournalAccount::class, 'journal_account_profitsharing_debit_id');
    }

    /**
     * Get the profit sharing credit account associated with the saving product.
     */
    public function profitSharingCreditAccount()
    {
        return $this->belongsTo(JournalAccount::class, 'journal_account_profitsharing_credit_id');
    }

    /**
     * Get the penalty debit account associated with the saving product.
     */
    public function penaltyDebitAccount()
    {
        return $this->belongsTo(JournalAccount::class, 'journal_account_penalty_debit_id');
    }

    /**
     * Get the penalty credit account associated with the saving product.
     */
    public function penaltyCreditAccount()
    {
        return $this->belongsTo(JournalAccount::class, 'journal_account_penalty_credit_id');
    }

    /**
     * Get the savings associated with the saving product.
     */
    public function savings()
    {
        return $this->hasMany(Saving::class);
    }
}

