<?php

namespace App\Http\Controllers;

use App\Models\JurnalUmum;
use App\Models\JournalAccount;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class LaporanArusKasController extends Controller
{
    public function exportPdf(Request $request)
    {
        $bulan = (int) ($request->get('bulan') ?? now()->month);
        $tahun = (int) ($request->get('tahun') ?? now()->year);
        
        $data = $this->getViewData($bulan, $tahun);
        
        $pdf = Pdf::loadView('pdf.laporan-arus-kas', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'sans-serif',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

        $filename = 'laporan-arus-kas-' . $data['bulan_nama'] . '-' . $data['tahun'] . '.pdf';
        
        // Return PDF for preview (like print mode) instead of direct download
        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    private function getViewData($bulan, $tahun)
    {
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

        // Calculate opening cash balance (previous month)
        $kasAwal = 0;
        foreach ($kasAccounts as $account) {
            $kasAwal += $this->getClosingBalance($account, $bulan - 1, $tahun);
        }

        // Calculate closing cash balance (current month)
        $kasAkhir = 0;
        foreach ($kasAccounts as $account) {
            $kasAkhir += $this->getClosingBalance($account, $bulan, $tahun);
        }

        // Get all transactions for the period to categorize cash flows
        $startDate = Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth();

        $transactions = JurnalUmum::whereBetween('tanggal_bayar', [$startDate, $endDate])
            ->with('akun')
            ->orderBy('tanggal_bayar')
            ->get();

        // Categorize cash flows
        $arusOperasi = collect();
        $arusInvestasi = collect();
        $arusPendanaan = collect();

        $totalOperasi = 0;
        $totalInvestasi = 0;
        $totalPendanaan = 0;

        foreach ($transactions as $transaction) {
            $account = $transaction->akun;
            if (!$account) continue;

            $amount = $transaction->debet - $transaction->kredit;
            
            // Skip if no cash impact
            if ($amount == 0) continue;

            $flowData = (object) [
                'tanggal' => $transaction->tanggal_bayar,
                'keterangan' => $transaction->keterangan ?? $account->account_name,
                'jumlah' => abs($amount),
                'type' => $amount > 0 ? 'masuk' : 'keluar',
                'account_type' => $account->account_type,
                'account_name' => $account->account_name,
            ];

            // Categorize based on account type and name
            if ($this->isOperatingActivity($account)) {
                $arusOperasi->push($flowData);
                $totalOperasi += $amount;
            } elseif ($this->isInvestingActivity($account)) {
                $arusInvestasi->push($flowData);
                $totalInvestasi += $amount;
            } elseif ($this->isFinancingActivity($account)) {
                $arusPendanaan->push($flowData);
                $totalPendanaan += $amount;
            }
        }

        // Calculate net cash flow
        $arusKasBersih = $totalOperasi + $totalInvestasi + $totalPendanaan;

        $date = Carbon::createFromDate($tahun, $bulan, 1);
        
        return [
            'kas_awal' => $kasAwal,
            'kas_akhir' => $kasAkhir,
            'arus_operasi' => $arusOperasi,
            'arus_investasi' => $arusInvestasi,
            'arus_pendanaan' => $arusPendanaan,
            'total_operasi' => $totalOperasi,
            'total_investasi' => $totalInvestasi,
            'total_pendanaan' => $totalPendanaan,
            'arus_kas_bersih' => $arusKasBersih,
            'periode' => $date->format('F Y'),
            'bulan_nama' => $date->locale('id')->monthName,
            'tahun' => $tahun,
            'tanggal_cetak' => now()->locale('id')->isoFormat('dddd, D MMMM Y'),
        ];
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
        
        if ($monthEnd->lt($openingBalanceDate)) {
            return 0;
        }

        $allTransactions = JurnalUmum::where('akun_id', $account->id)
            ->where('tanggal_bayar', '>=', $openingBalanceDate)
            ->where('tanggal_bayar', '<=', $monthEnd)
            ->orderBy('tanggal_bayar')
            ->orderBy('id')
            ->get();

        $balance = abs($account->opening_balance ?? 0);
        
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
     * Determine if account is related to operating activities
     */
    private function isOperatingActivity($account)
    {
        $operatingKeywords = [
            'pendapatan', 'revenue', 'penjualan', 'jasa', 'bunga',
            'beban', 'expense', 'gaji', 'operasional', 'administrasi',
            'piutang', 'utang', 'persediaan'
        ];
        
        $accountNameLower = strtolower($account->account_name);
        
        foreach ($operatingKeywords as $keyword) {
            if (strpos($accountNameLower, $keyword) !== false) {
                return true;
            }
        }
        
        return in_array($account->account_type, ['revenue', 'expense']);
    }

    /**
     * Determine if account is related to investing activities
     */
    private function isInvestingActivity($account)
    {
        $investingKeywords = [
            'peralatan', 'equipment', 'aset tetap', 'fixed asset',
            'investasi', 'investment', 'properti', 'kendaraan'
        ];
        
        $accountNameLower = strtolower($account->account_name);
        
        foreach ($investingKeywords as $keyword) {
            if (strpos($accountNameLower, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Determine if account is related to financing activities
     */
    private function isFinancingActivity($account)
    {
        $financingKeywords = [
            'modal', 'capital', 'pinjaman', 'loan', 'kredit',
            'dividen', 'dividend', 'saham', 'stock', 'obligasi'
        ];
        
        $accountNameLower = strtolower($account->account_name);
        
        foreach ($financingKeywords as $keyword) {
            if (strpos($accountNameLower, $keyword) !== false) {
                return true;
            }
        }
        
        return $account->account_type === 'equity';
    }
}
