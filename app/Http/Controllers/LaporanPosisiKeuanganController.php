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
        $jenisPeriode = $request->get('jenis_periode', 'bulanan');
        $bulan = $jenisPeriode === 'bulanan' ? (int) ($request->get('bulan') ?? now()->month) : null;
        $tahun = (int) ($request->get('tahun') ?? now()->year);

        // Data is automatically saved to database via JurnalUmum model events

        $data = $this->getViewData($bulan, $tahun, $jenisPeriode);

        $pdf = Pdf::loadView('pdf.laporan-posisi-keuangan', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'sans-serif',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

        $filename = $jenisPeriode === 'bulanan'
            ? 'laporan-posisi-keuangan-' . $data['bulan_nama'] . '-' . $data['tahun'] . '.pdf'
            : 'laporan-posisi-keuangan-tahunan-' . $data['tahun'] . '.pdf';

        // Return PDF for preview (like print mode) instead of direct download
        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    private function getViewData($bulan, $tahun, $jenisPeriode = 'bulanan')
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
            // Calculate closing balance for the selected period
            $closingBalance = $this->getClosingBalance($account, $bulan, $tahun, $jenisPeriode);
            
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

        if ($jenisPeriode === 'bulanan') {
            $date = Carbon::createFromDate($tahun, $bulan, 1);
            $periode = $date->format('F Y');
            $bulanNama = $date->locale('id')->monthName;
        } else {
            $periode = "Tahun $tahun";
            $bulanNama = "Tahunan";
        }

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
            'periode' => $periode,
            'bulan_nama' => $bulanNama,
            'tahun' => $tahun,
            'jenis_periode' => $jenisPeriode,
            'tanggal_cetak' => now()->locale('id')->isoFormat('dddd, D MMMM Y'),
        ];
    }

    /**
     * Get closing balance for specific account, month/year and period type
     */
    private function getClosingBalance($account, $month, $year, $jenisPeriode = 'bulanan')
    {
        if ($jenisPeriode === 'bulanan') {
            $periodEnd = Carbon::createFromDate($year, $month, 1)->endOfMonth()->endOfDay();
        } else {
            // Tahunan - ambil sampai akhir tahun
            $periodEnd = Carbon::createFromDate($year, 12, 31)->endOfYear()->endOfDay();
        }

        $openingBalanceDate = $account->opening_balance_date
            ? Carbon::parse($account->opening_balance_date)
            : Carbon::createFromDate(2000, 1, 1);

        // Jika periode yang diminta sebelum opening balance date
        if ($periodEnd->lt($openingBalanceDate)) {
            return 0;
        }

        // Get all transactions from opening balance date to end of specified period
        $allTransactions = JurnalUmum::where('akun_id', $account->id)
            ->where('tanggal_bayar', '>=', $openingBalanceDate)
            ->where('tanggal_bayar', '<=', $periodEnd)
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
