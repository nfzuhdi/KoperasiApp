<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SavingPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'saving_id',
        'amount',
        'fine',
        'payment_method',
        'reference_number',
        'status',
        'reviewed_by',
        'notes',
        'month',
        'year',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fine' => 'decimal:2',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->reference_number)) {
                $payment->reference_number = self::generateReferenceNumber();
            }
            
            // Auto-populate month and year from created_at
            $date = now();
            $payment->month = $date->month;
            $payment->year = $date->year;
            
            // Update next_due_date for mandatory routine savings
            static::created(function ($payment) {
                // Get the saving account and its product
                $saving = $payment->savingAccount;
                if ($saving) {
                    $savingProduct = $saving->savingProduct;
                    
                    // Check if it's a mandatory routine saving with a deposit period
                    if ($savingProduct && $savingProduct->is_mandatory_routine && $savingProduct->deposit_period) {
                        $baseDate = now();
                        
                        // Calculate next due date based on deposit period
                        switch ($savingProduct->deposit_period) {
                            case 'weekly':
                                $nextDueDate = $baseDate->copy()->addWeek();
                                break;
                            case 'monthly':
                                $nextDueDate = $baseDate->copy()->addMonth();
                                break;
                            case 'yearly':
                                $nextDueDate = $baseDate->copy()->addYear();
                                break;
                            default:
                                $nextDueDate = null;
                        }
                        
                        // Update the saving record with the new next_due_date
                        if ($nextDueDate) {
                            $saving->next_due_date = $nextDueDate;
                            $saving->save();
                        }
                    }
                }
            });
        });
    }

    /**
     * Generate a unique reference number for the payment.
     */
    public static function generateReferenceNumber()
    {
        $prefix = 'PAY';
        $date = now()->format('Ymd');
        
        // Get the latest payment with a reference number from today
        $latestPayment = self::where('reference_number', 'like', "{$prefix}{$date}%")
            ->orderBy('id', 'desc')
            ->first();

        if (!$latestPayment) {
            // If no payments today, start with PAY-YYYYMMDD-0001
            $nextNumber = 1;
        } else {
            // Extract the number from the latest reference number
            $lastNumber = $latestPayment->reference_number;
            if (preg_match("/{$prefix}{$date}-(\d+)/", $lastNumber, $matches)) {
                $nextNumber = (int)$matches[1] + 1;
            } else {
                // Fallback if pattern doesn't match
                $nextNumber = 1;
            }
        }

        // Format with leading zeros (4 digits)
        return "{$prefix}{$date}-" . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get the saving that owns the payment.
     */
    public function savingAccount(): BelongsTo
    {
        return $this->belongsTo(Saving::class, 'saving_id');
    }

    /**
     * Get the user who reviewed the payment.
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scope a query to only include approved payments.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    
}
