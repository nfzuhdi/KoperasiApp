<?php

namespace App\Filament\Resources\SavingResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Barryvdh\DomPDF\Facade\Pdf;

class SavingPaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'savingPayments';
    
    protected static ?string $title = 'Transaction History';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_number')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Payment Date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'cash' => 'success',
                        'transfer' => 'info',
                        'debit_card' => 'warning',
                        'credit_card' => 'warning',
                        'e_wallet' => 'primary',
                        'other' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->headerActions([])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => "/app/saving-payments/{$record->id}"),
                Action::make('print')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->action(function ($record) {
                        $pdf = Pdf::loadView('receipt.saving-payment-receipt', [
                            'payment' => $record,
                            'saving' => $record->savingAccount,
                            'member' => $record->savingAccount->member,
                        ]);
                        
                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->output();
                        }, "payment-receipt-{$record->id}.pdf");
                    })
            ])
            ->bulkActions([]);
    }
}