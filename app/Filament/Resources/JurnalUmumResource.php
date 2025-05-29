<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JurnalUmumResource\Pages;
use App\Models\JurnalUmum;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DatePicker;

class JurnalUmumResource extends Resource
{
    protected static ?string $model = JurnalUmum::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationGroup = 'Akuntansi';
    
    protected static ?string $modelLabel = 'Jurnal Transaksi';
    
    protected static ?int $navigationSort = 3;

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
            ->filters([
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        DatePicker::make('dari_tanggal')
                            ->label('Dari Tanggal')
                            ->native(false)
                            ->placeholder('DD/MM/YYYY'),
                        DatePicker::make('sampai_tanggal')
                            ->label('Sampai Tanggal')
                            ->native(false)
                            ->placeholder('DD/MM/YYYY'),
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
                    })
            ])
            ->actions([
                // Tables\Actions\ViewAction::make()
                //     ->icon('heroicon-m-eye')
                //     ->iconButton(),
            ])
            ->bulkActions([])
            ->groups([
                'tanggal_bayar',
            ])
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