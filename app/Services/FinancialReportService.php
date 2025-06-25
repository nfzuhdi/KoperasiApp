<?php

namespace App\Services;

use App\Models\JurnalUmum;
use App\Models\JournalAccount;
use App\Models\NeracaSaldo;
use App\Models\LaporanPosisiKeuangan;
use App\Models\LaporanArusKas;
use App\Models\LaporanLabaRugi;
use App\Models\LaporanPerubahanEkuitas;
use Carbon\Carbon;

class FinancialReportService
{
    /**
     * Update all financial reports for a specific month and year
     */
    public function updateFinancialReports($bulan, $tahun)
    {
        $this->updateNeracaSaldo($bulan, $tahun);
        $this->updateLaporanPosisiKeuangan($bulan, $tahun);
        $this->updateLaporanLabaRugi($bulan, $tahun);
        $this->updateLaporanArusKas($bulan, $tahun);
        $this->updateLaporanPerubahanEkuitas($bulan, $tahun);
    }

    /**
     * Update Neraca Saldo
     */
    public function updateNeracaSaldo($bulan, $tahun)
    {
        // Get all active accounts
        $accounts = JournalAccount::where('is_active', true)
            ->orderBy('account_number')
            ->orderBy('account_name')
            ->get();
        
        // Clear existing data for this period
        NeracaSaldo::where('bulan', $bulan)
                   ->where('tahun', $tahun)
                   ->delete();
        
        foreach ($accounts as $account) {
            // Calculate closing balance for the selected month
            $closingBalance = $this->getClosingBalance($account, $bulan, $tahun);
            
            // Skip accounts with zero balance
            if ($closingBalance == 0) {
                continue;
            }
            
            // Determine debet/kredit based on account position and balance
            $saldoDebet = 0;
            $saldoKredit = 0;
            
            if ($closingBalance != 0) {
                if (strtolower($account->account_position) === 'debit') {
                    if ($closingBalance >= 0) {
                        $saldoDebet = abs($closingBalance);
                    } else {
                        $saldoKredit = abs($closingBalance);
                    }
                } else { // kredit
                    if ($closingBalance >= 0) {
                        $saldoKredit = abs($closingBalance);
                    } else {
                        $saldoDebet = abs($closingBalance);
                    }
                }
            }
            
            // Save to database
            NeracaSaldo::create([
                'bulan' => $bulan,
                'tahun' => $tahun,
                'akun_id' => $account->id,
                'kode_akun' => $account->account_number,
                'nama_akun' => $account->account_name,
                'jenis_akun' => $account->account_type,
                'saldo_debet' => $saldoDebet,
                'saldo_kredit' => $saldoKredit,
            ]);
        }
    }

    /**
     * Update Laporan Posisi Keuangan
     */
    public function updateLaporanPosisiKeuangan($bulan, $tahun)
    {
        // Get all active accounts
        $accounts = JournalAccount::where('is_active', true)
            ->whereIn('account_type', ['asset', 'liability', 'equity'])
            ->orderBy('account_number')
            ->orderBy('account_name')
            ->get();
        
        // Clear existing data for this period
        LaporanPosisiKeuangan::where('bulan', $bulan)
                             ->where('tahun', $tahun)
                             ->delete();
        
        foreach ($accounts as $account) {
            // Calculate closing balance for the selected month
            $closingBalance = $this->getClosingBalance($account, $bulan, $tahun);
            
            // Skip accounts with zero balance
            if ($closingBalance == 0) {
                continue;
            }
            
            // Determine classification
            $klasifikasi = $this->getKlasifikasi($account);
            
            // Save to database
            LaporanPosisiKeuangan::create([
                'bulan' => $bulan,
                'tahun' => $tahun,
                'akun_id' => $account->id,
                'kode_akun' => $account->account_number,
                'nama_akun' => $account->account_name,
                'jenis_akun' => $account->account_type,
                'klasifikasi' => $klasifikasi,
                'saldo' => abs($closingBalance),
            ]);
        }
    }

    /**
     * Update Laporan Laba Rugi
     */
    public function updateLaporanLabaRugi($bulan, $tahun)
    {
        // Get all active income and expense accounts
        $accounts = JournalAccount::where('is_active', true)
            ->whereIn('account_type', ['income', 'expense'])
            ->orderBy('account_number')
            ->orderBy('account_name')
            ->get();
        
        // Clear existing data for this period
        LaporanLabaRugi::where('bulan', $bulan)
                       ->where('tahun', $tahun)
                       ->delete();
        
        foreach ($accounts as $account) {
            // Calculate closing balance for the selected month
            $closingBalance = $this->getClosingBalance($account, $bulan, $tahun);
            
            // Skip accounts with zero balance
            if ($closingBalance == 0) {
                continue;
            }
            
            // Determine classification
            $klasifikasi = $account->account_type === 'income' ? 'pendapatan' : 'beban';
            
            // Save to database
            LaporanLabaRugi::create([
                'bulan' => $bulan,
                'tahun' => $tahun,
                'akun_id' => $account->id,
                'kode_akun' => $account->account_number,
                'nama_akun' => $account->account_name,
                'jenis_akun' => $account->account_type,
                'klasifikasi' => $klasifikasi,
                'jumlah' => abs($closingBalance),
            ]);
        }
    }

    /**
     * Update Laporan Arus Kas
     */
    public function updateLaporanArusKas($bulan, $tahun)
    {
        // Clear existing data for this period
        LaporanArusKas::where('bulan', $bulan)
                      ->where('tahun', $tahun)
                      ->delete();

        // Get cash and cash equivalent accounts
        $kasAccounts = JournalAccount::where('is_active', true)
            ->where('account_type', 'asset')
            ->where(function($query) {
                $query->where('account_name', 'like', '%kas%')
                      ->orWhere('account_name', 'like', '%bank%')
                      ->orWhere('account_name', 'like', '%setara kas%');
            })
            ->orderBy('account_number')
            ->get();

        $kasAccountIds = $kasAccounts->pluck('id')->toArray();

        // Get all transactions for the period to categorize cash flows
        $startDate = Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth();

        // Get all transactions that involve cash accounts
        $cashTransactions = JurnalUmum::whereIn('akun_id', $kasAccountIds)
            ->whereBetween('tanggal_bayar', [$startDate, $endDate])
            ->with('akun')
            ->orderBy('tanggal_bayar')
            ->get();

        // Group transactions by reference or description to find related entries
        $transactionGroups = $cashTransactions->groupBy(function($transaction) {
            return $transaction->tanggal_bayar->format('Y-m-d') . '|' . ($transaction->keterangan ?? 'no-desc');
        });

        foreach ($transactionGroups as $transactions) {
            foreach ($transactions as $transaction) {
                $account = $transaction->akun;
                if (!$account) continue;

                // For cash accounts: Debit = Cash In, Credit = Cash Out
                $cashFlow = $transaction->debet - $transaction->kredit;

                // Skip if no cash impact
                if ($cashFlow == 0) continue;

                // Find the corresponding non-cash account to determine activity type
                $correspondingAccount = $this->findCorrespondingAccount($transaction, $kasAccountIds);
                $activityAccount = $correspondingAccount ?? $account;

                // Determine category and type
                $kategori = 'operasi'; // default
                if ($this->isInvestingActivity($activityAccount)) {
                    $kategori = 'investasi';
                } elseif ($this->isFinancingActivity($activityAccount)) {
                    $kategori = 'pendanaan';
                }

                $jenis = $cashFlow > 0 ? 'masuk' : 'keluar';

                // Save to database
                LaporanArusKas::create([
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                    'tanggal' => $transaction->tanggal_bayar,
                    'keterangan' => $transaction->keterangan ?? $activityAccount->account_name,
                    'akun_id' => $account->id,
                    'nama_akun' => $account->account_name,
                    'kategori' => $kategori,
                    'jenis' => $jenis,
                    'jumlah' => abs($cashFlow),
                ]);
            }
        }
    }

    /**
     * Update Laporan Perubahan Ekuitas
     */
    public function updateLaporanPerubahanEkuitas($bulan, $tahun)
    {
        // Get equity accounts
        $equityAccounts = JournalAccount::where('is_active', true)
            ->where('account_type', 'equity')
            ->orderBy('account_number')
            ->orderBy('account_name')
            ->get();
        
        // Clear existing data for this period
        LaporanPerubahanEkuitas::where('bulan', $bulan)
                               ->where('tahun', $tahun)
                               ->delete();
        
        foreach ($equityAccounts as $account) {
            // Calculate opening balance (previous month)
            $openingBalance = $this->getClosingBalance($account, $bulan - 1, $tahun);
            if ($bulan == 1) {
                $openingBalance = $this->getClosingBalance($account, 12, $tahun - 1);
            }
            
            // Calculate closing balance for the selected month
            $closingBalance = $this->getClosingBalance($account, $bulan, $tahun);
            
            // Calculate changes during the period
            $perubahan = $closingBalance - $openingBalance;
            
            // Save to database
            LaporanPerubahanEkuitas::create([
                'bulan' => $bulan,
                'tahun' => $tahun,
                'akun_id' => $account->id,
                'kode_akun' => $account->account_number,
                'nama_akun' => $account->account_name,
                'jenis_akun' => $account->account_type,
                'saldo_awal' => abs($openingBalance),
                'perubahan' => $perubahan,
                'saldo_akhir' => abs($closingBalance),
            ]);
        }
    }

    /**
     * Get closing balance for specific account, month and year
     */
    private function getClosingBalance($account, $month, $year)
    {
        // Handle negative month (previous year)
        if ($month <= 0) {
            $month = 12 + $month;
            $year = $year - 1;
        }
        
        $monthEnd = Carbon::createFromDate($year, $month, 1)->endOfMonth()->endOfDay();
        $openingBalanceDate = $account->opening_balance_date 
            ? Carbon::parse($account->opening_balance_date) 
            : Carbon::createFromDate(2000, 1, 1);
        
        // Jika bulan yang diminta sebelum opening balance date
        if ($monthEnd->lt($openingBalanceDate)) {
            return 0;
        }

        // Get all transactions from opening balance date to end of specified month
        $allTransactions = JurnalUmum::where('akun_id', $account->id)
            ->where('tanggal_bayar', '>=', $openingBalanceDate)
            ->where('tanggal_bayar', '<=', $monthEnd)
            ->orderBy('tanggal_bayar')
            ->orderBy('id')
            ->get();

        // Start with opening balance
        $balance = abs($account->opening_balance ?? 0);
        
        // Apply all transactions
        foreach ($allTransactions as $transaction) {
            if (strtolower($account->account_position) === 'debit') {
                $balance += $transaction->debet - $transaction->kredit;
            } else {
                $balance += $transaction->kredit - $transaction->debet;
            }
        }

        return $balance;
    }

    /**
     * Get classification for account
     */
    private function getKlasifikasi($account)
    {
        if ($account->account_type === 'asset') {
            return $this->isAktivaLancar($account->account_name) ? 'aktiva_lancar' : 'aktiva_tetap';
        } elseif ($account->account_type === 'liability') {
            return 'kewajiban';
        } elseif ($account->account_type === 'equity') {
            return 'ekuitas';
        }
        
        return 'aktiva_lancar'; // default
    }

    /**
     * Determine if account is current asset (aktiva lancar)
     */
    private function isAktivaLancar($accountName)
    {
        $aktivaLancarKeywords = [
            'kas', 'bank', 'piutang', 'persediaan', 'inventory', 
            'sewa dibayar', 'biaya dibayar', 'setara kas'
        ];
        
        $accountNameLower = strtolower($accountName);
        
        foreach ($aktivaLancarKeywords as $keyword) {
            if (strpos($accountNameLower, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if account is operating activity
     */
    private function isOperatingActivity($account)
    {
        if (!$account) return true; // Default to operating if unknown

        $operatingKeywords = [
            'pendapatan', 'revenue', 'penjualan', 'jasa', 'bunga', 'service',
            'beban', 'expense', 'gaji', 'operasional', 'administrasi', 'salary',
            'piutang', 'utang', 'persediaan', 'receivable', 'payable', 'inventory',
            'pajak', 'tax', 'listrik', 'air', 'telepon', 'internet', 'sewa', 'rent'
        ];

        $accountNameLower = strtolower($account->account_name ?? '');

        foreach ($operatingKeywords as $keyword) {
            if (strpos($accountNameLower, $keyword) !== false) {
                return true;
            }
        }

        return in_array($account->account_type ?? '', ['income', 'expense', 'current_asset', 'current_liability']);
    }

    /**
     * Check if account is investing activity
     */
    private function isInvestingActivity($account)
    {
        if (!$account) return false;

        $investingKeywords = [
            'peralatan', 'equipment', 'aset tetap', 'fixed asset',
            'investasi', 'investment', 'properti', 'kendaraan', 'vehicle',
            'mesin', 'machine', 'gedung', 'building', 'tanah', 'land',
            'furniture', 'computer', 'komputer'
        ];

        $accountNameLower = strtolower($account->account_name ?? '');

        foreach ($investingKeywords as $keyword) {
            if (strpos($accountNameLower, $keyword) !== false) {
                return true;
            }
        }

        return in_array($account->account_type ?? '', ['fixed_asset', 'long_term_investment']);
    }

    /**
     * Check if account is financing activity
     */
    private function isFinancingActivity($account)
    {
        if (!$account) return false;

        $financingKeywords = [
            'modal', 'capital', 'pinjaman', 'loan', 'kredit', 'credit',
            'dividen', 'dividend', 'saham', 'stock', 'obligasi', 'bond',
            'hutang jangka panjang', 'long term debt', 'utang bank'
        ];

        $accountNameLower = strtolower($account->account_name ?? '');

        foreach ($financingKeywords as $keyword) {
            if (strpos($accountNameLower, $keyword) !== false) {
                return true;
            }
        }

        return in_array($account->account_type ?? '', ['equity', 'long_term_liability']);
    }

    /**
     * Find corresponding account for cash transaction
     */
    private function findCorrespondingAccount($cashTransaction, $kasAccountIds)
    {
        // Method 1: Look for transactions on the same date with same description
        $correspondingTransaction = JurnalUmum::where('tanggal_bayar', $cashTransaction->tanggal_bayar)
            ->where('id', '!=', $cashTransaction->id)
            ->where('keterangan', $cashTransaction->keterangan)
            ->whereNotIn('akun_id', $kasAccountIds)
            ->first();

        if ($correspondingTransaction && $correspondingTransaction->akun) {
            return $correspondingTransaction->akun;
        }

        // Method 2: If no corresponding transaction found, try to categorize based on description
        $description = strtolower($cashTransaction->keterangan ?? '');

        // Operating activity keywords
        if (str_contains($description, 'penjualan') ||
            str_contains($description, 'pendapatan') ||
            str_contains($description, 'beban') ||
            str_contains($description, 'gaji') ||
            str_contains($description, 'operasional')) {
            // Create a dummy account object for operating activities
            return (object) [
                'account_name' => $cashTransaction->keterangan,
                'account_type' => 'income'
            ];
        }

        // Investment activity keywords
        if (str_contains($description, 'peralatan') ||
            str_contains($description, 'aset') ||
            str_contains($description, 'investasi') ||
            str_contains($description, 'properti')) {
            return (object) [
                'account_name' => $cashTransaction->keterangan,
                'account_type' => 'fixed_asset'
            ];
        }

        // Financing activity keywords
        if (str_contains($description, 'modal') ||
            str_contains($description, 'pinjaman') ||
            str_contains($description, 'dividen') ||
            str_contains($description, 'saham')) {
            return (object) [
                'account_name' => $cashTransaction->keterangan,
                'account_type' => 'equity'
            ];
        }

        return null;
    }
}
