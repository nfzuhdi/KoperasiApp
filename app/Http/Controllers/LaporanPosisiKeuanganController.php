<?php

namespace App\Http\Controllers;

use App\Models\JurnalUmum;
use App\Models\JournalAccount;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class LaporanPosisiKeuanganController extends Controller
{
    public function exportPdf(Request $request)
    {
        $bulan = (int) ($request->get('bulan') ?? now()->month);
        $tahun = (int) ($request->get('tahun') ?? now()->year);
        
        $data = $this->getViewData($bulan, $tahun);
        
        $pdf = Pdf::loadView('pdf.laporan-posisi-keuangan', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'sans-serif',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

        $filename = 'laporan-posisi-keuangan-' . $data['bulan_nama'] . '-' . $data['tahun'] . '.pdf';
        
        // Return PDF for preview (like print mode) instead of direct download
        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    private function getViewData($bulan, $tahun)
    {
        // Get all active accounts
        $accounts = JournalAccount::where('is_active', true)
            ->orderBy('account_number')
            ->orderBy('account_name')
            ->get();
        
        $aktiva = collect();
        $pasiva = collect();
        
        $totalAktiva = 0;
        $totalPasiva = 0;
        
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
            
            // Categorize accounts
            if (in_array($account->account_type, ['asset'])) {
                $aktiva->push($accountData);
                $totalAktiva += abs($closingBalance);
            } elseif (in_array($account->account_type, ['liability', 'equity'])) {
                $pasiva->push($accountData);
                $totalPasiva += abs($closingBalance);
            }
        }
        
        // Group aktiva by sub-categories
        $aktivaLancar = $aktiva->filter(function ($item) {
            return $this->isAktivaLancar($item->nama_akun);
        });
        
        $aktivaTetap = $aktiva->filter(function ($item) {
            return !$this->isAktivaLancar($item->nama_akun);
        });
        
        // Group pasiva by sub-categories
        $kewajiban = $pasiva->filter(function ($item) {
            return $item->account_type === 'liability';
        });
        
        $ekuitas = $pasiva->filter(function ($item) {
            return $item->account_type === 'equity';
        });

        $date = Carbon::createFromDate($tahun, $bulan, 1);
        
        return [
            'aktiva_lancar' => $aktivaLancar,
            'aktiva_tetap' => $aktivaTetap,
            'kewajiban' => $kewajiban,
            'ekuitas' => $ekuitas,
            'total_aktiva_lancar' => $aktivaLancar->sum('saldo'),
            'total_aktiva_tetap' => $aktivaTetap->sum('saldo'),
            'total_aktiva' => $totalAktiva,
            'total_kewajiban' => $kewajiban->sum('saldo'),
            'total_ekuitas' => $ekuitas->sum('saldo'),
            'total_pasiva' => $totalPasiva,
            'is_balanced' => abs($totalAktiva - $totalPasiva) < 0.01,
            'selisih' => abs($totalAktiva - $totalPasiva),
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
}
