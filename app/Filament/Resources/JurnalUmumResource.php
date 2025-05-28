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
                    ->sortable()
                    ->label('Tanggal Bayar'),
                    
                Tables\Columns\TextColumn::make('no_ref')
                    ->searchable()
                    ->label('No Ref'),
                    
                Tables\Columns\TextColumn::make('no_transaksi')
                    ->searchable()
                    ->label('No Transaksi'),
                    
                Tables\Columns\TextColumn::make('akun.account_number')
                    ->searchable()
                    ->label('Akun'),
                    
                Tables\Columns\TextColumn::make('keterangan')
                    ->searchable()
                    ->wrap()
                    ->label('Keterangan'),
                    
                Tables\Columns\TextColumn::make('debet')
                    ->money('IDR')
                    ->alignEnd()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('IDR')
                    ]),
                    
                Tables\Columns\TextColumn::make('kredit')
                    ->money('IDR')
                    ->alignEnd()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('IDR')
                    ]),
            ])
            ->defaultSort('tanggal_bayar', 'desc')
            ->filters([
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        DatePicker::make('dari_tanggal')
                            ->label('Dari Tanggal'),
                        DatePicker::make('sampai_tanggal')
                            ->label('Sampai Tanggal'),
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
                // View only karena jurnal dibuat otomatis
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->groups([
                'tanggal_bayar',
                'no_transaksi',
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