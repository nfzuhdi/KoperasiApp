<?php

namespace App\Http\Controllers;

use App\Models\JurnalUmum;
use App\Models\JournalAccount;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class LaporanLabaRugiController extends Controller
{
    public function exportPdf(Request $request)
    {
        $bulan = (int) ($request->get('bulan') ?? now()->month);
        $tahun = (int) ($request->get('tahun') ?? now()->year);

        // Data is automatically saved to database via JurnalUmum model events

        $data = $this->getViewData($bulan, $tahun);

        $pdf = Pdf::loadView('pdf.laporan-laba-rugi', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'sans-serif',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

        $filename = 'laporan-laba-rugi-' . $data['bulan_nama'] . '-' . $data['tahun'] . '.pdf';

        // Return PDF for preview (like print mode) instead of direct download
        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    private function getViewData($bulan, $tahun)
    {
        // Get all active income and expense accounts (matching List implementation)
        $accounts = JournalAccount::where('is_active', true)
            ->whereIn('account_type', ['income', 'expense'])
            ->orderBy('account_number')
            ->orderBy('account_name')
            ->get();

        $pendapatan = collect();
        $beban = collect();

        $totalPendapatan = 0;
        $totalBeban = 0;

        foreach ($accounts as $account) {
            // Calculate closing balance for the selected month
            $closingBalance = $this->getClosingBalance($account, $bulan, $tahun);

            // Skip accounts with zero balance
            if ($closingBalance == 0) {
                continue;
            }

            $accountData = (object) [
                'kode_akun' => $account->account_number,
                'nama_akun' => $account->account_name,
                'saldo' => abs($closingBalance),
                'account_type' => $account->account_type,
            ];

            // Categorize accounts (matching List implementation)
            if ($account->account_type === 'income') {
                $pendapatan->push($accountData);
                $totalPendapatan += abs($closingBalance);
            } elseif ($account->account_type === 'expense') {
                $beban->push($accountData);
                $totalBeban += abs($closingBalance);
            }
        }
        
        // Calculate profit/loss
        $labaRugi = $totalPendapatan - $totalBeban;

        $date = Carbon::createFromDate($tahun, $bulan, 1);
        
        return [
            'pendapatan' => $pendapatan,
            'beban' => $beban,
            'total_pendapatan' => $totalPendapatan,
            'total_beban' => $totalBeban,
            'laba_rugi' => $labaRugi,
            'is_profit' => $labaRugi >= 0,
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
