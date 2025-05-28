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
            \Log::error('Loan product not found', ['loan_id' => $loan->id]);
            return;
        }
        
        $totalPayment = (float)($this->amount ?? 0);
        
        try {
            DB::beginTransaction();
            
            // Cek apakah ini pembayaran pertama
            $isFirstPayment = LoanPayment::where('loan_id', $loan->id)
                ->where('status', 'approved')
                ->where('id', '<', $this->id)
                ->count() == 0;
            
            // Hitung total margin
            $totalMargin = (float)($loan->selling_price - $loan->purchase_price);
            
            \Log::info('Starting Murabahah payment journal processing', [
                'payment_id' => $this->id,
                'loan_id' => $loan->id,
                'amount' => $totalPayment,
                'is_first_payment' => $isFirstPayment,
                'total_margin' => $totalMargin
            ]);
            
            // 1. Debit: Kas/Bank (journal_account_principal_debit_id) - TOTAL PEMBAYARAN
            $kasAccount = JournalAccount::find($loanProduct->journal_account_principal_debit_id);
            if (!$kasAccount) {
                throw new \Exception("Kas account not found");
            }
            
            if ($kasAccount->account_position === 'debit') {
                $kasAccount->balance += $totalPayment;
            } else {
                $kasAccount->balance -= $totalPayment;
            }
            $kasAccount->save();
            
            // 2. Credit: Piutang Murabahah (journal_account_balance_debit_id)
            $piutangAccount = JournalAccount::find($loanProduct->journal_account_balance_debit_id);
            if (!$piutangAccount) {
                throw new \Exception("Piutang account not found");
            }
            
            // Pembayaran reguler - kurangi piutang
            if ($piutangAccount->account_position === 'debit') {
                $piutangAccount->balance -= $totalPayment;
            } else {
                $piutangAccount->balance += $totalPayment;
            }
            
            // Hitung total pembayaran yang diharapkan
            $expectedPayments = $loanProduct->tenor_months;
            
            // Hitung pembayaran yang sudah dilakukan (termasuk yang ini)
            $completedPayments = LoanPayment::where('loan_id', $loan->id)
                ->where('status', 'approved')
                ->count();
            
            // Jika ini pembayaran terakhir, pastikan piutang menjadi 0
            if ($completedPayments >= $expectedPayments) {
                $piutangAccount->balance = 0;
            }
            
            $piutangAccount->save();
            
            // 3. Credit: Pendapatan Margin Murabahah (journal_account_income_credit_id)
            $pendapatanAccount = JournalAccount::find($loanProduct->journal_account_income_credit_id);
            if (!$pendapatanAccount) {
                throw new \Exception("Pendapatan account not found");
            }
            
            // PENTING: Untuk setiap pembayaran, tambahkan ke pendapatan
            if ($pendapatanAccount->account_position === 'credit') {
                $pendapatanAccount->balance += $totalPayment;
            } else {
                $pendapatanAccount->balance -= $totalPayment;
            }
            
            \Log::info('Updated pendapatan account for Murabahah payment', [
                'payment_id' => $this->id,
                'account' => $pendapatanAccount->account_name,
                'old_balance' => $pendapatanAccount->balance - $totalPayment,
                'new_balance' => $pendapatanAccount->balance,
                'payment_amount' => $totalPayment
            ]);
            
            $pendapatanAccount->save();
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error processing Murabahah journal: ' . $e->getMessage(), [
                'payment_id' => $this->id,
                'exception' => $e
            ]);
            throw $e;
        }
    }

    /**
     * Fix Murabahah accounting for a specific payment
     * This method should be called manually for existing payments
     * 
     * @param Loan $loan
     * @return void
     */
    public function fixMurabahahPaymentAccounting(Loan $loan)
    {
        if ($loan->loanProduct->contract_type !== 'Murabahah') {
            \Log::warning('Cannot fix accounting: not a Murabahah loan', ['loan_id' => $loan->id]);
            return;
        }
        
        $loanProduct = $loan->loanProduct;
        
        if (!$loanProduct) {
            \Log::error('Loan product not found', ['loan_id' => $loan->id]);
            return;
        }
        
        try {
            DB::beginTransaction();
            
            // Calculate total margin
            $totalMargin = (float)($loan->selling_price - $loan->purchase_price);
            
            // Check if this is the first payment
            $isFirstPayment = LoanPayment::where('loan_id', $loan->id)
                ->where('status', 'approved')
                ->where('id', '<', $this->id)
                ->count() == 0;
            
            \Log::info('Fixing Murabahah payment accounting', [
                'payment_id' => $this->id,
                'loan_id' => $loan->id,
                'is_first_payment' => $isFirstPayment,
                'total_margin' => $totalMargin
            ]);
            
            // If this is the first payment, update pendapatan account
            if ($isFirstPayment) {
                $pendapatanAccount = JournalAccount::find($loanProduct->journal_account_income_credit_id);
                if (!$pendapatanAccount) {
                    \Log::error('Pendapatan account not found', [
                        'account_id' => $loanProduct->journal_account_income_credit_id
                    ]);
                    throw new \Exception("Pendapatan account not found");
                }
                
                $oldPendapatanBalance = $pendapatanAccount->balance;
                
                // Reset pendapatan to full margin amount
                if ($pendapatanAccount->account_position === 'credit') {
                    // Calculate difference to add
                    $currentMarginInAccount = $oldPendapatanBalance;
                    $marginDifference = $totalMargin - $currentMarginInAccount;
                    
                    // Add difference to balance
                    $pendapatanAccount->balance += $marginDifference;
                } else {
                    $pendapatanAccount->balance = -$totalMargin;
                }
                
                \Log::info('Fixed pendapatan account for Murabahah payment', [
                    'account' => $pendapatanAccount->account_name,
                    'old_balance' => $oldPendapatanBalance,
                    'new_balance' => $pendapatanAccount->balance,
                    'margin_amount' => $totalMargin,
                    'margin_difference' => $marginDifference ?? 0
                ]);
                
                $pendapatanAccount->save();
            }
            
            DB::commit();
            
            \Log::info('Murabahah payment accounting fixed successfully', [
                'payment_id' => $this->id,
                'loan_id' => $loan->id,
                'is_first_payment' => $isFirstPayment,
                'pendapatan_balance' => isset($pendapatanAccount) ? $pendapatanAccount->balance : 'N/A'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error fixing Murabahah payment accounting: ' . $e->getMessage(), [
                'payment_id' => $this->id,
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
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

    /**
     * Fix pendapatan margin Murabahah directly
     * This method directly sets the pendapatan margin account to the specified value
     * 
     * @param float $targetAmount The exact amount to set (e.g., 4000000)
     * @return void
     */
    public function fixPendapatanMarginMurabahah(float $targetAmount)
    {
        try {
            DB::beginTransaction();
            
            // Get the loan
            $loan = $this->loan;
            if (!$loan || !$loan->loanProduct) {
                throw new \Exception("Loan or loan product not found");
            }
            
            if ($loan->loanProduct->contract_type !== 'Murabahah') {
                throw new \Exception("This is not a Murabahah loan");
            }
            
            \Log::info('Fixing pendapatan margin Murabahah directly', [
                'payment_id' => $this->id,
                'loan_id' => $loan->id,
                'target_amount' => $targetAmount
            ]);
            
            // Get pendapatan account
            $pendapatanAccount = JournalAccount::find($loan->loanProduct->journal_account_income_credit_id);
            if (!$pendapatanAccount) {
                throw new \Exception("Pendapatan account not found");
            }
            
            $oldBalance = $pendapatanAccount->balance;
            
            // Set the balance directly to the target amount
            if ($pendapatanAccount->account_position === 'credit') {
                $pendapatanAccount->balance = $targetAmount;
            } else {
                $pendapatanAccount->balance = -$targetAmount;
            }
            
            $pendapatanAccount->save();
            
            \Log::info('Fixed pendapatan margin Murabahah successfully', [
                'account' => $pendapatanAccount->account_name,
                'old_balance' => $oldBalance,
                'new_balance' => $pendapatanAccount->balance
            ]);
            
            DB::commit();
            
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error fixing pendapatan margin: ' . $e->getMessage(), [
                'payment_id' => $this->id,
                'exception' => $e
            ]);
            throw $e;
        }
    }
}