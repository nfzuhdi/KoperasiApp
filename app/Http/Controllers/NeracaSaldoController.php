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
        $jenisPeriode = $request->get('jenis_periode', 'bulanan');
        $bulan = $jenisPeriode === 'bulanan' ? (int) ($request->get('bulan') ?? now()->month) : null;
        $tahun = (int) ($request->get('tahun') ?? now()->year);
        $showZeroBalance = $request->get('show_zero_balance') ?? 'no';

        // Data is automatically saved to database via JurnalUmum model events

        $data = $this->getViewData($bulan, $tahun, $showZeroBalance, $jenisPeriode);

        // Pastikan menggunakan toleransi untuk perbandingan floating-point
        $data['is_balanced'] = abs($data['total_debet'] - $data['total_kredit']) < 0.01;

        $pdf = Pdf::loadView('exports.neraca-saldo-pdf', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'sans-serif',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

        $filename = $jenisPeriode === 'bulanan'
            ? 'neraca-saldo-' . $data['bulan_nama'] . '-' . $data['tahun'] . '.pdf'
            : 'neraca-saldo-tahunan-' . $data['tahun'] . '.pdf';

        // Return PDF for preview (like print mode) instead of direct download
        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    public function exportExcel(Request $request)
    {
        $jenisPeriode = $request->get('jenis_periode', 'bulanan');
        $bulan = $jenisPeriode === 'bulanan' ? (int) ($request->get('bulan') ?? now()->month) : null;
        $tahun = (int) ($request->get('tahun') ?? now()->year);
        $showZeroBalance = $request->get('show_zero_balance') ?? 'no';

        // Data is automatically saved to database via JurnalUmum model events

        $data = $this->getViewData($bulan, $tahun, $showZeroBalance, $jenisPeriode);
        $filename = $jenisPeriode === 'bulanan'
            ? 'neraca-saldo-' . $data['bulan_nama'] . '-' . $data['tahun'] . '.xlsx'
            : 'neraca-saldo-tahunan-' . $data['tahun'] . '.xlsx';

        return Excel::download(new NeracaSaldoExport($data), $filename);
    }

    private function getViewData($bulan, $tahun, $showZeroBalance, $jenisPeriode = 'bulanan')
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
            // Calculate closing balance for the selected period
            $closingBalance = $this->getClosingBalance($account, $bulan, $tahun, $jenisPeriode);
            
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
                'kode_akun' => $account->account_number,
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

        if ($jenisPeriode === 'bulanan') {
            $date = Carbon::createFromDate($tahun, $bulan, 1);
            $periode = $date->format('F Y');
            $bulanNama = $date->locale('id')->monthName;
        } else {
            $periode = "Tahun $tahun";
            $bulanNama = "Tahunan";
        }

        return [
            'neraca_saldo' => $neracaSaldo,
            'total_debet' => $totalDebet,
            'total_kredit' => $totalKredit,
            'selisih' => abs($totalDebet - $totalKredit),
            'is_balanced' => $totalDebet == $totalKredit,
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


}
