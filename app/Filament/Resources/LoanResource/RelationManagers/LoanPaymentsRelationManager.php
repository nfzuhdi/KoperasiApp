<?php

namespace App\Filament\Resources\LoanResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Loan;
use App\Models\LoanPayment;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class LoanPaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'loanPayments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Hidden::make('loan_id')
                    ->default(fn ($livewire) => $livewire->ownerRecord->id),
                
                Section::make('Payment Details')
                    ->schema([
                        Select::make('payment_period')
                            ->label('Payment Period')
                            ->options(function ($get, $livewire) {
                                $loan = $livewire->ownerRecord;
                                if (!$loan) return [];

                                $tenor = (int) $loan->loanProduct->tenor_months;
                                $options = [];

                                // Only consider approved and pending payments as "paid periods"
                                $paidPeriods = LoanPayment::where('loan_id', $loan->id)
                                    ->whereIn('status', ['approved', 'pending'])
                                    ->pluck('payment_period')
                                    ->toArray();

                                for ($i = 1; $i <= $tenor; $i++) {
                                    if (!in_array((string)$i, $paidPeriods)) {
                                        $options[(string)$i] = "Period $i";
                                    }
                                }

                                $hasPrincipalReturn = LoanPayment::where('loan_id', $loan->id)
                                    ->whereIn('status', ['approved', 'pending'])
                                    ->where('is_principal_return', true)
                                    ->exists();

                                if (!$hasPrincipalReturn && $loan->payment_status !== 'paid') {
                                    $options['principal_return'] = 'Principal Return';
                                }

                                return $options;
                            })
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (callable $set, callable $get, $state, $livewire) {
                                if ($state && $livewire->ownerRecord) {
                                    $loan = $livewire->ownerRecord;
                                    if ($loan) {
                                        $periodNumber = is_numeric($state) ? (int) $state : 0;
                                        $tenor = (int) $loan->loanProduct->tenor_months;
                                        
                                        $isPrincipalReturn = ($periodNumber > $tenor) || 
                                                            (is_string($state) && str_contains(strtolower($state), 'principal return'));
                                        $set('is_principal_return', $isPrincipalReturn);
                                        
                                        $dueDate = $loan->disbursed_at
                                            ? Carbon::parse($loan->disbursed_at)->addMonths($periodNumber)->format('Y-m-d')
                                            : now()->addMonths($periodNumber)->format('Y-m-d');
                                        $set('due_date', $dueDate);
                                        
                                        if ($isPrincipalReturn) {
                                            $amount = $loan->loan_amount;
                                            if ($loan->loanProduct->contract_type === 'Murabahah') {
                                                $amount = $loan->purchase_price;
                                            }
                                            $set('amount', $amount);
                                            $set('amount_display', $amount);
                                        } else {
                                            $rate = $loan->margin_amount / 100;
                                            $amount = 0;
                                            
                                            if ($loan->loanProduct->contract_type === 'Murabahah') {
                                                $amount = ($loan->selling_price - $loan->purchase_price) / $tenor;
                                            } else if ($loan->loanProduct->contract_type === 'Mudharabah' || 
                                                      $loan->loanProduct->contract_type === 'Musyarakah') {
                                                $amount = $loan->loan_amount * $rate / $tenor;
                                            } else {
                                                $amount = $loan->loan_amount * $rate / $tenor;
                                            }
                                            
                                            $amount = round($amount, 2);
                                            $set('amount', $amount);
                                            $set('amount_display', $amount);
                                            
                                            if ($loan->loanProduct->contract_type === 'Mudharabah' || 
                                                $loan->loanProduct->contract_type === 'Musyarakah') {
                                                $set('koperasi_profit', $amount);
                                            }
                                        }
                                        
                                        $isLate = now()->isAfter(Carbon::parse($dueDate));
                                        $set('is_late', $isLate);
                                        
                                        if ($isLate && $loan->loanProduct->late_payment_fine_percentage > 0) {
                                            $finePercentage = $loan->loanProduct->late_payment_fine_percentage;
                                            $fineAmount = $amount * ($finePercentage / 100);
                                            $set('fine', round($fineAmount, 2));
                                        } else {
                                            $set('fine', 0);
                                        }
                                    }
                                }
                            }),
                        
                        TextInput::make('due_date')
                            ->label('Due Date')
                            ->type('date')
                            ->required()
                            ->disabled(),
                        
                        Hidden::make('koperasi_profit'),
                        Hidden::make('amount'),
                        Hidden::make('is_principal_return')
                            ->default(false),
                        Hidden::make('is_late')
                            ->default(false),
                        
                        TextInput::make('amount_display')
                            ->label(fn ($get, $livewire) => 
                                $livewire->ownerRecord && 
                                ($livewire->ownerRecord->loanProduct->contract_type === 'Mudharabah' || 
                                 $livewire->ownerRecord->loanProduct->contract_type === 'Musyarakah') &&
                                !$get('is_principal_return') ? 'Payment Amount (Keuntungan Koperasi)' : 'Payment Amount')
                            ->prefix('Rp')
                            ->disabled()
                            ->numeric()
                            ->default(0),
                        
                        TextInput::make('fine')
                            ->label('Late Payment Fine')
                            ->prefix('Rp')
                            ->numeric()
                            ->default(0)
                            ->disabled(),
                        
                        Select::make('payment_method')
                            ->label('Payment Method')
                            ->options([
                                'cash' => 'Cash',
                                'transfer' => 'Bank Transfer',
                                'deduction' => 'Salary Deduction',
                            ])
                            ->required(),
                    ])
                    ->columns(2),
                
                Section::make('Additional Information')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Additional Notes')
                            ->placeholder('Additional notes or comments')
                            ->rows(3),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference_number')
            ->columns([
                TextColumn::make('reference_number')
                    ->label('Reference Number')
                    ->searchable(),
                
                TextColumn::make('payment_period')
                    ->label('Period')
                    ->formatStateUsing(fn (string $state, $record) => 
                        $record->is_principal_return ? 'Principal Return' : "Period $state"),
                
                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('IDR'),
                
                TextColumn::make('fine')
                    ->label('Fine')
                    ->money('IDR'),
                
                TextColumn::make('created_at')
                    ->label('Payment Date')
                    ->dateTime('d/m/Y H:i'),
                
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data) {
                        $data['reference_number'] = 'LP-' . now()->format('YmdHis') . '-' . rand(1000, 9999);
                        return $data;
                    })
                    ->using(function (array $data, $livewire) {
                        // Check if there's a rejected payment for this period
                        $existingRejected = LoanPayment::where('loan_id', $livewire->ownerRecord->id)
                            ->where('payment_period', $data['payment_period'])
                            ->where('status', 'rejected')
                            ->first();
                        
                        if ($existingRejected) {
                            // Delete the rejected payment first
                            $existingRejected->delete();
                        }
                        
                        // Create the new payment
                        return LoanPayment::create($data);
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Action::make('approve')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'pending' && auth()->user()->hasRole('kepala_cabang'))
                    ->requiresConfirmation()
                    ->action(function ($record, $livewire) {
                        try {
                            DB::beginTransaction();
                            
                            $record->status = 'approved';
                            $record->reviewed_by = auth()->id();
                            $record->save();
                            
                            $loan = Loan::find($record->loan_id);
                            
                            if ($loan->loanProduct->contract_type === 'Mudharabah') {
                                $record->processJournalMudharabah($loan);
                            } else if ($loan->loanProduct->contract_type === 'Murabahah') {
                                $record->processJournalMurabahah($loan);
                            } else if ($loan->loanProduct->contract_type === 'Musyarakah') {
                                $record->processJournalMusyarakah($loan);
                            } else {
                                $record->processDefaultJournal($loan);
                            }
                            
                            if ($record->fine > 0) {
                                $record->processFineJournalFixed($loan);
                            }
                            
                            $this->updateLoanPaymentStatus($loan);
                            
                            DB::commit();
                            
                            Notification::make()
                                ->title('Payment approved successfully')
                                ->success()
                                ->send();
                                
                        } catch (\Exception $e) {
                            DB::rollBack();
                            
                            Log::error('Error approving payment: ' . $e->getMessage(), [
                                'payment_id' => $record->id,
                                'exception' => $e
                            ]);
                            
                            Notification::make()
                                ->title('Error approving payment')
                                ->body('An error occurred: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('reject')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === 'pending' && auth()->user()->hasRole('kepala_cabang'))
                    ->form([
                        Textarea::make('rejection_notes')
                            ->label('Reason for Rejection')
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->status = 'rejected';
                        $record->reviewed_by = auth()->id();
                        $record->notes = ($record->notes ? $record->notes . "\n\n" : '') . "Rejected: " . $data['rejection_notes'];
                        $record->save();
                        
                        Notification::make()
                            ->title('Payment rejected')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }
    
    private function updateLoanPaymentStatus($loan)
    {
        $tenor = (int) $loan->loanProduct->tenor_months;
        $totalExpectedPayments = $tenor + 1;
        $approvedPayments = LoanPayment::where('loan_id', $loan->id)
            ->where('status', 'approved')
            ->count();
        
        $principalReturnPaid = LoanPayment::where('loan_id', $loan->id)
            ->where('status', 'approved')
            ->where('is_principal_return', true)
            ->exists();
        
        if ($approvedPayments === 0) {
            $loan->payment_status = 'not_paid';
        } else if ($approvedPayments < $totalExpectedPayments) {
            $loan->payment_status = 'on_going';
        } else if ($principalReturnPaid) {
            $loan->payment_status = 'paid';
            $loan->paid_off_at = now();
            
            if (Schema::hasColumn('loans', 'completed_at')) {
                $loan->completed_at = now();
            }
        }
        
        $loan->save();
    }
}