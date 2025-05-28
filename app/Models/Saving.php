<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Saving extends Model
{
    protected $fillable = [
        'member_id',
        'saving_product_id',
        'created_by',
        'account_number',
        'balance',
        'status',
        'reviewed_by',
        'rejected_reason',
        'payment_period',
        'maturity_date',
        'next_due_date',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'maturity_date' => 'date',
        'next_due_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($saving) {
            if (empty($saving->account_number)) {
                $saving->account_number = self::generateAccountNumber();
            }
            
            // // Set initial next_due_date for mandatory routine savings
            // if (empty($saving->next_due_date)) {
            //     $savingProduct = SavingProduct::find($saving->saving_product_id);
                
            //     if ($savingProduct && $savingProduct->is_mandatory_routine && $savingProduct->deposit_period) {
            //         $baseDate = Carbon::now();
                    
            //         // Calculate next due date based on deposit period
            //         switch ($savingProduct->deposit_period) {
            //             case 'weekly':
            //                 $saving->next_due_date = $baseDate->copy()->addWeek();
            //                 break;
            //             case 'monthly':
            //                 $saving->next_due_date = $baseDate->copy()->addMonth();
            //                 break;
            //             case 'yearly':
            //                 $saving->next_due_date = $baseDate->copy()->addYear();
            //                 break;
            //         }
            //     }
            // }
        });
    }

    public static function generateAccountNumber()
    {
        $prefix = 'SAV';

        // Get the latest account number
        $latestSaving = self::orderBy('id', 'desc')->first();

        if (!$latestSaving) {
            // If no savings yet, start with SAV00001
            $nextNumber = 1;
        } else {
            // Extract the number from the latest account number
            $lastNumber = $latestSaving->account_number;
            if (preg_match('/SAV(\d+)/', $lastNumber, $matches)) {
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

    public function savingProduct(): BelongsTo
    {
        return $this->belongsTo(SavingProduct::class);
    }

    /**
     * Get the user who created this saving.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who reviewed this saving.
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get the payments for this saving account.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(SavingPayment::class, 'saving_id');
    }
}





