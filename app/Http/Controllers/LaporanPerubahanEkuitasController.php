<?php

namespace App\Http\Controllers;

use App\Models\JurnalUmum;
use App\Models\JournalAccount;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class LaporanPerubahanEkuitasController extends Controller
{
    public function exportPdf(Request $request)
    {
        $bulan = (int) ($request->get('bulan') ?? now()->month);
        $tahun = (int) ($request->get('tahun') ?? now()->year);
        
        $data = $this->getViewData($bulan, $tahun);
        
        $pdf = Pdf::loadView('pdf.laporan-perubahan-ekuitas', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'sans-serif',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

        $filename = 'laporan-perubahan-ekuitas-' . $data['bulan_nama'] . '-' . $data['tahun'] . '.pdf';
        
        // Return PDF for preview (like print mode) instead of direct download
        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    private function getViewData($bulan, $tahun)
    {
        // Get equity accounts
        $equityAccounts = JournalAccount::where('is_active', true)
            ->where('account_type', 'equity')
            ->orderBy('account_number')
            ->orderBy('account_name')
            ->get();
        
        // Get revenue and expense accounts for profit/loss calculation
        $revenueExpenseAccounts = JournalAccount::where('is_active', true)
            ->whereIn('account_type', ['revenue', 'expense'])
            ->orderBy('account_number')
            ->orderBy('account_name')
            ->get();
        
        $ekuitas = collect();
        $totalEkuitasAwal = 0;
        $totalEkuitasAkhir = 0;
        
        // Calculate profit/loss for the period
        $totalPendapatan = 0;
        $totalBeban = 0;
        
        foreach ($revenueExpenseAccounts as $account) {
            $closingBalance = $this->getClosingBalance($account, $bulan, $tahun);
            
            if ($account->account_type === 'revenue') {
                $totalPendapatan += abs($closingBalance);
            } elseif ($account->account_type === 'expense') {
                $totalBeban += abs($closingBalance);
            }
        }
        
        $labaRugi = $totalPendapatan - $totalBeban;
        
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
            
            $accountData = (object) [
                'kode_akun' => $account->account_number,
                'nama_akun' => $account->account_name,
                'saldo_awal' => abs($openingBalance),
                'perubahan' => $perubahan,
                'saldo_akhir' => abs($closingBalance),
                'account_type' => $account->account_type,
            ];
            
            $ekuitas->push($accountData);
            $totalEkuitasAwal += abs($openingBalance);
            $totalEkuitasAkhir += abs($closingBalance);
        }

        $date = Carbon::createFromDate($tahun, $bulan, 1);
        
        return [
            'ekuitas' => $ekuitas,
            'total_ekuitas_awal' => $totalEkuitasAwal,
            'total_ekuitas_akhir' => $totalEkuitasAkhir,
            'laba_rugi' => $labaRugi,
            'is_profit' => $labaRugi >= 0,
            'total_perubahan' => $totalEkuitasAkhir - $totalEkuitasAwal,
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
}
