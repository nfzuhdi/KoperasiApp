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
    
    public static function generateReferenceNumber(): string
    {
        $prefix = 'LP';
        $date = now()->format('ymd');

        // Get the latest payment for today
        $latestPayment = self::whereDate('created_at', now())
            ->orderBy('id', 'desc')
            ->first();

        if (!$latestPayment) {
            $nextNumber = 1;
        } else {
            $lastReference = $latestPayment->reference_number;
            if (preg_match('/LP\d{6}-(\d+)/', $lastReference, $matches)) {
                $nextNumber = (int)$matches[1] + 1;
            } else {
                $nextNumber = 1;
            }
        }

        return "{$prefix}{$date}-" . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function isOverdue(): bool
    {
        return $this->is_late || ($this->created_at < now()->toDateString() && $this->status !== 'approved');
    }

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
     * @param Loan $loan
     * @return void
     */
    public function processJournalMudharabah(Loan $loan)
    {
        $loanProduct = $loan->loanProduct;
        
        if (!$loanProduct) {
            return;
        }
        
        $totalPayment = (float)($this->amount ?? 0);
        
        try {
            DB::beginTransaction();
            
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
                $principalDebitAccount = JournalAccount::find($loanProduct->journal_account_principal_debit_id);
                if ($principalDebitAccount) {
                    if ($principalDebitAccount->account_position === 'debit') {
                        $principalDebitAccount->balance += $totalPayment;
                    } else {
                        $principalDebitAccount->balance -= $totalPayment;
                    }
                    $principalDebitAccount->save();
                }
                $balanceDebitAccount = JournalAccount::find($loanProduct->journal_account_balance_debit_id);
                if ($balanceDebitAccount) {
                    if ($balanceDebitAccount->account_position === 'debit') {
                        $balanceDebitAccount->balance -= $totalPayment;
                    } else {
                        $balanceDebitAccount->balance += $totalPayment;
                    }
                    $balanceDebitAccount->save();
                }
                
                \Log::info('Jurnal pengembalian modal Mudharabah', [
                    'payment_id' => $this->id,
                    'payment_period' => $this->payment_period,
                    'total_payment' => $totalPayment,
                    'debit_account' => $principalDebitAccount ? $principalDebitAccount->account_name : 'Not found',
                    'credit_account' => $balanceDebitAccount ? $balanceDebitAccount->account_name : 'Not found'
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

    /**
     * Process journal entries for Murabahah payment
     * 
     * @param Loan $loan
     * @return void
     */
    public function processJournalMurabahah(Loan $loan)
    {
        $loanProduct = $loan->loanProduct;
        
        if (!$loanProduct) {
            return;
        }
        
        $totalPayment = (float)($this->amount ?? 0);
        
        try {
            DB::beginTransaction();
            
            // Untuk Murabahah pembayaran angsuran:
            // 1. Debit: Kas/Bank (journal_account_principal_debit_id) - TOTAL PEMBAYARAN
            $debitAccount = JournalAccount::find($loanProduct->journal_account_principal_debit_id);
            if ($debitAccount) {
                if ($debitAccount->account_position === 'debit') {
                    $debitAccount->balance += $totalPayment;
                } else {
                    $debitAccount->balance -= $totalPayment;
                }
                $debitAccount->save();
            }
            
            // 2. Credit: Piutang Murabahah (journal_account_balance_credit_id) - TOTAL PEMBAYARAN
            $creditAccount = JournalAccount::find($loanProduct->journal_account_balance_credit_id);
            if ($creditAccount) {
                if ($creditAccount->account_position === 'credit') {
                    $creditAccount->balance += $totalPayment;
                } else {
                    $creditAccount->balance -= $totalPayment;
                }
                $creditAccount->save();
            }
            
            \Log::info('Jurnal pembayaran angsuran Murabahah', [
                'payment_id' => $this->id,
                'payment_period' => $this->payment_period,
                'total_payment' => $totalPayment,
                'debit_account' => $debitAccount ? $debitAccount->account_name : 'Not found',
                'credit_account' => $creditAccount ? $creditAccount->account_name : 'Not found'
            ]);
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error processing Murabahah journal: ' . $e->getMessage(), [
                'payment_id' => $this->id,
                'exception' => $e
            ]);
        }
    }

    /**
     * Process journal entries for Musyarakah payment
     * 
     * @param Loan $loan
     * @return void
     */
    public function processJournalMusyarakah(Loan $loan)
    {
        $loanProduct = $loan->loanProduct;
        
        if (!$loanProduct) {
            return;
        }
        
        $totalPayment = (float)($this->amount ?? 0);
        
        try {
            DB::beginTransaction();
            
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
                
                \Log::info('Jurnal bagi hasil Musyarakah', [
                    'payment_id' => $this->id,
                    'payment_period' => $this->payment_period,
                    'total_payment' => $totalPayment,
                    'debit_account' => $debitAccount ? $debitAccount->account_name : 'Not found',
                    'credit_account' => $creditAccount ? $creditAccount->account_name : 'Not found'
                ]);
            }
            
            // 2. Record principal payment (jika pembayaran terakhir - pengembalian modal)
            if ($isLastPayment) {
                $principalDebitAccount = JournalAccount::find($loanProduct->journal_account_principal_debit_id);
                if ($principalDebitAccount) {
                    if ($principalDebitAccount->account_position === 'debit') {
                        $principalDebitAccount->balance += $totalPayment;
                    } else {
                        $principalDebitAccount->balance -= $totalPayment;
                    }
                    $principalDebitAccount->save();
                }
                $balanceDebitAccount = JournalAccount::find($loanProduct->journal_account_balance_debit_id);
                if ($balanceDebitAccount) {
                    if ($balanceDebitAccount->account_position === 'debit') {
                        $balanceDebitAccount->balance -= $totalPayment;
                    } else {
                        $balanceDebitAccount->balance += $totalPayment;
                    }
                    $balanceDebitAccount->save();
                }
                
                \Log::info('Jurnal pengembalian modal Musyarakah', [
                    'payment_id' => $this->id,
                    'payment_period' => $this->payment_period,
                    'total_payment' => $totalPayment,
                    'debit_account' => $principalDebitAccount ? $principalDebitAccount->account_name : 'Not found',
                    'credit_account' => $balanceDebitAccount ? $balanceDebitAccount->account_name : 'Not found'
                ]);
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error processing Musyarakah journal: ' . $e->getMessage(), [
                'payment_id' => $this->id,
                'exception' => $e
            ]);
        }
    }

    /**
     * Process fine/penalty journal entries
     * 
     * @param Loan $loan
     * @return void
     */
    public function processFineJournalFixed(Loan $loan)
    {
        if ($this->fine <= 0) {
            \Log::info('No fine to process, skipping');
            return;
        }
        
        $loanProduct = $loan->loanProduct;
        
        if (!$loanProduct) {
            \Log::warning('Cannot process fine journal: loan product not found', [
                'payment_id' => $this->id,
                'loan_id' => $loan->id
            ]);
            return;
        }
        
        // Gunakan akun jurnal denda jika sudah dikonfigurasi
        $fineDebitAccountId = $loanProduct->journal_account_fine_debit_id ?? $loanProduct->journal_account_principal_debit_id;
        $fineCreditAccountId = $loanProduct->journal_account_fine_credit_id ?? null;
        
        if (!$fineDebitAccountId || !$fineCreditAccountId) {
            \Log::warning('Cannot process fine journal: missing configuration', [
                'payment_id' => $this->id,
                'fine_amount' => $this->fine,
                'fine_debit_account_id' => $fineDebitAccountId,
                'fine_credit_account_id' => $fineCreditAccountId
            ]);
            return;
        }
        
        // Pastikan nilai denda yang digunakan adalah nilai asli
        $fineAmount = (float)$this->fine;
        
        try {
            DB::beginTransaction();
            
            // Debit: Kas (journal_account_fine_debit_id) - JUMLAH DENDA
            $fineDebitAccount = JournalAccount::find($fineDebitAccountId);
            if ($fineDebitAccount) {
                $oldBalance = $fineDebitAccount->balance;
                
                if ($fineDebitAccount->account_position === 'debit') {
                    $fineDebitAccount->balance += $fineAmount;
                } else {
                    $fineDebitAccount->balance -= $fineAmount;
                }
                
                $fineDebitAccount->save();
                
                \Log::info('Updated debit account balance for fine', [
                    'account' => $fineDebitAccount->account_name,
                    'old_balance' => $oldBalance,
                    'new_balance' => $fineDebitAccount->balance,
                    'difference' => $fineDebitAccount->balance - $oldBalance,
                    'fine_amount_used' => $fineAmount
                ]);
            }
            
            // Credit: Pendapatan Denda (journal_account_fine_credit_id) - JUMLAH DENDA
            $fineCreditAccount = JournalAccount::find($fineCreditAccountId);
            if ($fineCreditAccount) {
                $oldBalance = $fineCreditAccount->balance;
                
                if ($fineCreditAccount->account_position === 'credit') {
                    $fineCreditAccount->balance += $fineAmount;
                } else {
                    $fineCreditAccount->balance -= $fineAmount;
                }
                
                $fineCreditAccount->save();
                
                \Log::info('Updated credit account balance for fine', [
                    'account' => $fineCreditAccount->account_name,
                    'old_balance' => $oldBalance,
                    'new_balance' => $fineCreditAccount->balance,
                    'difference' => $fineCreditAccount->balance - $oldBalance,
                    'fine_amount_used' => $fineAmount
                ]);
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error processing fine journal: ' . $e->getMessage(), [
                'payment_id' => $this->id,
                'exception' => $e
            ]);
        }
    }
}