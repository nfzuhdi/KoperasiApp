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
        'journal_account_balance_id',
        'journal_account_income_id',
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

    // Journal account relationships
    public function balanceAccount(): BelongsTo
    {
        return $this->belongsTo(JournalAccount::class, 'journal_account_balance_id');
    }

    public function incomeAccount(): BelongsTo
    {
        return $this->belongsTo(JournalAccount::class, 'journal_account_income_id');
    }
}



