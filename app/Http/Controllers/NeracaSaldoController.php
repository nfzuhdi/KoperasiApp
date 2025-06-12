<?php

namespace App\Http\Controllers;

use App\Models\JurnalUmum;
use App\Models\JournalAccount;
use App\Exports\NeracaSaldoExport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class NeracaSaldoController extends Controller
{
    public function exportPdf(Request $request)
    {
        $bulan = (int) ($request->get('bulan') ?? now()->month);
        $tahun = (int) ($request->get('tahun') ?? now()->year);
        $showZeroBalance = $request->get('show_zero_balance') ?? 'no';
        
        $data = $this->getViewData($bulan, $tahun, $showZeroBalance);
        
        $pdf = Pdf::loadView('exports.neraca-saldo-pdf', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'sans-serif',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

        $filename = 'neraca-saldo-' . $data['bulan_nama'] . '-' . $data['tahun'] . '.pdf';
        
        // Return PDF for preview (like print mode) instead of direct download
        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    public function exportExcel(Request $request)
    {
        $bulan = (int) ($request->get('bulan') ?? now()->month);
        $tahun = (int) ($request->get('tahun') ?? now()->year);
        $showZeroBalance = $request->get('show_zero_balance') ?? 'no';
        
        $data = $this->getViewData($bulan, $tahun, $showZeroBalance);
        $filename = 'neraca-saldo-' . $data['bulan_nama'] . '-' . $data['tahun'] . '.xlsx';
        
        return Excel::download(new NeracaSaldoExport($data), $filename);
    }

    private function getViewData($bulan, $tahun, $showZeroBalance)
    {
        // Get all active accounts
        $accounts = JournalAccount::where('is_active', true)
            ->orderBy('account_number')
            ->orderBy('account_name')
            ->get();
        
        $neracaSaldo = collect();
        $totalDebet = 0;
        $totalKredit = 0;
        
        foreach ($accounts as $account) {
            // Calculate closing balance for the selected month
            $closingBalance = $this->getClosingBalance($account, $bulan, $tahun);
            
            // Skip accounts with zero balance if option is set
            if ($showZeroBalance === 'no' && $closingBalance == 0) {
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
            
            $totalDebet += $saldoDebet;
            $totalKredit += $saldoKredit;
            
            $neracaSaldo->push((object) [
                'kode_akun' => $account->account_code,
                'nama_akun' => $account->account_name,
                'posisi_normal' => ucfirst($account->account_position),
                'saldo_debet' => $saldoDebet,
                'saldo_kredit' => $saldoKredit,
                'saldo_balance' => $closingBalance,
                'account_type' => $account->account_type ?? 'Tidak Diketahui',
            ]);
        }

        // Sort by account code
        $neracaSaldo = $neracaSaldo->sortBy('kode_akun');

        $date = Carbon::createFromDate($tahun, $bulan, 1);

        return [
            'neraca_saldo' => $neracaSaldo,
            'total_debet' => $totalDebet,
            'total_kredit' => $totalKredit,
            'selisih' => abs($totalDebet - $totalKredit),
            'is_balanced' => $totalDebet == $totalKredit,
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
