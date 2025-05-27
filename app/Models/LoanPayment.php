<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        'member_profit',
        'koperasi_profit',
    ];

    protected $casts = [
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'fine' => 'decimal:2',
        'member_profit' => 'decimal:2',
        'koperasi_profit' => 'decimal:2',
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
        
        // Ambil nilai dari payment
        $totalPayment = (float)($this->amount ?? 0);
        
        try {
            DB::beginTransaction();
            
            // Cek apakah ini pembayaran terakhir (pengembalian modal)
            $isLastPayment = false;
            $tenor = (int) $loanProduct->tenor_months;
            if ($this->payment_period == $tenor + 1 || $this->is_principal_return) {
                $isLastPayment = true;
            }
            
            // 1. Record profit payment (bagi hasil) - untuk semua periode reguler
            if (!$isLastPayment) {
                // Debit: Kas (journal_account_principal_debit_id) - TOTAL PEMBAYARAN
                $debitAccount = JournalAccount::find($loanProduct->journal_account_principal_debit_id);
                if ($debitAccount) {
                    if ($debitAccount->account_position === 'debit') {
                        $debitAccount->balance += $totalPayment;
                    } else {
                        $debitAccount->balance -= $totalPayment;
                    }
                    $debitAccount->save();
                }
                
                // Credit: Pendapatan Bagi Hasil (journal_account_income_credit_id) - SELURUH PEMBAYARAN
                $creditAccount = JournalAccount::find($loanProduct->journal_account_income_credit_id);
                if ($creditAccount) {
                    if ($creditAccount->account_position === 'credit') {
                        $creditAccount->balance += $totalPayment;
                    } else {
                        $creditAccount->balance -= $totalPayment;
                    }
                    $creditAccount->save();
                }
                
                // Log transaksi
                \Log::info('Jurnal bagi hasil Mudharabah', [
                    'payment_id' => $this->id,
                    'payment_period' => $this->payment_period,
                    'total_payment' => $totalPayment,
                    'debit_account' => $debitAccount ? $debitAccount->account_name : 'Not found',
                    'credit_account' => $creditAccount ? $creditAccount->account_name : 'Not found'
                ]);
            }
            
            // 2. Record principal payment (jika pembayaran terakhir - pengembalian modal)
            if ($isLastPayment) {
                // Debit: Kas (journal_account_principal_debit_id)
                $principalDebitAccount = JournalAccount::find($loanProduct->journal_account_principal_debit_id);
                if ($principalDebitAccount) {
                    if ($principalDebitAccount->account_position === 'debit') {
                        $principalDebitAccount->balance += $totalPayment;
                    } else {
                        $principalDebitAccount->balance -= $totalPayment;
                    }
                    $principalDebitAccount->save();
                }
                
                // Credit: Piutang Pembiayaan Mudharabah (journal_account_balance_debit_id)
                $balanceDebitAccount = JournalAccount::find($loanProduct->journal_account_balance_debit_id);
                if ($balanceDebitAccount) {
                    if ($balanceDebitAccount->account_position === 'debit') {
                        $balanceDebitAccount->balance -= $totalPayment;
                    } else {
                        $balanceDebitAccount->balance += $totalPayment;
                    }
                    $balanceDebitAccount->save();
                }
                
                // Log transaksi
                \Log::info('Jurnal pengembalian modal Mudharabah', [
                    'payment_id' => $this->id,
                    'payment_period' => $this->payment_period,
                    'total_payment' => $totalPayment,
                    'debit_account' => $principalDebitAccount ? $principalDebitAccount->account_name : 'Not found',
                    'credit_account' => $balanceDebitAccount ? $balanceDebitAccount->account_name : 'Not found'
                ]);
            }
            
            // 3. Process fine if it exists
            if ($this->fine && $this->fine > 0) {
                // Debit: Kas (journal_account_principal_debit_id)
                $fineDebitAccount = JournalAccount::find($loanProduct->journal_account_principal_debit_id);
                if ($fineDebitAccount) {
                    if ($fineDebitAccount->account_position === 'debit') {
                        $fineDebitAccount->balance += $this->fine;
                    } else {
                        $fineDebitAccount->balance -= $this->fine;
                    }
                    $fineDebitAccount->save();
                }
                
                // Credit: Pendapatan Denda (journal_account_income_credit_id)
                $fineCreditAccount = JournalAccount::find($loanProduct->journal_account_income_credit_id);
                if ($fineCreditAccount) {
                    if ($fineCreditAccount->account_position === 'credit') {
                        $fineCreditAccount->balance += $this->fine;
                    } else {
                        $fineCreditAccount->balance -= $this->fine;
                    }
                    $fineCreditAccount->save();
                }
                
                // Log transaksi denda
                \Log::info('Jurnal denda Mudharabah', [
                    'payment_id' => $this->id,
                    'fine_amount' => $this->fine,
                    'debit_account' => $fineDebitAccount ? $fineDebitAccount->account_name : 'Not found',
                    'credit_account' => $fineCreditAccount ? $fineCreditAccount->account_name : 'Not found'
                ]);
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error processing Mudharabah journal: ' . $e->getMessage(), [
                'payment_id' => $this->id,
                'exception' => $e
            ]);
        }
    }
}