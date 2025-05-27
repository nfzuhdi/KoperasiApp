<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'due_date',
        'month',
        'year',
        'amount',
        'payment_period',
        'is_principal_return',
        'fine',
        'is_late',
        'payment_method',
        'reference_number',
        'status',
        'reviewed_by',
        'notes',
    ];

    protected $casts = [
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'fine' => 'decimal:2',
        'is_late' => 'boolean',
        'is_principal_return' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            // Auto-generate reference number if not provided
            if (empty($payment->reference_number)) {
                $payment->reference_number = self::generateReferenceNumber();
            }
        });
    }

    /**
     * Generate a unique reference number for the payment.
     */
    public static function generateReferenceNumber(): string
    {
        $prefix = 'LP';
        $date = now()->format('ymd');

        // Get the latest payment for today
        $latestPayment = self::whereDate('created_at', now())
            ->orderBy('id', 'desc')
            ->first();

        if (!$latestPayment) {
            // If no payments today, start with 1
            $nextNumber = 1;
        } else {
            // Extract the number from the latest reference number
            $lastReference = $latestPayment->reference_number;
            if (preg_match('/LP\d{6}-(\d+)/', $lastReference, $matches)) {
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
     * Get the loan that owns the payment.
     */
    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
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

    /**
     * Check if payment is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->is_late || ($this->created_at < now()->toDateString() && $this->status !== 'approved');
    }

    /**
     * Calculate days overdue.
     */
    public function daysOverdue(): int
    {
        if (!$this->isOverdue()) {
            return 0;
        }

        return now()->diffInDays($this->created_at);
    }

    /**
     * Process journal entries for Mudharabah payment
     * 
     * @param Loan $loan The loan being paid
     * @return void
     */
    public function processJournalMudharabah(Loan $loan)
    {
        // Get loan product and journal accounts
        $loanProduct = $loan->loanProduct;
        
        if (!$loanProduct) {
            return;
        }
        
        // Calculate total margin amount
        $totalMarginAmount = $loan->loan_amount * ($loan->margin_amount / 100);
        
        // Calculate margin per period
        $marginPerPeriod = $totalMarginAmount / $loanProduct->tenor_months;
        
        // 1. Record profit payment (bagi hasil) - untuk semua periode reguler
        if (!$this->is_principal_return) {
            // Debit: Kas (journal_account_principal_debit_id)
            $debitAccount = JournalAccount::find($loanProduct->journal_account_principal_debit_id);
            if ($debitAccount) {
                if ($debitAccount->account_position === 'debit') {
                    $debitAccount->balance += $marginPerPeriod;
                } else {
                    $debitAccount->balance -= $marginPerPeriod;
                }
                $debitAccount->save();
            }
            
            // Credit: Pendapatan Bagi Hasil (journal_account_income_credit_id)
            $creditAccount = JournalAccount::find($loanProduct->journal_account_income_credit_id);
            if ($creditAccount) {
                if ($creditAccount->account_position === 'credit') {
                    $creditAccount->balance += $marginPerPeriod;
                } else {
                    $creditAccount->balance -= $marginPerPeriod;
                }
                $creditAccount->save();
            }
        }
        
        // 2. Record principal payment (jika pembayaran khusus pengembalian modal)
        if ($this->is_principal_return) {
            // Debit: Kas (journal_account_principal_debit_id)
            $principalDebitAccount = JournalAccount::find($loanProduct->journal_account_principal_debit_id);
            if ($principalDebitAccount) {
                if ($principalDebitAccount->account_position === 'debit') {
                    $principalDebitAccount->balance += $loan->loan_amount;
                } else {
                    $principalDebitAccount->balance -= $loan->loan_amount;
                }
                $principalDebitAccount->save();
            }
            
            // Credit: Piutang Pembiayaan Mudharabah (journal_account_balance_debit_id)
            $balanceDebitAccount = JournalAccount::find($loanProduct->journal_account_balance_debit_id);
            if ($balanceDebitAccount) {
                if ($balanceDebitAccount->account_position === 'debit') {
                    $balanceDebitAccount->balance -= $loan->loan_amount;
                } else {
                    $balanceDebitAccount->balance += $loan->loan_amount;
                }
                $balanceDebitAccount->save();
            }
        }
        
        // 3. Process fine if it exists
        if ($this->fine && $this->fine > 0) {
            // Check if journal accounts for profit are configured
            if ($loanProduct->journal_account_profit_debit_id && 
                $loanProduct->journal_account_profit_credit_id) {
                
                // Debit: Kas (journal_account_profit_debit_id)
                $fineDebitAccount = JournalAccount::find($loanProduct->journal_account_profit_debit_id);
                if ($fineDebitAccount) {
                    if ($fineDebitAccount->account_position === 'debit') {
                        $fineDebitAccount->balance += $this->fine;
                    } else {
                        $fineDebitAccount->balance -= $this->fine;
                    }
                    $fineDebitAccount->save();
                }
                
                // Credit: Pendapatan Denda (journal_account_profit_credit_id)
                $fineCreditAccount = JournalAccount::find($loanProduct->journal_account_profit_credit_id);
                if ($fineCreditAccount) {
                    if ($fineCreditAccount->account_position === 'credit') {
                        $fineCreditAccount->balance += $this->fine;
                    } else {
                        $fineCreditAccount->balance -= $this->fine;
                    }
                    $fineCreditAccount->save();
                }
            }
        }
    }
}
