<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    protected $fillable = [
        'member_id',
        'loan_product_id',
        'created_by',
        'account_number',
        'loan_amount',
        'margin_amount',
        'status',
        'disbursement_status',
        'disbursed_at',
        'reviewed_by',
        'approved_at',
        'rejected_reason',
        'purchase_price',
        'selling_price',
        'payment_status',

        //Jaminan
        'collateral_type',
        'bpkb_collateral_value',
        'bpkb_owner_name',
        'bpkb_number',
        'bpkb_vehicle_number',
        'bpkb_vehicle_brand',
        'bpkb_vehicle_type',
        'bpkb_vehicle_year',
        'bpkb_frame_number',
        'bpkb_engine_number',

        // Jaminan SHM
        'shm_collateral_value',
        'shm_owner_name',
        'shm_certificate_number',
        'shm_land_area',
        'shm_land_location',
    ];

    protected $casts = [
        'loan_amount' => 'decimal:2',
        'margin_amount' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'disbursed_at' => 'date',
        'approved_at' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($loan) {
            // Auto generate account number
            if (empty($loan->account_number)) {
                $loan->account_number = self::generateAccountNumber();
            }

            // Cek apakah relasi loanProduct sudah terisi
            $loanProduct = $loan->loanProduct ?? LoanProduct::find($loan->loan_product_id);

            if ($loanProduct && $loanProduct->contract_type === 'Murabahah') {
                if (is_null($loan->selling_price)) {
                    $loan->selling_price = $loan->purchase_price + $loan->margin_amount;
                }
            }
        });
    }

    public static function generateAccountNumber(): string
    {
        $prefix = 'LN';
        $latestLoan = self::orderBy('id', 'desc')->first();

        $nextNumber = 1;
        if ($latestLoan && preg_match('/LN(\d+)/', $latestLoan->account_number, $matches)) {
            $nextNumber = (int)$matches[1] + 1;
        }

        return $prefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function loanProduct(): BelongsTo
    {
        return $this->belongsTo(LoanProduct::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(LoanPayment::class);
    }

    public function loanPayments(): HasMany
    {
        return $this->hasMany(LoanPayment::class);
    }
}