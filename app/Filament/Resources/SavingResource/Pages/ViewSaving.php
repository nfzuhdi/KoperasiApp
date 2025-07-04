<?php

namespace App\Filament\Resources\SavingResource\Pages;

use App\Filament\Resources\SavingResource;
use App\Filament\Resources\SavingPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Support\Colors\Color;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use App\Models\SavingPayment;
use Filament\Forms\Components\TextInput;
use Filament\Forms;
use Filament\Forms\Components\Placeholder;
use Illuminate\Database\Eloquent\Model;
use App\Models\Saving;

class ViewSaving extends ViewRecord
{
    protected static string $resource = SavingResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Grid::make(3)
                    ->schema([
                        Section::make('Saving Information')
                            ->schema([
                                TextEntry::make('account_number')
                                    ->label('NOMOR REKENING'),
                                
                                TextEntry::make('member.full_name')
                                    ->label('NAMA ANGGOTA'),
                        
                                TextEntry::make('savingProduct.savings_product_name')
                                    ->label('PRODUK SIMPANAN'),
                                    
                                TextEntry::make('status')
                                    ->label('STATUS')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                                    ->color(fn (string $state): string => match ($state) {
                                        'active' => 'success',
                                        'closed' => 'gray',
                                        'blocked' => 'danger',
                                        default => 'gray',
                                    }),
                                
                                
                                
                                TextEntry::make('rejected_reason')
                                    ->label('ALASAN PENOLAKAN')
                                    ->visible(fn ($record) => $record->status === 'declined' && $record->rejected_reason !== null),
                            ])
                            ->columns(2)
                            ->columnSpan(2),
                            
                        Section::make('Balance')
                            ->schema([
                                TextEntry::make('balance')
                                    ->label('SALDO REKENING')
                                    ->money('IDR')
                                    ->color(Color::Emerald)
                                    ->weight('bold')
                                    ->size(TextEntry\TextEntrySize::Large),
                                    
                                TextEntry::make('next_due_date')
                                    ->label('JATUH TEMPO BERIKUTNYA')
                                    ->date('d/m/Y')
                                    ->color(fn ($record) => 
                                        $record->next_due_date && $record->next_due_date->isPast() 
                                            ? Color::Red 
                                            : Color::Green)
                                    ->visible(fn ($record) => 
                                        $record->savingProduct && 
                                        $record->savingProduct->is_mandatory_routine &&
                                        $record->next_due_date !== null)
                                    ->weight('medium'),
                            ])
                            ->columnSpan(1),
                    ]),
                    
                Grid::make(3)
                    ->schema([
                        Section::make('Product Details')
                            ->schema([
                                TextEntry::make('savingProduct.min_deposit')
                                    ->label('Min Deposit')
                                    ->money('IDR')
                                    ->color(Color::Emerald),
                                    
                                TextEntry::make('savingProduct.max_deposit')
                                    ->label('Max Deposit')
                                    ->money('IDR')
                                    ->placeholder('No limit')
                                    ->color(Color::Emerald),
                                    
                                TextEntry::make('savingProduct.admin_fee')
                                    ->label('Admin Fee')
                                    ->money('IDR')
                                    ->placeholder('No fee')
                                    ->color(Color::Rose),
                                    
                                TextEntry::make('savingProduct.is_withdrawable')
                                    ->label('Withdrawable')
                                    ->badge()
                                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No'),
                                    
                                TextEntry::make('savingProduct.is_mandatory_routine')
                                    ->label('Mandatory Routine')
                                    ->badge()
                                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No'),
                                    
                                TextEntry::make('savingProduct.deposit_period')
                                    ->label('Deposit Period')
                                    ->formatStateUsing(fn (?string $state): string => $state ? ucfirst($state) : 'N/A')
                                    ->placeholder('N/A')
                                    ->color(Color::Blue),
                            ])
                            ->columns(2)
                            ->columnSpan(2),
                            
                        Section::make('Metadata')
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Created At:')
                                    ->dateTime('d/m/Y H:i:s')
                                    ->color(Color::Gray),
                                    
                                TextEntry::make('creator.name')
                                    ->label('Created By:')
                                    ->placeholder('N/A')
                                    ->color(Color::Blue),
                                
                                TextEntry::make('reviewer.name')
                                    ->label('Reviewed By:')
                                    ->visible(fn ($record) => $record->reviewed_by !== null)
                                    ->color(Color::Blue),
                            ])
                            ->columnSpan(1),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approveSaving')
                ->label('Setuju')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Setujui Pembukaan Rekening')
                ->modalDescription('Pastikan semua dokumen dan persyaratan telah dipenuhi.')
                ->visible(fn () => $this->record->status === 'pending')
                ->action(function (): void {
                    try {
                        DB::beginTransaction();
                        
                        $this->record->update([
                            'status' => 'active',
                            'approved_at' => now(),
                            'approved_by' => auth()->id(),
                        ]);

                        DB::commit();

                        Notification::make()
                            ->success()
                            ->title('Rekening Disetujui')
                            ->body('Pembukaan rekening telah disetujui.')
                            ->send();

                    } catch (\Exception $e) {
                        DB::rollBack();
                        Notification::make()
                            ->danger()
                            ->title('Error')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            Actions\Action::make('rejectSaving')
                ->label('Tolak')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Tolak Pembukaan Rekening')
                ->form([
                    Textarea::make('rejection_reason')
                        ->label('Alasan Penolakan')
                        ->required()
                        ->maxLength(255),
                ])
                ->visible(fn () => $this->record->status === 'pending')
                ->action(function (array $data): void {
                    try {
                        DB::beginTransaction();
                        
                        $this->record->update([
                            'status' => 'declined',
                            'rejected_at' => now(),
                            'rejected_by' => auth()->id(),
                            'rejection_reason' => $data['rejection_reason'],
                        ]);

                        DB::commit();

                        Notification::make()
                            ->success()
                            ->title('Rekening Ditolak')
                            ->body('Pembukaan rekening telah ditolak.')
                            ->send();

                    } catch (\Exception $e) {
                        DB::rollBack();
                        Notification::make()
                            ->danger()
                            ->title('Error')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            Actions\Action::make('createPayment')
                ->label('Bayar Simpanan Anggota')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->button() // tampilkan teks + ikon (bukan hanya ikon)
                ->tooltip('Klik untuk mencatat pembayaran simpanan anggota')
                ->visible(fn () => $this->record->status === 'active')
                ->url(fn () => SavingPaymentResource::getUrl('create', ['saving_id' => $this->record->id])),

            Actions\Action::make('distributeProfitSharing')
                ->label('Distribusi Bagi Hasil')
                ->icon('heroicon-o-currency-dollar')
                ->modalHeading('Distribusi Bagi Hasil Mudharabah')
                ->color('success')
                ->visible(fn () => 
                    $this->record && 
                    $this->record->savingProduct && 
                    $this->record->status === 'active' && 
                    $this->record->savingProduct->savings_type === 'time_deposit'
                )
                ->form([
                        
                    Placeholder::make('balance_info')
                        ->label('Saldo Simpanan')
                        ->content(fn () => 'Rp ' . number_format($this->record->balance, 2)),

                    Placeholder::make('profit_sharing_ratio_info')
                        ->label('Nisbah Bagi Hasil Anggota')
                        ->content(function () {
                            $memberRatio = $this->record->savingProduct->member_ratio;
                            $coopRatio = 100 - $memberRatio;
                            
                            return sprintf(
                                '%d%% Anggota : %d%% Koperasi',
                                $memberRatio,
                                $coopRatio
                            );
                        }),

                    TextInput::make('profit')
                        ->label('Hasil Kelola Simpanan')
                        ->numeric()
                        ->required()
                        ->prefix('Rp')
                        ->live(),
                        
                ])
                ->action(function (array $data): void {
                    try {
                        DB::beginTransaction();
                        
                        $profit = $data['profit'];
                        $memberRatio = $this->record->savingProduct->member_ratio;
                        $profitShare = ($profit * $memberRatio) / 100;
                        
                        SavingPayment::create([
                            'saving_id' => $this->record->id,
                            'amount' => $profitShare,
                            'payment_type' => 'profit_sharing',
                            'status' => 'pending',
                            'payment_method' => 'system',
                            'description' => sprintf(
                                'Bagi hasil deposito %s bulan %s - Saldo: Rp %s (Nisbah: %s%%)', 
                                $this->record->savingProduct->savings_product_name,
                                now()->format('F Y'),
                                number_format($this->record->balance, 2),
                                $memberRatio
                            ),
                            'month' => now()->month,
                            'year' => now()->year,
                            'created_by' => auth()->id(),
                            'reference_number' => 'PS-' . now()->format('Ymd') . '-' . $this->record->id,
                        ]);

                        DB::commit();
                        
                        Notification::make()
                            ->success()
                            ->title('Bagi hasil telah didistribusikan dan menunggu persetujuan kepala cabang')
                            ->send();

                    } catch (\Exception $e) {
                        DB::rollBack();
                        Notification::make()->danger()->title('Error')->body($e->getMessage())->send();
                    }
                }),

            Actions\Action::make('withdrawAndClose')
                ->label(fn () => $this->record->status === 'active' ? 'Tutup Rekening' : 'Tutup Rekening')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Tutup Rekening')
                ->modalDescription(fn () => 
                    $this->record->balance > 0 
                        ? 'Total saldo yang akan ditarik: Rp ' . number_format($this->record->balance, 2) 
                        : 'Rekening akan ditutup.'
                )
                ->form([
                    Textarea::make('closing_reason')
                        ->label('Alasan Penutupan')
                        ->required()
                        ->maxLength(255),
                ])
                ->visible(fn () => 
                    $this->record->status === 'active' && 
                    $this->record->member && 
                    $this->record->member->member_status === 'terminated'
                )
                ->action(function (array $data): void {
                    try {
                        DB::beginTransaction();
                        
                        // Create withdrawal payment record if balance > 0
                        if ($this->record->balance > 0) {
                            SavingPayment::create([
                                'saving_id' => $this->record->id,
                                'amount' => $this->record->balance, // removed the minus sign
                                'payment_type' => 'withdrawal',
                                'status' => 'pending',
                                'payment_method' => 'System',
                                'description' => "Penarikan saldo untuk penutupan rekening.\nAlasan: " . $data['closing_reason'],
                                'month' => now()->month,
                                'year' => now()->year,
                                'created_by' => auth()->id(),
                                'reference_number' => 'WD-CLOSE-' . now()->format('Ymd') . '-' . $this->record->id,
                            ]);
                        }

                        // Update saving status
                        $this->record->update([
                            'status' => 'closed',
                            'closed_at' => now(),
                            'closed_by' => auth()->id(),
                            'closing_reason' => $data['closing_reason'],
                        ]);

                        DB::commit();
                        
                        $message = $this->record->balance > 0 
                            ? 'Rekening berhasil ditutup. Penarikan saldo sedang menunggu persetujuan kepala cabang'
                            : 'Rekening berhasil ditutup';

                        Notification::make()
                            ->success()
                            ->title('Rekening Ditutup')
                            ->body($message)
                            ->send();

                    } catch (\Exception $e) {
                        DB::rollBack();
                        Notification::make()
                            ->danger()
                            ->title('Error')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
        ];
    }
    
    // First, modify the class to eager load the savingProduct relationship
    public function getRecord(): Model 
    {
        $record = parent::getRecord();
        if ($record) {
            $record->load(['savingProduct', 'member']);
        }
        return $record;
    }
}














