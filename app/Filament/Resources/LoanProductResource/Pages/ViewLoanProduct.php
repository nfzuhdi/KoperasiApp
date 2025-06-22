<?php

namespace App\Filament\Resources\LoanProductResource\Pages;

use App\Filament\Resources\LoanProductResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Tabs;

class ViewLoanProduct extends ViewRecord
{
    protected static string $resource = LoanProductResource::class;

    protected static ?string $title = 'Detail Produk Pinjaman';

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Tabs::make('LoanProductTabs')
                    ->tabs([
                        Tabs\Tab::make('Informasi Produk')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        Section::make('Informasi')
                                            ->schema([
                                                TextEntry::make('name')
                                                    ->label('NAMA PEMBIAYAAN'),
                                                TextEntry::make('code')
                                                    ->label('KODE PRODUK'),
                                                TextEntry::make('usage_purposes')
                                                    ->label('TUJUAN PEMBIAYAAN')
                                                    ->columnSpanFull(),
                                            ])
                                            ->columnSpan(2),
                                        Section::make('Jenis Kontrak')
                                            ->schema([
                                                TextEntry::make('contract_type')
                                                    ->label('JENIS PEMBIAYAAN')
                                                    ->badge()
                                                    ->color(fn (string $state): string => match ($state) {
                                                        'Mudharabah' => 'success',
                                                        'Musyarakah' => 'warning',
                                                        'Murabahah' => 'info',
                                                    }),
                                            ])
                                            ->columnSpan(1),
                                    ]),
                            ]),
                        Tabs\Tab::make('Ketentuan Pembiayaan')
                            ->schema([
                                Section::make('Pengaturan Umum')
                                    ->schema([
                                        TextEntry::make('min_amount')
                                            ->label('JUMLAH MINIMAL')
                                            ->money('IDR'),
                                        TextEntry::make('max_amount')
                                            ->label('JUMLAH MAKSIMAL')
                                            ->money('IDR'),
                                        TextEntry::make('min_rate')
                                            ->label('MARGIN MINIMAL')
                                            ->suffix('%'),
                                        TextEntry::make('max_rate')
                                            ->label('MARGIN MAKSIMAL')
                                            ->suffix('%'),
                                        TextEntry::make('tenor_months')
                                            ->label('TENOR (BULAN)')
                                            ->formatStateUsing(fn (string $state): string => $state . ' Bulan'),
                                    ])
                                    ->columns(2),
                            ]),
                        Tabs\Tab::make('Parameter Akun Jurnal')
                            ->schema([
                                Section::make('Akun Jurnal Pembiayaan')
                                    ->schema([
                                        TextEntry::make('balanceDebitAccount.account_name')
                                            ->label('PILIH AKUN PIUTANG')
                                            ->formatStateUsing(fn ($record, $state) => 
                                                $record->balanceDebitAccount ? 
                                                "{$record->balanceDebitAccount->account_number} - {$state}" : 
                                                '-'
                                            ),
                                        TextEntry::make('balanceCreditAccount.account_name')
                                            ->label('PILIH AKUN KAS')
                                            ->formatStateUsing(fn ($record, $state) => 
                                                $record->balanceCreditAccount ? 
                                                "{$record->balanceCreditAccount->account_number} - {$state}" : 
                                                '-'
                                            ),
                                    ])
                                    ->columns(2),
                                Section::make('Akun Jurnal Kas/Bank')
                                    ->schema([
                                        TextEntry::make('principalCreditAccount.account_name')
                                            ->label('PILIH AKUN KAS')
                                            ->formatStateUsing(fn ($record, $state) => 
                                                $record->principalCreditAccount ? 
                                                "{$record->principalCreditAccount->account_number} - {$state}" : 
                                                '-'
                                            ),
                                        TextEntry::make('principalDebitAccount.account_name')
                                            ->label('PILIH AKUN PIUTANG')
                                            ->formatStateUsing(fn ($record, $state) => 
                                                $record->principalDebitAccount ? 
                                                "{$record->principalDebitAccount->account_number} - {$state}" : 
                                                '-'
                                            ),
                                    ])
                                    ->columns(2),
                                Section::make('Akun Jurnal Pendapatan')
                                    ->schema([
                                        TextEntry::make('incomeDebitAccount.account_name')
                                            ->label('PILIH AKUN KAS')
                                            ->formatStateUsing(fn ($record, $state) => 
                                                $record->incomeDebitAccount ? 
                                                "{$record->incomeDebitAccount->account_number} - {$state}" : 
                                                '-'
                                            ),
                                        TextEntry::make('incomeCreditAccount.account_name')
                                            ->label('PILIH AKUN PENDAPATAN')
                                            ->formatStateUsing(fn ($record, $state) => 
                                                $record->incomeCreditAccount ? 
                                                "{$record->incomeCreditAccount->account_number} - {$state}" : 
                                                '-'
                                            ),
                                    ])
                                    ->columns(2),
                                Section::make('Akun Jurnal Denda')
                                    ->schema([
                                        TextEntry::make('fineDebitAccount.account_name')
                                            ->label('PILIH AKUN KAS')
                                            ->formatStateUsing(fn ($record, $state) => 
                                                $record->fineDebitAccount ? 
                                                "{$record->fineDebitAccount->account_number} - {$state}" : 
                                                '-'
                                            ),
                                        TextEntry::make('fineCreditAccount.account_name')
                                            ->label('PILIH AKUN DENDA')
                                            ->formatStateUsing(fn ($record, $state) => 
                                                $record->fineCreditAccount ? 
                                                "{$record->fineCreditAccount->account_number} - {$state}" : 
                                                '-'
                                            ),
                                    ])
                                    ->columns(2),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}