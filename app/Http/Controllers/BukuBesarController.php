<?php

namespace App\Http\Controllers;

use App\Models\JournalAccount;
use App\Models\JurnalUmum;
use App\Models\BukuBesar as BukuBesarModel;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class BukuBesarController extends Controller
{
    public function exportPdf(Request $request)
    {
        $bulan = (int) ($request->get('bulan') ?? now()->month);
        $tahun = (int) ($request->get('tahun') ?? now()->year);
        $akun_id = $request->get('akun_id');
        $position = $request->get('position');
        $saldo = $request->get('saldo');

        // Get base query for active accounts
        $accountsQuery = JournalAccount::where('is_active', true);

        // Apply account filter
        if (!empty($akun_id)) {
            $accountsQuery->where('id', $akun_id);
        }

        // Apply position filter
        if (!empty($position)) {
            $accountsQuery->whereRaw('LOWER(account_position) = ?', [strtolower($position)]);
        }

        $accounts = $accountsQuery->get();
        
        $entries = collect();
        
        foreach ($accounts as $account) {
            // Get transactions for this account in selected period
            $currentTransactions = JurnalUmum::where('akun_id', $account->id)
                ->whereMonth('tanggal_bayar', $bulan)
                ->whereYear('tanggal_bayar', $tahun)
                ->orderBy('tanggal_bayar')
                ->get();

            // Create collection for this account's entries
            $accountEntries = collect();

            // Get selected period start date
            $selectedPeriodStart = Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth();
            
            // Calculate opening balance for the selected period
            $openingBalance = $this->calculateOpeningBalance($account, $selectedPeriodStart);
            
            // Initialize running balance with opening balance
            $runningBalance = $openingBalance;

            // Add opening balance entry
            $openingEntry = new BukuBesarModel();
            $openingEntry->tanggal = $selectedPeriodStart;
            $openingEntry->keterangan = 'Saldo Awal';
            $openingEntry->debet = 0;
            $openingEntry->kredit = 0;
            $openingEntry->saldo = $runningBalance;
            $openingEntry->akun = $account;
            $openingEntry->is_opening = true;
            $accountEntries->push($openingEntry);

            // Add current month transactions with running balance
            foreach ($currentTransactions as $entry) {
                if (strtolower($account->account_position) === 'debit') {
                    $runningBalance += $entry->debet - $entry->kredit;
                } else {
                    $runningBalance += $entry->kredit - $entry->debet;
                }
                
                $bukuBesarEntry = new BukuBesarModel();
                $bukuBesarEntry->tanggal = $entry->tanggal_bayar;
                $bukuBesarEntry->keterangan = $entry->keterangan;
                $bukuBesarEntry->debet = $entry->debet;
                $bukuBesarEntry->kredit = $entry->kredit;
                $bukuBesarEntry->saldo = $runningBalance;
                $bukuBesarEntry->akun = $account;
                $bukuBesarEntry->is_opening = false;
                
                $accountEntries->push($bukuBesarEntry);
            }

            // Always add entries if account is active
            $entries[$account->id] = $accountEntries;
        }

        // Apply saldo filter if selected
        if (!empty($saldo)) {
            $entries = $entries->filter(function ($accountEntries) use ($saldo) {
                $lastEntry = $accountEntries->last();
                if (!$lastEntry) return false;

                return match($saldo) {
                    'positive' => $lastEntry->saldo > 0,
                    'negative' => $lastEntry->saldo < 0,
                    'zero' => $lastEntry->saldo == 0,
                    default => true,
                };
            });
        }

        $date = Carbon::createFromDate($tahun, $bulan, 1);

        $data = [
            'entries' => $entries,
            'periode' => $date->format('F Y'),
            'bulan_nama' => $date->locale('id')->monthName,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'generated_at' => now()->format('d/m/Y H:i:s'),
        ];

        $pdf = Pdf::loadView('pdf.buku-besar', $data);
        $pdf->setPaper('A4', 'landscape');

        $filename = 'Buku_Besar_' . $date->locale('id')->monthName . '_' . $tahun . '.pdf';

        // Stream for preview (like print mode)
        return $pdf->stream($filename);
    }

    /**
     * Calculate opening balance for a specific period
     */
    private function calculateOpeningBalance($account, $selectedPeriodStart)
    {
        $previousMonthEnd = $selectedPeriodStart->copy()->subDay()->endOfDay();
        
        $openingBalanceDate = $account->opening_balance_date 
            ? Carbon::parse($account->opening_balance_date) 
            : Carbon::createFromDate(2000, 1, 1);
        
        if ($selectedPeriodStart->lte($openingBalanceDate->startOfMonth())) {
            return abs($account->opening_balance ?? 0);
        }

        $allTransactions = JurnalUmum::where('akun_id', $account->id)
            ->where('tanggal_bayar', '>=', $openingBalanceDate)
            ->where('tanggal_bayar', '<=', $previousMonthEnd)
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
}
