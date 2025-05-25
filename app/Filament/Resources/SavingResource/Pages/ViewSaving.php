<?php

namespace App\Filament\Resources\SavingResource\Pages;

use App\Filament\Resources\SavingResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Support\Colors\Color;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

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
                                        'pending' => 'warning',
                                        'closed' => 'gray',
                                        'blocked' => 'danger',
                                        'declined' => 'danger',
                                        default => 'gray',
                                    }),
                                
                                TextEntry::make('reviewer.name')
                                    ->label('REVIEWED BY')
                                    ->visible(fn ($record) => $record->reviewed_by !== null),
                                
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
                                    ->label('Created At')
                                    ->dateTime('d/m/Y H:i:s')
                                    ->color(Color::Gray),
                                    
                                TextEntry::make('creator.name')
                                    ->label('Created By')
                                    ->placeholder('N/A')
                                    ->color(Color::Blue),
                            ])
                            ->columnSpan(1),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [      
            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->status === 'pending' && auth()->user()->hasRole('kepala_cabang'))
                ->requiresConfirmation()
                ->modalHeading('Approve Saving')
                ->modalDescription('Are you sure you want to approve this saving? The status will be changed to active.')
                ->action(function () {
                    $this->record->status = 'active';
                    $this->record->reviewed_by = auth()->id();
                    $this->record->save();
                    
                    Notification::make()
                        ->title('Saving approved successfully')
                        ->success()
                        ->send();
                        
                    $this->redirect(SavingResource::getUrl('view', ['record' => $this->record]));
                }),
                
            Actions\Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->status === 'pending' && auth()->user()->hasRole('kepala_cabang'))
                ->form([
                    Textarea::make('rejected_reason')
                        ->label('Reason for Rejection')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->record->status = 'declined';
                    $this->record->reviewed_by = auth()->id();
                    $this->record->rejected_reason = $data['rejected_reason'];
                    $this->record->save();
                    
                    Notification::make()
                        ->title('Saving rejected')
                        ->success()
                        ->send();
                        
                    $this->redirect(SavingResource::getUrl('view', ['record' => $this->record]));
                }),
        ];
    }
}














