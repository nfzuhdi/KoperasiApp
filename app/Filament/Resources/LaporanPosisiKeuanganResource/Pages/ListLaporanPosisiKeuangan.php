<?php

namespace App\Filament\Resources\LaporanPosisiKeuanganResource\Pages;

use App\Filament\Resources\LaporanPosisiKeuanganResource;
use App\Models\JournalAccount;
use App\Models\JurnalUmum;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Pages\ListRecords;
use Carbon\Carbon;

class ListLaporanPosisiKeuangan extends ListRecords implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = LaporanPosisiKeuanganResource::class;

    protected static string $view = 'filament.pages.laporan-posisi-keuangan';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'bulan' => now()->month,
            'tahun' => now()->year,
        ]);
    }

    public function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form
            ->schema([
                Section::make('Filter Periode')
                    ->description('Pilih periode untuk menampilkan laporan posisi keuangan')
                    ->schema([
                        Select::make('bulan')
                            ->label('Bulan')
                            ->options([
                                1 => 'Januari',
                                2 => 'Februari', 
                                3 => 'Maret',
                                4 => 'April',
                                5 => 'Mei',
                                6 => 'Juni',
                                7 => 'Juli',
                                8 => 'Agustus',
                                9 => 'September',
                                10 => 'Oktober',
                                11 => 'November',
                                12 => 'Desember',
                            ])
                            ->default(now()->month)
                            ->required()
                            ->live(),
                        
                        Select::make('tahun')
                            ->label('Tahun')
                            ->options(function () {
                                $currentYear = now()->year;
                                $years = [];
                                for ($i = $currentYear - 5; $i <= $currentYear + 1; $i++) {
                                    $years[$i] = $i;
                                }
                                return $years;
                            })
                            ->default(now()->year)
                            ->required()
                            ->live(),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->url(fn () => route('laporan-posisi-keuangan.export-pdf', [
                    'bulan' => $this->data['bulan'] ?? now()->month,
                    'tahun' => $this->data['tahun'] ?? now()->year,
                ]))
                ->openUrlInNewTab(),
        ];
    }

    public function getTitle(): string 
    {
        return 'Laporan Posisi Keuangan';
    }

    protected function getViewData(): array
    {
        $bulan = (int) ($this->data['bulan'] ?? now()->month);
        $tahun = (int) ($this->data['tahun'] ?? now()->year);
        
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
