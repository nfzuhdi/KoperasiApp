<?php

namespace App\Filament\Resources\BukuBesarResource\Pages;

use App\Filament\Resources\BukuBesarResource;
use App\Models\JurnalUmum;
use App\Models\BukuBesar as BukuBesarModel;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Pages\ListRecords;

class ListBukuBesar extends ListRecords implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = BukuBesarResource::class;

    protected static string $view = 'filament.pages.buku-besar';

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
                    ->description('Pilih periode dan filter untuk menampilkan data buku besar')
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

                        Select::make('akun_id')
                            ->label('Akun')
                            ->options(\App\Models\JournalAccount::where('is_active', true)
                                ->pluck('account_name', 'id'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->placeholder('Semua Akun'),

                        Select::make('position')
                            ->label('Posisi Akun')
                            ->options(function () {
                                $positions = \App\Models\JournalAccount::where('is_active', true)
                                    ->whereNotNull('account_position')
                                    ->pluck('account_position')
                                    ->unique()
                                    ->mapWithKeys(function ($position) {
                                        return [strtolower($position) => ucfirst($position)];
                                    });
                                
                                if ($positions->isEmpty()) {
                                    return [
                                        'debit' => 'Debit',
                                        'kredit' => 'Kredit'
                                    ];
                                }
                                
                                return $positions->toArray();
                            })
                            ->placeholder('Semua Posisi')
                            ->live(),

                        Select::make('saldo')
                            ->label('Status Saldo')
                            ->options([
                                'positive' => 'Saldo Positif',
                                'negative' => 'Saldo Negatif',
                                'zero' => 'Saldo Nol'
                            ])
                            ->placeholder('Semua Status')
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
                ->action(function () {
                    // Logic untuk export PDF
                    $this->notify('success', 'Export akan segera tersedia');
                }),
            
            Actions\Action::make('print')
                ->label('Print')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->action(function () {
                    // Logic untuk print
                    $this->notify('success', 'Fitur print akan segera tersedia');
                }),
        ];
    }

    public function getTitle(): string 
    {
        return 'Buku Besar';
    }

    protected function getViewData(): array
    {
        $bulan = (int) ($this->data['bulan'] ?? now()->month);
        $tahun = (int) ($this->data['tahun'] ?? now()->year);
        
        // Get base query for active accounts
        $accountsQuery = \App\Models\JournalAccount::where('is_active', true);

        // Apply account filter
        if (!empty($this->data['akun_id'])) {
            $accountsQuery->where('id', $this->data['akun_id']);
        }

        // Apply position filter
        if (!empty($this->data['position'])) {
            $accountsQuery->whereRaw('LOWER(account_position) = ?', [strtolower($this->data['position'])]);
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
        if (!empty($this->data['saldo'])) {
            $entries = $entries->filter(function ($accountEntries, $accountId) {
                $lastEntry = $accountEntries->last();
                if (!$lastEntry) return false;

                return match($this->data['saldo']) {
                    'positive' => $lastEntry->saldo > 0,
                    'negative' => $lastEntry->saldo < 0,
                    'zero' => $lastEntry->saldo == 0,
                    default => true,
                };
            });
        }

        $date = Carbon::createFromDate($tahun, $bulan, 1);

        return [
            'entries' => $entries,
            'periode' => $date->format('F Y'),
            'bulan_nama' => $date->locale('id')->monthName,
            'tahun' => $tahun,
        ];
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