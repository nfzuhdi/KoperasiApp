<?php

namespace App\Filament\Resources\LaporanArusKasResource\Pages;

use App\Filament\Resources\LaporanArusKasResource;
use App\Models\JournalAccount;
use App\Models\JurnalUmum;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Pages\ListRecords;
use Carbon\Carbon;

class ListLaporanArusKas extends ListRecords implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = LaporanArusKasResource::class;
    protected static string $view = 'filament.pages.laporan-arus-kas';

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
                    ->description('Pilih periode untuk menampilkan laporan arus kas')
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
                ->url(fn () => route('laporan-arus-kas.export-pdf', array_filter([
                    'jenis_periode' => $this->data['jenis_periode'] ?? 'bulanan',
                    'bulan' => ($this->data['jenis_periode'] ?? 'bulanan') === 'bulanan' ? ($this->data['bulan'] ?? now()->month) : null,
                    'tahun' => $this->data['tahun'] ?? now()->year,
                ])))
                ->openUrlInNewTab(),
        ];
    }

    public function getTitle(): string
    {
        return 'Laporan Arus Kas';
    }

    protected function getViewData(): array
    {
        $jenisPeriode = $this->data['jenis_periode'] ?? 'bulanan';
        $bulan = $jenisPeriode === 'bulanan' ? (int) ($this->data['bulan'] ?? now()->month) : null;
        $tahun = (int) ($this->data['tahun'] ?? now()->year);

        // Get cash and cash equivalent accounts
        $kasAccounts = JournalAccount::where('is_active', true)
            ->where('account_type', 'asset')
            ->where(function($query) {
                $query->where('account_name', 'like', '%kas%')
                      ->orWhere('account_name', 'like', '%bank%')
                      ->orWhere('account_name', 'like', '%setara kas%');
            })
            ->orderBy('account_number')
            ->get();

        $kasAccountIds = $kasAccounts->pluck('id')->toArray();

        // Calculate opening cash balance
        $kasAwal = 0;
        if ($jenisPeriode === 'bulanan') {
            // Previous month
            foreach ($kasAccounts as $account) {
                $kasAwal += $this->getClosingBalance($account, $bulan - 1, $tahun, $jenisPeriode);
            }
        } else {
            // Previous year
            foreach ($kasAccounts as $account) {
                $kasAwal += $this->getClosingBalance($account, 12, $tahun - 1, 'bulanan');
            }
        }

        // Calculate closing cash balance
        $kasAkhir = 0;
        foreach ($kasAccounts as $account) {
            $kasAkhir += $this->getClosingBalance($account, $bulan, $tahun, $jenisPeriode);
        }

        // Get all cash transactions for the period
        if ($jenisPeriode === 'bulanan') {
            $startDate = Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth();
            $endDate = Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth();
        } else {
            // Tahunan - ambil semua transaksi dalam tahun tersebut
            $startDate = Carbon::createFromDate($tahun, 1, 1)->startOfYear();
            $endDate = Carbon::createFromDate($tahun, 12, 31)->endOfYear();
        }

        // Get all transactions that involve cash accounts
        $cashTransactions = JurnalUmum::whereIn('akun_id', $kasAccountIds)
            ->whereBetween('tanggal_bayar', [$startDate, $endDate])
            ->with('akun')
            ->orderBy('tanggal_bayar')
            ->get();

        // Group transactions by reference or description to find related entries
        $transactionGroups = $cashTransactions->groupBy(function($transaction) {
            return $transaction->tanggal_bayar->format('Y-m-d') . '|' . ($transaction->keterangan ?? 'no-desc');
        });

        // Categorize cash flows
        $arusOperasi = collect();
        $arusInvestasi = collect();
        $arusPendanaan = collect();

        $totalOperasi = 0;
        $totalInvestasi = 0;
        $totalPendanaan = 0;

        foreach ($transactionGroups as $transactions) {
            foreach ($transactions as $transaction) {
                $account = $transaction->akun;
                if (!$account) continue;

                // For cash accounts: Debit = Cash In, Credit = Cash Out
                $cashFlow = $transaction->debet - $transaction->kredit;
                
                // Skip if no cash impact
                if ($cashFlow == 0) continue;

                // Find the corresponding non-cash account to determine activity type
                $correspondingAccount = $this->findCorrespondingAccount($transaction, $kasAccountIds);
                $activityAccount = $correspondingAccount ?? $account;

                $flowData = (object) [
                    'tanggal' => $transaction->tanggal_bayar,
                    'keterangan' => $transaction->keterangan ?? $activityAccount->account_name,
                    'jumlah' => abs($cashFlow),
                    'type' => $cashFlow > 0 ? 'masuk' : 'keluar',
                    'account_type' => $activityAccount->account_type,
                    'account_name' => $activityAccount->account_name,
                    'cash_flow' => $cashFlow, // Keep original value for calculation
                ];

                // Categorize based on the activity account
                if ($this->isOperatingActivity($activityAccount)) {
                    $arusOperasi->push($flowData);
                    $totalOperasi += $cashFlow;
                } elseif ($this->isInvestingActivity($activityAccount)) {
                    $arusInvestasi->push($flowData);
                    $totalInvestasi += $cashFlow;
                } elseif ($this->isFinancingActivity($activityAccount)) {
                    $arusPendanaan->push($flowData);
                    $totalPendanaan += $cashFlow;
                } else {
                    // Default to operating activities for unclassified
                    $arusOperasi->push($flowData);
                    $totalOperasi += $cashFlow;
                }
            }
        }

        // Calculate net cash flow
        $arusKasBersih = $totalOperasi + $totalInvestasi + $totalPendanaan;

        // Verify cash flow calculation
        $calculatedCashChange = $arusKasBersih;
        $actualCashChange = $kasAkhir - $kasAwal;

        if ($jenisPeriode === 'bulanan') {
            $date = Carbon::createFromDate($tahun, $bulan, 1);
            $periode = $date->format('F Y');
            $bulanNama = $date->locale('id')->monthName;
        } else {
            $periode = "Tahun $tahun";
            $bulanNama = "Tahunan";
        }

        return [
            'kas_awal' => $kasAwal,
            'kas_akhir' => $kasAkhir,
            'arus_operasi' => $arusOperasi,
            'arus_investasi' => $arusInvestasi,
            'arus_pendanaan' => $arusPendanaan,
            'total_operasi' => $totalOperasi,
            'total_investasi' => $totalInvestasi,
            'total_pendanaan' => $totalPendanaan,
            'arus_kas_bersih' => $arusKasBersih,
            'calculated_change' => $calculatedCashChange,
            'actual_change' => $actualCashChange,
            'is_balanced' => abs($calculatedCashChange - $actualCashChange) < 0.01,
            'periode' => $periode,
            'bulan_nama' => $bulanNama,
            'tahun' => $tahun,
            'jenis_periode' => $jenisPeriode,
            'tanggal_cetak' => now()->locale('id')->isoFormat('dddd, D MMMM Y'),
        ];
    }

    private function findCorrespondingAccount($cashTransaction, $kasAccountIds)
    {
        // Method 1: Look for transactions on the same date with same description
        $correspondingTransaction = JurnalUmum::where('tanggal_bayar', $cashTransaction->tanggal_bayar)
            ->where('id', '!=', $cashTransaction->id)
            ->where('keterangan', $cashTransaction->keterangan)
            ->whereNotIn('akun_id', $kasAccountIds)
            ->first();

        if ($correspondingTransaction && $correspondingTransaction->akun) {
            return $correspondingTransaction->akun;
        }

        // Method 2: If no corresponding transaction found, try to categorize based on description
        $description = strtolower($cashTransaction->keterangan ?? '');
        
        // Operating activity keywords
        if (str_contains($description, 'penjualan') || 
            str_contains($description, 'pendapatan') ||
            str_contains($description, 'beban') ||
            str_contains($description, 'gaji') ||
            str_contains($description, 'operasional')) {
            // Create a dummy account object for operating activities
            return (object) [
                'account_name' => $cashTransaction->keterangan,
                'account_type' => 'income'
            ];
        }

        // Investment activity keywords
        if (str_contains($description, 'peralatan') ||
            str_contains($description, 'aset') ||
            str_contains($description, 'investasi') ||
            str_contains($description, 'properti')) {
            return (object) [
                'account_name' => $cashTransaction->keterangan,
                'account_type' => 'fixed_asset'
            ];
        }

        // Financing activity keywords
        if (str_contains($description, 'modal') ||
            str_contains($description, 'pinjaman') ||
            str_contains($description, 'dividen') ||
            str_contains($description, 'saham')) {
            return (object) [
                'account_name' => $cashTransaction->keterangan,
                'account_type' => 'equity'
            ];
        }

        return null;
    }

    private function getClosingBalance($account, $month, $year, $jenisPeriode = 'bulanan')
    {
        // Handle negative month (previous year)
        if ($month <= 0) {
            $month = 12 + $month;
            $year = $year - 1;
        }

        if ($jenisPeriode === 'bulanan') {
            $periodEnd = Carbon::createFromDate($year, $month, 1)->endOfMonth()->endOfDay();
        } else {
            // Tahunan - ambil sampai akhir tahun
            $periodEnd = Carbon::createFromDate($year, 12, 31)->endOfYear()->endOfDay();
        }

        $openingBalanceDate = $account->opening_balance_date
            ? Carbon::parse($account->opening_balance_date)
            : Carbon::createFromDate(2000, 1, 1);

        if ($periodEnd->lt($openingBalanceDate)) {
            return 0;
        }

        $allTransactions = JurnalUmum::where('akun_id', $account->id)
            ->where('tanggal_bayar', '>=', $openingBalanceDate)
            ->where('tanggal_bayar', '<=', $periodEnd)
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

    private function isOperatingActivity($account)
    {
        if (!$account) return true; // Default to operating if unknown
        
        $operatingKeywords = [
            'pendapatan', 'revenue', 'penjualan', 'jasa', 'bunga', 'service',
            'beban', 'expense', 'gaji', 'operasional', 'administrasi', 'salary',
            'piutang', 'utang', 'persediaan', 'receivable', 'payable', 'inventory',
            'pajak', 'tax', 'listrik', 'air', 'telepon', 'internet', 'sewa', 'rent'
        ];
        
        $accountNameLower = strtolower($account->account_name ?? '');
        
        foreach ($operatingKeywords as $keyword) {
            if (strpos($accountNameLower, $keyword) !== false) {
                return true;
            }
        }
        
        return in_array($account->account_type ?? '', ['income', 'expense', 'current_asset', 'current_liability']);
    }

    private function isInvestingActivity($account)
    {
        if (!$account) return false;
        
        $investingKeywords = [
            'peralatan', 'equipment', 'aset tetap', 'fixed asset',
            'investasi', 'investment', 'properti', 'kendaraan', 'vehicle',
            'mesin', 'machine', 'gedung', 'building', 'tanah', 'land',
            'furniture', 'computer', 'komputer'
        ];
        
        $accountNameLower = strtolower($account->account_name ?? '');
        
        foreach ($investingKeywords as $keyword) {
            if (strpos($accountNameLower, $keyword) !== false) {
                return true;
            }
        }
        
        return in_array($account->account_type ?? '', ['fixed_asset', 'long_term_investment']);
    }

    private function isFinancingActivity($account)
    {
        if (!$account) return false;
        
        $financingKeywords = [
            'modal', 'capital', 'pinjaman', 'loan', 'kredit', 'credit',
            'dividen', 'dividend', 'saham', 'stock', 'obligasi', 'bond',
            'hutang jangka panjang', 'long term debt', 'utang bank'
        ];
        
        $accountNameLower = strtolower($account->account_name ?? '');
        
        foreach ($financingKeywords as $keyword) {
            if (strpos($accountNameLower, $keyword) !== false) {
                return true;
            }
        }
        
        return in_array($account->account_type ?? '', ['equity', 'long_term_liability']);
    }
}
