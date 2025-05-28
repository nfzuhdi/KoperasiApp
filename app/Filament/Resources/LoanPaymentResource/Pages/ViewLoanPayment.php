<?php

namespace App\Filament\Resources\LoanPaymentResource\Pages;

use App\Filament\Resources\LoanPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\DB;
use App\Models\Loan;

class ViewLoanPayment extends ViewRecord
{
    protected static string $resource = LoanPaymentResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Grid::make(3)
                    ->schema([
                        Section::make('Payment Information')
                            ->schema([
                                TextEntry::make('reference_number')
                                    ->label('REFERENCE NUMBER'),
                                
                                TextEntry::make('loan.account_number')
                                    ->label('LOAN ACCOUNT'),
                                
                                TextEntry::make('loan.member.full_name')
                                    ->label('MEMBER NAME'),
                                
                                TextEntry::make('payment_period')
                                    ->label('PAYMENT PERIOD'),
                                
                                TextEntry::make('created_at')
                                    ->label('PAYMENT DATE')
                                    ->dateTime(),
                                
                                TextEntry::make('status')
                                    ->label('STATUS')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                                    ->color(fn (string $state): string => match ($state) {
                                        'approved' => 'success',
                                        'pending' => 'warning',
                                        'rejected' => 'danger',
                                        default => 'gray',
                                    }),
                            ])
                            ->columns(2)
                            ->columnSpan(2),
                            
                        Section::make('Amount Details')
                            ->schema([
                                TextEntry::make('amount')
                                    ->label('PAYMENT AMOUNT')
                                    ->money('IDR')
                                    ->color(Color::Emerald)
                                    ->weight('bold')
                                    ->size(TextEntry\TextEntrySize::Large),
                                
                                TextEntry::make('fine')
                                    ->label('LATE PAYMENT FINE')
                                    ->money('IDR')
                                    ->visible(fn ($record) => $record->fine > 0),
                                
                                TextEntry::make('payment_method')
                                    ->label('PAYMENT METHOD')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                                    ->color(fn (string $state): string => match ($state) {
                                        'cash' => 'success',
                                        'transfer' => 'info',
                                        default => 'gray',
                                    }),
                            ])
                            ->columnSpan(1),
                    ]),
                    
                Section::make('Additional Information')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('NOTES')
                            ->columnSpanFull(),
                            
                        TextEntry::make('reviewedBy.name')
                            ->label('REVIEWED BY')
                            ->visible(fn ($record) => $record->reviewed_by !== null),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record->notes || $record->reviewed_by),
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
                ->modalHeading('Approve Payment')
                ->modalDescription('Are you sure you want to approve this payment?')
                ->action(function () {
                    try {
                        DB::beginTransaction();

                        $this->record->status = 'approved';
                        $this->record->reviewed_by = auth()->id();
                        $this->record->save();

                        $loan = Loan::find($this->record->loan_id);
                        if (!$loan) {
                            throw new \Exception("Loan not found with ID: {$this->record->loan_id}");
                        }
                        
                        if ($loan->loanProduct->contract_type === 'Mudharabah') {
                            $this->record->processJournalMudharabah($loan);
                        } else if ($loan->loanProduct->contract_type === 'Murabahah') {
                            $this->record->processJournalMurabahah($loan);
                        } else if ($loan->loanProduct->contract_type === 'Musyarakah') {
                            $this->record->processJournalMusyarakah($loan);
                        } else {
                            LoanPaymentResource::processJournalEntries($this->record);
                        }
                        
                        if ($this->record->fine > 0){
                            $this->record->processFineJournalFixed($loan);
                        }

                        LoanPaymentResource::updateLoanPaymentStatus($loan);

                        DB::commit();

                        Notification::make()
                            ->title('Payment approved successfully')
                            ->success()
                            ->send();
                            
                        $this->redirect(LoanPaymentResource::getUrl('view', ['record' => $this->record]));
                    } catch (\Exception $e) {
                        DB::rollBack();

                        Notification::make()
                            ->title('Error approving payment')
                            ->body('An error occurred: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
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
                        
                    $this->redirect(LoanPaymentResource::getUrl('view', ['record' => $this->record]));
                }),
        ];
    }
}

