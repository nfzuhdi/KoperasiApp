<?php

namespace App\Filament\Resources\LaporanLabaRugiResource\Pages;

use App\Filament\Resources\LaporanLabaRugiResource;
use App\Models\JournalAccount;
use App\Models\JurnalUmum;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Pages\ListRecords;
use Carbon\Carbon;

class ListLaporanLabaRugi extends ListRecords implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = LaporanLabaRugiResource::class;
    protected static string $view = 'filament.pages.laporan-laba-rugi';

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
                    ->description('Pilih periode untuk menampilkan laporan laba rugi')
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
                ->url(fn () => route('laporan-laba-rugi.export-pdf', [
                    'bulan' => $this->data['bulan'] ?? now()->month,
                    'tahun' => $this->data['tahun'] ?? now()->year,
                ]))
                ->openUrlInNewTab(),
        ];
    }

    public function getTitle(): string
    {
        return 'Laporan Laba Rugi';
    }

    protected function getViewData(): array
    {
        $bulan = (int) ($this->data['bulan'] ?? now()->month);
        $tahun = (int) ($this->data['tahun'] ?? now()->year);

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
            $closingBalance = $this->getClosingBalance($account, $bulan, $tahun);
            
            if ($closingBalance == 0) {
                continue;
            }

            $accountData = (object) [
                'kode_akun' => $account->account_number,
                'nama_akun' => $account->account_name,
                'saldo' => abs($closingBalance),
                'account_type' => $account->account_type,
            ];

            if ($account->account_type === 'income') {
                $pendapatan->push($accountData);
                $totalPendapatan += abs($closingBalance);
            } elseif ($account->account_type === 'expense') {
                $beban->push($accountData);
                $totalBeban += abs($closingBalance);
            }
        }

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

    private function getClosingBalance($account, $month, $year)
    {
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
