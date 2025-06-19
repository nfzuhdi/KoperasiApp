<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JurnalUmumResource\Pages;
use App\Models\JurnalUmum;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Enums\FiltersLayout;

class JurnalUmumResource extends Resource
{
    protected static ?string $model = JurnalUmum::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationGroup = 'Laporan Keuangan';
    
    protected static ?string $pluralLabel = 'Jurnal Umum';
    
    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal_bayar')
                    ->date('d M Y')
                    ->label('Tanggal')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('keterangan')
                    ->searchable()
                    ->wrap()
                    ->label('Keterangan')
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->akun) {
                            return $record->akun->account_name;
                        }
                        return $state;
                    }),
                
                Tables\Columns\TextColumn::make('akun.account_number')
                    ->searchable()
                    ->label('Akun')
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->akun) {
                            return "{$record->akun->account_number} - {$record->akun->account_name}";
                        }
                        return $state;
                    }),
                
                Tables\Columns\TextColumn::make('debet')
                    ->label('Debit')
                    ->money('IDR', true)
                    ->alignEnd()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total Debit')
                            ->money('IDR')
                    ]),
                
                Tables\Columns\TextColumn::make('kredit')
                    ->money('IDR', true)
                    ->alignEnd()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total Kredit')
                            ->money('IDR')
                    ]),
            ])
            ->defaultSort('tanggal_bayar')
            ->groups([
                Tables\Grouping\Group::make('tanggal_bayar')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->collapsible()
                    ->titlePrefixedWithLabel(false),
            ])
            ->defaultGroup('tanggal_bayar')
            ->groupedBulkActions([])
            ->filters([
                Tables\Filters\Filter::make('date_range')
                    ->label('Rentang Tanggal')
                    ->form([
                        \Filament\Forms\Components\Fieldset::make('Rentang Tanggal')
                            ->schema([
                                \Filament\Forms\Components\Grid::make(2) // 2 kolom
                                    ->schema([
                                        DatePicker::make('dari_tanggal')
                                            ->label('Dari Tanggal')
                                            ->native(false)
                                            ->placeholder('DD/MM/YYYY')
                                            ->columnSpan(1), // Panjang normal
                                        DatePicker::make('sampai_tanggal')
                                            ->label('Sampai Tanggal')
                                            ->native(false)
                                            ->placeholder('DD/MM/YYYY')
                                            ->columnSpan(1), // Panjang normal
                                    ]),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari_tanggal'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_bayar', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_bayar', '<=', $date),
                            );
                    }),
                Tables\Filters\Filter::make('specific_date')
                    ->label('Tanggal Spesifik')
                    ->form([
                        \Filament\Forms\Components\Fieldset::make('Tanggal Spesifik')
                            ->schema([
                                DatePicker::make('tanggal')
                                    ->label('Tanggal')
                                    ->native(false)
                                    ->placeholder('DD/MM/YYYY')
                                    ->columnSpan(2), // Panjang normal
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['tanggal'],
                            fn (Builder $query, $date): Builder => $query->whereDate('tanggal_bayar', '=', $date),
                        );
                    }),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->striped()
            ->defaultPaginationPageOption(50);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJurnalUmum::route('/'),
        ];
    }
}