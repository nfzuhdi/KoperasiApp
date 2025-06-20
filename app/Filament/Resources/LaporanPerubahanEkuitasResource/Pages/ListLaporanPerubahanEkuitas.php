<?php

namespace App\Filament\Resources\LaporanPerubahanEkuitasResource\Pages;

use App\Filament\Resources\LaporanPerubahanEkuitasResource;
use App\Models\JournalAccount;
use App\Models\JurnalUmum;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Pages\ListRecords;
use Carbon\Carbon;

class ListLaporanPerubahanEkuitas extends ListRecords implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = LaporanPerubahanEkuitasResource::class;
    protected static string $view = 'filament.pages.laporan-perubahan-ekuitas';

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
                    ->description('Pilih periode untuk menampilkan laporan perubahan ekuitas')
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
                ->url(fn () => route('laporan-perubahan-ekuitas.export-pdf', [
                    'bulan' => $this->data['bulan'] ?? now()->month,
                    'tahun' => $this->data['tahun'] ?? now()->year,
                ]))
                ->openUrlInNewTab(),
        ];
    }

    public function getTitle(): string
    {
        return 'Laporan Perubahan Ekuitas';
    }

    protected function getViewData(): array
    {
        $bulan = (int) ($this->data['bulan'] ?? now()->month);
        $tahun = (int) ($this->data['tahun'] ?? now()->year);

        // Get equity accounts
        $equityAccounts = JournalAccount::where('is_active', true)
            ->where('account_type', 'equity')
            ->orderBy('account_number')
            ->orderBy('account_name')
            ->get();
        
        // Get revenue and expense accounts for profit/loss calculation
        $revenueExpenseAccounts = JournalAccount::where('is_active', true)
            ->whereIn('account_type', ['income', 'expense'])
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
            
            if ($account->account_type === 'income') {
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
}
