<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'paid_off_at',

        //Jaminan
        'collateral_type',
        'bpkb_collateral_value',
        'bpkb_owner_name',
        'bpkb_number', // Nomor BPKB
        'bpkb_vehicle_number', // Nomor polisi
        'bpkb_vehicle_brand', // Merk kendaraan
        'bpkb_vehicle_type', // Tipe kendaraan
        'bpkb_vehicle_year', // Tahun kendaraan
        'bpkb_frame_number', // Nomor rangka
        'bpkb_engine_number', // Nomor mesin
        
        // Jaminan SHM
        'shm_collateral_value',
        'shm_owner_name',
        'shm_certificate_number',
        'shm_land_area',
        'shm_land_location',

        'status',
        'disbursement_status',
        'disbursed_at',
    ];

    protected $casts = [
        'loan_amount' => 'decimal:2',
        'margin_amount' => 'decimal:2',
        'disbursed_at' => 'date',
        'approved_at' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($loan) {
            if (empty($loan->account_number)) {
                $loan->account_number = self::generateAccountNumber();
            }
        });
    }

    public static function generateAccountNumber()
    {
        $prefix = 'LN';

        // Get the latest account number
        $latestLoan = self::orderBy('id', 'desc')->first();

        if (!$latestLoan) {
            // If no loans yet, start with LN00001
            $nextNumber = 1;
        } else {
            // Extract the number from the latest account number
            $lastNumber = $latestLoan->account_number;
            if (preg_match('/LN(\d+)/', $lastNumber, $matches)) {
                $nextNumber = (int)$matches[1] + 1;
            } else {
                // Fallback if pattern doesn't match
                $nextNumber = 1;
            }
        }

        // Format with leading zeros (5 digits)
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

    /**
     * Get the user who reviewed this loan.
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}



