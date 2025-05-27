<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LoanProduct extends Model
{
    use HasFactory;

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
        'journal_account_balance_debit_id',
        'journal_account_balance_credit_id',
        'journal_account_principal_debit_id',
        'journal_account_principal_credit_id',
        'journal_account_income_debit_id',
        'journal_account_income_credit_id',
    ];

    protected $casts = [
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'min_rate' => 'decimal:2',
        'max_rate' => 'decimal:2',
        'usage_purposes' => 'string',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->code) {
                // Cari kode terakhir dengan format LP
                $lastProduct = self::where('code', 'like', 'LP%')
                    ->orderBy('id', 'desc')
                    ->first();
                
                // Jika sudah ada produk, ambil nomor terakhir dan tambahkan 1
                if ($lastProduct) {
                    $lastNumber = (int) substr($lastProduct->code, 2);
                    $newNumber = $lastNumber + 1;
                } else {
                    // Jika belum ada produk, mulai dari 1
                    $newNumber = 1;
                }
                
                // Format nomor dengan padding 3 digit
                $model->code = 'LP' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    public function balanceDebitAccount()
    {
        return $this->belongsTo(JournalAccount::class, 'journal_account_balance_debit_id');
    }

    public function balanceCreditAccount()
    {
        return $this->belongsTo(JournalAccount::class, 'journal_account_balance_credit_id');
    }

    public function principalCreditAccount()
    {
        return $this->belongsTo(JournalAccount::class, 'journal_account_principal_debit_id');
    }

    public function principalDebitAccount()
    {
        return $this->belongsTo(JournalAccount::class, 'journal_account_principal_credit_id');
    }

    public function incomeDebitAccount()
    {
        return $this->belongsTo(JournalAccount::class, 'journal_account_income_debit_id');
    }

    public function incomeCreditAccount()
    {
        return $this->belongsTo(JournalAccount::class, 'journal_account_income_credit_id');
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public static function rules($id = null)
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('loan_products', 'name')->ignore($id),
            ],
            // Aturan validasi lainnya...
        ];
    }
}
