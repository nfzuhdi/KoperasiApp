<?php

namespace App\Filament\Resources\SavingPaymentResource\Pages;

use App\Filament\Resources\SavingPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Textarea;

class ViewSavingPayment extends ViewRecord
{
    protected static string $resource = SavingPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            
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
}
