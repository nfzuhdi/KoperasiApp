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
                ->url(fn () => route('laporan-posisi-keuangan.export-pdf', array_filter([
                    'jenis_periode' => $this->data['jenis_periode'] ?? 'bulanan',
                    'bulan' => ($this->data['jenis_periode'] ?? 'bulanan') === 'bulanan' ? ($this->data['bulan'] ?? now()->month) : null,
                    'tahun' => $this->data['tahun'] ?? now()->year,
                ])))
                ->openUrlInNewTab(),
        ];
    }

    public function getTitle(): string
    {
        return 'Laporan Posisi Keuangan';
    }

    protected function getViewData(): array
    {
        $jenisPeriode = $this->data['jenis_periode'] ?? 'bulanan';
        $bulan = $jenisPeriode === 'bulanan' ? (int) ($this->data['bulan'] ?? now()->month) : null;
        $tahun = (int) ($this->data['tahun'] ?? now()->year);

        $accounts = JournalAccount::where('is_active', true)
            ->whereIn('account_type', ['asset', 'liability', 'equity'])
            ->orderBy('account_number')
            ->orderBy('account_name')
            ->get();

        $aktivaLancar = collect();
        $aktivaTetap = collect();
        $kewajiban = collect();
        $ekuitas = collect();

        foreach ($accounts as $account) {
            $saldo = $this->getClosingBalance($account, $bulan, $tahun, $jenisPeriode);

            if (abs($saldo) < 1) continue;

            $item = (object)[
                'kode_akun' => $account->account_number,
                'nama_akun' => $account->account_name,
                'saldo' => round(abs($saldo)),
                'account_type' => $account->account_type,
            ];

            if ($account->account_type === 'asset') {
                if ($this->isAktivaLancar($account->account_name)) {
                    $aktivaLancar->push($item);
                } else {
                    $aktivaTetap->push($item);
                }
            }

            if ($account->account_type === 'liability') {
                $kewajiban->push($item);
            }

            if ($account->account_type === 'equity') {
                $ekuitas->push($item);
            }
        }

        $totalAktivaLancar = $aktivaLancar->sum('saldo');
        $totalAktivaTetap  = $aktivaTetap->sum('saldo');
        $totalAktiva       = $totalAktivaLancar + $totalAktivaTetap;
        $totalKewajiban    = $kewajiban->sum('saldo');
        $totalEkuitas      = $ekuitas->sum('saldo');
        $totalPasiva       = $totalKewajiban + $totalEkuitas;

        if ($jenisPeriode === 'bulanan') {
            $date = Carbon::createFromDate($tahun, $bulan, 1);
            $periode = $date->format('F Y');
            $bulanNama = $date->locale('id')->monthName;
        } else {
            $periode = "Tahun $tahun";
            $bulanNama = "Tahunan";
        }

        return [
            'aktiva_lancar'       => $aktivaLancar,
            'aktiva_tetap'        => $aktivaTetap,
            'kewajiban'           => $kewajiban,
            'ekuitas'             => $ekuitas,
            'total_aktiva_lancar' => $totalAktivaLancar,
            'total_aktiva_tetap'  => $totalAktivaTetap,
            'total_aktiva'        => $totalAktiva,
            'total_kewajiban'     => $totalKewajiban,
            'total_ekuitas'       => $totalEkuitas,
            'total_pasiva'        => $totalPasiva,
            'is_balanced'         => abs($totalAktiva - $totalPasiva) < 1,
            'selisih'             => abs($totalAktiva - $totalPasiva),
            'periode'             => $periode,
            'bulan_nama'          => $bulanNama,
            'tahun'               => $tahun,
            'jenis_periode'       => $jenisPeriode,
            'tanggal_cetak'       => now()->locale('id')->isoFormat('dddd, D MMMM Y'),
        ];
    }

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

        if ($periodEnd->lt($openingBalanceDate)) {
            return 0;
        }

        $transactions = JurnalUmum::where('akun_id', $account->id)
            ->whereBetween('tanggal_bayar', [$openingBalanceDate, $periodEnd])
            ->orderBy('tanggal_bayar')
            ->orderBy('id')
            ->get();

        $balance = abs($account->opening_balance ?? 0);

        foreach ($transactions as $trx) {
            if (strtolower($account->account_position) === 'debit') {
                $balance += $trx->debet - $trx->kredit;
            } else {
                $balance += $trx->kredit - $trx->debet;
            }
        }

        return $balance;
    }

    private function isAktivaLancar($accountName)
    {
        $keywords = ['kas', 'bank', 'piutang', 'persediaan', 'inventory', 'sewa dibayar', 'biaya dibayar', 'setara kas'];
        $lower = strtolower($accountName);

        foreach ($keywords as $keyword) {
            if (strpos($lower, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }
}
