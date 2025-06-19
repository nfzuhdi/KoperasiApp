<?php

namespace App\Filament\Resources\SavingPaymentResource\Pages;

use App\Filament\Resources\SavingPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Colors\Color;

class ViewSavingPayment extends ViewRecord
{
    protected static string $resource = SavingPaymentResource::class;

    public function getTitle(): string 
    {
        return 'Detail Transaksi ' . $this->record->reference_number;
    }

    protected function getHeaderActions(): array
    {
        return [
            // Actions\EditAction::make(),
            
            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->status === 'pending' && auth()->user()->hasRole('kepala_cabang'))
                ->requiresConfirmation()
                ->modalHeading('Approve Payment')
                ->modalDescription('Are you sure you want to approve this payment?')
                ->action(function () {
                    $this->record->status = 'approved';
                    $this->record->reviewed_by = auth()->id();
                    $this->record->save();
                    
                    Notification::make()
                        ->title('Payment approved successfully')
                        ->success()
                        ->send();
                        
                    $this->redirect(SavingPaymentResource::getUrl('view', ['record' => $this->record]));
                }),
                
            Actions\Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->status === 'pending' && auth()->user()->hasRole('kepala_cabang'))
                ->form([
                    Textarea::make('rejection_notes')
                        ->label('Reason for Rejection')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->record->status = 'rejected';
                    $this->record->reviewed_by = auth()->id();
                    $this->record->notes = ($this->record->notes ? $this->record->notes . "\n\n" : '') . "Rejected: " . $data['rejection_notes'];
                    $this->record->save();
                    
                    Notification::make()
                        ->title('Payment rejected')
                        ->success()
                        ->send();
                        
                    $this->redirect(SavingPaymentResource::getUrl('view', ['record' => $this->record]));
                }),
        ];
    }
    
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Baris pertama - Account Info & Amount
                Grid::make(3)
                    ->schema([
                        Section::make('Informasi Akun')
                            ->schema([
                                TextEntry::make('savingAccount.account_number')
                                    ->label('NOMOR REKENING')
                                    ->weight(FontWeight::Bold),
                                TextEntry::make('savingAccount.member.full_name')
                                    ->label('NAMA ANGGOTA')
                                    ->color(Color::Blue),
                                TextEntry::make('payment_type')
                                    ->label('TIPE TRANSAKSI')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'deposit' => 'Setoran',
                                        'withdrawal' => 'Penarikan',
                                        default => $state,
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        'deposit' => 'success',
                                        'withdrawal' => 'warning',
                                        default => 'gray',
                                    }),
                            ])
                            ->columns(1)
                            ->columnSpan(2),

                        Section::make('Jumlah Transaksi')
                            ->schema([
                                TextEntry::make('amount')
                                    ->label('JUMLAH TRANSAKSI')
                                    ->money('IDR')
                                    ->weight(FontWeight::Bold)
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->color(Color::Emerald),
                                TextEntry::make('fine')
                                    ->label('DENDA')
                                    ->money('IDR')
                                    ->color(Color::Red)
                                    ->weight(FontWeight::Medium)
                                    ->visible(fn ($record) => $record->fine > 0),
                            ])
                            ->columnSpan(1),
                    ]),

                // Baris kedua - Payment Details & Status
                Grid::make(3)
                    ->schema([
                        Section::make('Detail Pembayaran')
                            ->schema([
                                TextEntry::make('payment_method')
                                    ->label('METODE PEMBAYARAN')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'cash' => 'Tunai',
                                        'transfer' => 'Transfer Bank',
                                        'debit_card' => 'Kartu Debit',
                                        'credit_card' => 'Kartu Kredit',
                                        'e_wallet' => 'Dompet Digital',
                                        'other' => 'Lainnya',
                                        default => ucfirst($state),
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        'cash' => 'success',
                                        'transfer' => 'info',
                                        'debit_card' => 'warning',
                                        'credit_card' => 'danger',
                                        'e_wallet' => 'purple',
                                        default => 'gray',
                                    }),
                                TextEntry::make('reference_number')
                                    ->label('NOMOR REFERENSI')
                                    ->placeholder('N/A')
                                    ->color(Color::Gray),
                                TextEntry::make('created_at')
                                    ->label('TANGGAL TRANSAKSI')
                                    ->dateTime('d/m/Y H:i:s')
                                    ->color(Color::Slate),
                            ])
                            ->columns(1)
                            ->columnSpan(2),

                        Section::make('Status')
                            ->schema([
                                TextEntry::make('status')
                                    ->label('STATUS TRANSAKSI')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'approved' => 'Disetujui',
                                        'pending' => 'Menunggu',
                                        'rejected' => 'Ditolak',
                                        default => ucfirst($state),
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        'approved' => 'success',
                                        'pending' => 'warning',
                                        'rejected' => 'danger',
                                        default => 'gray',
                                    })
                                    ->size(TextEntry\TextEntrySize::Medium)
                                    ->weight(FontWeight::Bold),
                            ])
                            ->columnSpan(1),
                    ]),

                // Baris ketiga - Notes (jika ada)
                Grid::make(1)
                    ->schema([
                        Section::make('Additional Information')
                            ->schema([
                                TextEntry::make('notes')
                                    ->label('CATATAN')
                                    ->placeholder('Tidak ada catatan')
                                    ->color(Color::Slate),
                            ])
                            ->visible(fn ($record) => !empty($record->notes)),
                    ]),

                // Baris keempat - Metadata
                Grid::make(1)
                    ->schema([
                        Section::make('Metadata')
                            ->schema([
                                TextEntry::make('creator.name')
                                    ->label('DIBUAT OLEH')
                                    ->color(Color::Blue)
                                    ->weight(FontWeight::Medium),
                                TextEntry::make('reviewer.name')
                                    ->label('DISETUJUI/DITOLAK OLEH')
                                    ->color(Color::Blue)
                                    ->weight(FontWeight::Medium)
                                    ->visible(fn ($record) => $record->reviewed_by !== null)
                                    ->placeholder('Belum direview'),
                                TextEntry::make('updated_at')
                                    ->label('TERAKHIR DIUPDATE')
                                    ->dateTime('d/m/Y H:i:s')
                                    ->color(Color::Gray),
                            ])
                            ->columns(1)
                            ->columnSpan(1),
                    ]),
            ]);
    }
}