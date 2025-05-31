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

    /**
     * Get the loan that owns the payment.
     */
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
            
            // Generate transaction number
            $transactionNumber = 'LOAN-PAY-' . $loan->id . '-' . now()->format('Ymd-His');
            
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
                    
                    // Buat entri jurnal umum untuk Debit
                    JurnalUmum::create([
                        'tanggal_bayar' => now(),
                        'no_ref' => $this->reference_number,
                        'no_transaksi' => $transactionNumber,
                        'akun_id' => $debitAccount->id,
                        'keterangan' => "Pembayaran bagi hasil {$loan->account_number} periode {$this->payment_period}",
                        'debet' => $totalPayment,
                        'kredit' => 0,
                        'loan_payment_id' => $this->id
                    ]);
                }
                
                // Credit: Pendapatan (journal_account_income_credit_id) - TOTAL PEMBAYARAN
                $creditAccount = JournalAccount::find($loanProduct->journal_account_income_credit_id);
                if ($creditAccount) {
                    if ($creditAccount->account_position === 'credit') {
                        $creditAccount->balance += $totalPayment;
                    } else {
                        $creditAccount->balance -= $totalPayment;
                    }
                    $creditAccount->save();
                    
                    // Buat entri jurnal umum untuk Kredit
                    JurnalUmum::create([
                        'tanggal_bayar' => now(),
                        'no_ref' => $this->reference_number,
                        'no_transaksi' => $transactionNumber,
                        'akun_id' => $creditAccount->id,
                        'keterangan' => "Pembayaran bagi hasil {$loan->account_number} periode {$this->payment_period}",
                        'debet' => 0,
                        'kredit' => $totalPayment,
                        'loan_payment_id' => $this->id
                    ]);
                }
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
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e; // Tambahkan throw exception agar error terlihat
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
            
            // Cek apakah ini pembayaran pertama
            $isFirstPayment = LoanPayment::where('loan_id', $loan->id)
                ->where('status', 'approved')
                ->where('id', '<', $this->id)
                ->count() == 0;
            
            // Hitung total margin
            $totalMargin = (float)($loan->selling_price - $loan->purchase_price);
            
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
            
            $pendapatanAccount->save();
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
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
            return;
        }
        
        $loanProduct = $loan->loanProduct;
        
        if (!$loanProduct) {
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
            
            // If this is the first payment, update pendapatan account
            if ($isFirstPayment) {
                $pendapatanAccount = JournalAccount::find($loanProduct->journal_account_income_credit_id);
                if (!$pendapatanAccount) {
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
                
                $pendapatanAccount->save();
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
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
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
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
            return;
        }
        
        $loanProduct = $loan->loanProduct;
        
        if (!$loanProduct) {
            return;
        }
        
        // Gunakan akun jurnal denda jika sudah dikonfigurasi
        $fineDebitAccountId = $loanProduct->journal_account_fine_debit_id ?? $loanProduct->journal_account_principal_debit_id;
        $fineCreditAccountId = $loanProduct->journal_account_fine_credit_id ?? null;
        
        if (!$fineDebitAccountId || !$fineCreditAccountId) {
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
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
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
            
            DB::commit();
            
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}


