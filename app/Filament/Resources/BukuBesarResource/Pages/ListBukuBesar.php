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
                        Select::make('jenis_periode')
                            ->label('Jenis Periode')
                            ->options([
                                'bulanan' => 'Bulanan',
                                'tahunan' => 'Tahunan',
                            ])
                            ->default('bulanan')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Reset bulan when switching to tahunan
                                if ($state === 'tahunan') {
                                    $set('bulan', null);
                                } else {
                                    $set('bulan', now()->month);
                                }
                            }),

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
                            ->required(fn (callable $get) => $get('jenis_periode') === 'bulanan')
                            ->visible(fn (callable $get) => $get('jenis_periode') === 'bulanan')
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
                    // Get current filter data
                    $jenisPeriode = $this->data['jenis_periode'] ?? 'bulanan';
                    $params = [
                        'jenis_periode' => $jenisPeriode,
                        'bulan' => $jenisPeriode === 'bulanan' ? ($this->data['bulan'] ?? now()->month) : null,
                        'tahun' => $this->data['tahun'] ?? now()->year,
                        'akun_id' => $this->data['akun_id'] ?? null,
                        'position' => $this->data['position'] ?? null,
                        'saldo' => $this->data['saldo'] ?? null,
                    ];

                    // Build URL with parameters for preview
                    $url = route('buku-besar.export-pdf', array_filter($params));

                    // Open in new tab for preview (like print mode)
                    $this->js("window.open('$url', '_blank')");
                }),
        ];
    }

    public function getTitle(): string 
    {
        return 'Buku Besar';
    }

    protected function getViewData(): array
    {
        $jenisPeriode = $this->data['jenis_periode'] ?? 'bulanan';
        $bulan = $jenisPeriode === 'bulanan' ? (int) ($this->data['bulan'] ?? now()->month) : null;
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
            $transactionQuery = JurnalUmum::where('akun_id', $account->id);

            if ($jenisPeriode === 'bulanan') {
                $transactionQuery->whereMonth('tanggal_bayar', $bulan)
                                ->whereYear('tanggal_bayar', $tahun);
                $selectedPeriodStart = Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth();
            } else {
                // Tahunan - ambil semua transaksi dalam tahun tersebut
                $transactionQuery->whereYear('tanggal_bayar', $tahun);
                $selectedPeriodStart = Carbon::createFromDate($tahun, 1, 1)->startOfYear();
            }

            $currentTransactions = $transactionQuery->orderBy('tanggal_bayar')->get();

            // Create collection for this account's entries
            $accountEntries = collect();

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
            $entries = $entries->filter(function ($accountEntries) {
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

        if ($jenisPeriode === 'bulanan') {
            $date = Carbon::createFromDate($tahun, $bulan, 1);
            $periode = $date->format('F Y');
            $bulanNama = $date->locale('id')->monthName;
        } else {
            $periode = "Tahun $tahun";
            $bulanNama = "Tahunan";
        }

        return [
            'entries' => $entries,
            'periode' => $periode,
            'bulan_nama' => $bulanNama,
            'tahun' => $tahun,
            'jenis_periode' => $jenisPeriode,
        ];
    }

    /**
     * Calculate opening balance for a specific period
     */
    private function calculateOpeningBalance($account, $selectedPeriodStart)
    {
        $previousPeriodEnd = $selectedPeriodStart->copy()->subDay()->endOfDay();

        $openingBalanceDate = $account->opening_balance_date
            ? Carbon::parse($account->opening_balance_date)
            : Carbon::createFromDate(2000, 1, 1);

        // If selected period is before or at opening balance date, return opening balance
        if ($selectedPeriodStart->lte($openingBalanceDate)) {
            return abs($account->opening_balance ?? 0);
        }

        $allTransactions = JurnalUmum::where('akun_id', $account->id)
            ->where('tanggal_bayar', '>=', $openingBalanceDate)
            ->where('tanggal_bayar', '<=', $previousPeriodEnd)
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