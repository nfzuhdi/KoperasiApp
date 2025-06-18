<?php

namespace App\Filament\Resources\MemberResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;

class SavingsRelationManager extends RelationManager
{
    protected static string $relationship = 'savings';
    
    protected static ?string $title = 'Simpanan Anggota';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('saving_product.savings_product_name')
                    ->label('PRODUK SIMPANAN')
                    ->searchable(),
                BadgeColumn::make('status')
                    ->label('STATUS')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'active',
                        'danger' => 'inactive',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Aktif',
                        'pending' => 'Pending',
                        'inactive' => 'Tidak Aktif',
                        default => $state,
                    }),
                TextColumn::make('balance')
                    ->label('SALDO')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('TANGGAL DIBUAT')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                TextColumn::make('last_transaction_date')
                    ->label('TRANSAKSI TERAKHIR')
                    ->dateTime('d/m/Y H:i:s')
                    ->placeholder('Belum ada transaksi')
                    ->sortable(),
            ])
            ->actions([
                \Filament\Tables\Actions\ViewAction::make()
                ->url(fn ($record) => url("/app/savings/{$record->id}"))
            ]);
    }
}