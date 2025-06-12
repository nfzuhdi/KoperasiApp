<?php

namespace App\Filament\Resources\NeracaSaldoResource\Pages;

use App\Filament\Resources\NeracaSaldoResource;
use App\Models\JurnalUmum;
use App\Models\JournalAccount;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Pages\ListRecords;

class ListNeracaSaldo extends ListRecords implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = NeracaSaldoResource::class;

    protected static string $view = 'filament.pages.neraca-saldo';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'bulan' => now()->month,
            'tahun' => now()->year,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filter Periode')
                    ->description('Pilih periode untuk menampilkan neraca saldo')
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

                        Select::make('show_zero_balance')
                            ->label('Tampilkan Saldo Nol')
                            ->options([
                                'yes' => 'Ya, tampilkan semua akun',
                                'no' => 'Tidak, hanya yang ada saldo'
                            ])
                            ->default('no')
                            ->live(),
                    ])
                    ->columns(3)
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
                ->url(fn () => route('neraca-saldo.export-pdf', [
                    'bulan' => $this->data['bulan'] ?? now()->month,
                    'tahun' => $this->data['tahun'] ?? now()->year,
                    'show_zero_balance' => $this->data['show_zero_balance'] ?? 'no'
                ]))
                ->openUrlInNewTab(),

            Actions\Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-table-cells')
                ->color('info')
                ->url(fn () => route('neraca-saldo.export-excel', [
                    'bulan' => $this->data['bulan'] ?? now()->month,
                    'tahun' => $this->data['tahun'] ?? now()->year,
                    'show_zero_balance' => $this->data['show_zero_balance'] ?? 'no'
                ])),
        ];
    }

    public function getTitle(): string 
    {
        return 'Neraca Saldo';
    }

    protected function getViewData(): array
    {
        $bulan = (int) ($this->data['bulan'] ?? now()->month);
        $tahun = (int) ($this->data['tahun'] ?? now()->year);
        $showZeroBalance = $this->data['show_zero_balance'] ?? 'no';
        
        // Get all active accounts
        $accounts = JournalAccount::where('is_active', true)
            ->orderBy('account_number')  // Changed from account_code to kode_akun
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

    /**
     * Get account summary by type
     */
    private function getAccountSummary($neracaSaldo)
    {
        $summary = $neracaSaldo->groupBy('account_type')->map(function ($accounts, $type) {
            return [
                'type' => $type,
                'count' => $accounts->count(),
                'total_debet' => $accounts->sum('saldo_debet'),
                'total_kredit' => $accounts->sum('saldo_kredit'),
            ];
        });

        return $summary;
    }


}