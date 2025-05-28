<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SavingPaymentResource\Pages;
use App\Models\SavingPayment;
use App\Models\Saving;
use App\Models\User;
use App\Models\JournalAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class SavingPaymentResource extends Resource
{
    protected static ?string $model = SavingPayment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Payments';

    protected static ?string $navigationLabel = 'Saving Payments';

    protected static ?string $modelLabel = 'Saving Payment';

    protected static ?string $pluralModelLabel = 'Posting Simpanan Nasabah';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Saving Account')
                    ->schema([
                        Forms\Components\Select::make('saving_id')
                            ->label('Saving Account')
                            ->relationship('savingAccount', 'account_number')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->account_number} - {$record->member->full_name}")
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('product_preview_visible', true)),
                    ])
                    ->columnSpanFull(),
                    
                // Product Details Preview Section
                Section::make('Product Details')
                    ->schema([
                        Forms\Components\Placeholder::make('product_name')
                            ->label('Product Name')
                            ->content(function ($get) {
                                $saving = Saving::find($get('saving_id'));
                                return $saving ? $saving->savingProduct->savings_product_name : '-';
                            }),
                        Forms\Components\Placeholder::make('savings_type')
                            ->label('Savings Type')
                            ->content(function ($get) {
                                $saving = Saving::find($get('saving_id'));
                                return $saving ? ucfirst($saving->savingProduct->savings_type) : '-';
                            }),
                        Forms\Components\Placeholder::make('contract_type')
                            ->label('Contract Type')
                            ->content(function ($get) {
                                $saving = Saving::find($get('saving_id'));
                                return $saving && $saving->savingProduct->contract_type ? $saving->savingProduct->contract_type : '-';
                            }),
                        Forms\Components\Placeholder::make('current_balance')
                            ->label('Current Balance')
                            ->content(function ($get) {
                                $saving = Saving::find($get('saving_id'));
                                return $saving ? 'Rp ' . number_format($saving->balance, 2) : '-';
                            }),
                        Forms\Components\Placeholder::make('min_deposit')
                            ->label('Minimum Deposit')
                            ->content(function ($get) {
                                $saving = Saving::find($get('saving_id'));
                                return $saving ? 'Rp ' . number_format($saving->savingProduct->min_deposit, 2) : '-';
                            }),
                        Forms\Components\Placeholder::make('max_deposit')
                            ->label('Maximum Deposit')
                            ->content(function ($get) {
                                $saving = Saving::find($get('saving_id'));
                                if (!$saving) return '-';
                                return $saving->savingProduct->max_deposit 
                                    ? 'Rp ' . number_format($saving->savingProduct->max_deposit, 2) 
                                    : 'No limit';
                            }),
                        Forms\Components\Placeholder::make('next_due_date')
                            ->label('Next Due Date')
                            ->content(function ($get) {
                                $saving = Saving::find($get('saving_id'));
                                if (!$saving || !$saving->savingProduct?->is_mandatory_routine) {
                                    return '-';
                                }

                                // Get base date from next_due_date or use current date for first payment
                                $baseDate = $saving->next_due_date ? 
                                    Carbon::parse($saving->next_due_date) : 
                                    now();

                                // Preserve the day of month when calculating next due date
                                $nextDueDate = match ($saving->savingProduct->deposit_period) {
                                    'weekly' => $baseDate->copy()->addWeek(),
                                    'monthly' => $baseDate->copy()->addMonthNoOverflow(), // This preserves the day
                                    'yearly' => $baseDate->copy()->addYearNoOverflow(),   // This preserves the day
                                    default => null
                                };

                                return $nextDueDate ? $nextDueDate->format('d F Y') : '-';
                            })
                            ->visible(function ($get) {
                                $saving = Saving::find($get('saving_id'));
                                return $saving && $saving->savingProduct?->is_mandatory_routine;
                            }),
                        Forms\Components\Placeholder::make('payment_status')
                            ->label('Payment Status')
                            ->content(function ($get) {
                                $saving = Saving::find($get('saving_id'));
                                
                                // Return early if no saving or no next_due_date
                                if (!$saving || !$saving->next_due_date || !$saving->savingProduct?->is_mandatory_routine) {
                                    return '-';
                                }

                                $nextDueDate = Carbon::parse($saving->next_due_date);
                                $today = now();

                                if ($today->lte($nextDueDate)) {
                                    return "✓ Payment is on track\nDue date: " . $nextDueDate->format('d F Y');
                                } else {
                                    // Use diffInDays() without decimals
                                    $daysLate = $today->startOfDay()->diffInDays($nextDueDate->startOfDay());
                                    return "⚠ Payment is {$daysLate} days late\nDue date: " . $nextDueDate->format('d F Y');
                                }
                            })
                            ->visible(function ($get) {
                                $saving = Saving::find($get('saving_id'));
                                return $saving && $saving->next_due_date && $saving->savingProduct?->is_mandatory_routine;
                            }),
                    ])
                    ->visible(fn ($get) => $get('product_preview_visible'))
                    ->columns(2)
                    ->columnSpanFull(),
                    
                Section::make('Payment Details')
                    ->schema([
                        Forms\Components\Select::make('payment_type')
                            ->options([
                                'deposit' => 'Deposit Money',
                                'withdrawal' => 'Withdraw Money'
                            ])
                            ->default('deposit')
                            ->required()
                            ->live()
                            ->visible(function ($get) {
                                $saving = Saving::find($get('saving_id'));
                                return $saving && $saving->savingProduct?->is_withdrawable;
                            }),

                        Forms\Components\TextInput::make('amount')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->minValue(0)
                            ->step(0.01)
                            ->label(fn ($get) => $get('payment_type') === 'withdrawal' ? 'Withdrawal Amount' : 'Deposit Amount')
                            ->rules([
                                function (callable $get) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get) {
                                        $savingId = $get('saving_id');
                                        if (!$savingId) return;
                                        
                                        $saving = Saving::find($savingId);
                                        if (!$saving) return;
                                        
                                        $product = $saving->savingProduct;
                                        
                                        if ($get('payment_type') === 'withdrawal') {
                                            // Validate withdrawal amount
                                            if ($value > $saving->balance) {
                                                $fail("Withdrawal amount cannot exceed current balance of Rp " . number_format($saving->balance, 2));
                                            }
                                        } else {
                                            // Validate deposit amount
                                            if ($value < $product->min_deposit) {
                                                $fail("Deposit amount must be at least Rp " . number_format($product->min_deposit, 2));
                                            }
                                            if ($product->max_deposit && $value > $product->max_deposit) {
                                                $fail("Deposit amount cannot exceed Rp " . number_format($product->max_deposit, 2));
                                            }
                                        }
                                    };
                                }
                            ]),
                        Forms\Components\TextInput::make('fine')
                            ->label('Late Payment Fine')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->minValue(0)
                            ->step(0.01)
                            ->visible(function ($get) {
                                $savingId = $get('saving_id');
                                if (!$savingId) return false;
                                
                                $saving = Saving::find($savingId);
                                if (!$saving || !$saving->next_due_date || !$saving->savingProduct?->is_mandatory_routine) {
                                    return false;
                                }

                                $nextDueDate = Carbon::parse($saving->next_due_date);
                                $today = now();

                                // Only show fine input if payment is late
                                return $today->gt($nextDueDate);
                            }),
                        Forms\Components\Select::make('payment_method')
                            ->options([
                                'cash' => 'Cash',
                                'transfer' => 'Bank Transfer',
                                'debit_card' => 'Debit Card',
                                'credit_card' => 'Credit Card',
                                'e_wallet' => 'E-Wallet',
                                'other' => 'Other',
                            ])
                            ->placeholder('Select payment method'),
                    ])
                    ->columns(2),

                Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->placeholder('Additional notes or comments')
                            ->rows(3),
                        Forms\Components\Hidden::make('status')
                            ->default('pending'),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('savingAccount.account_number')
                    ->label('Account Number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('savingAccount.member.full_name')
                    ->label('Member')
                    ->searchable()
                    ->sortable(),
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
                Tables\Columns\TextColumn::make('reference_number')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('reviewedBy.name')
                    ->label('Reviewed By')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
                Tables\Filters\SelectFilter::make('payment_method')
                    ->options([
                        'cash' => 'Cash',
                        'transfer' => 'Bank Transfer',
                        'debit_card' => 'Debit Card',
                        'credit_card' => 'Credit Card',
                        'e_wallet' => 'E-Wallet',
                        'other' => 'Other',
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->icon('heroicon-m-eye')
                    ->iconButton(),
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-m-pencil-square')
                    ->iconButton(),
                Action::make('approve')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->iconButton()
                    ->visible(fn (SavingPayment $record) => $record->status === 'pending' && auth()->user()->hasRole('kepala_cabang'))
                    ->requiresConfirmation()
                    ->modalHeading('Approve Payment')
                    ->modalDescription('Are you sure you want to approve this payment?')
                    ->action(function (SavingPayment $record) {
                        try {
                            // Mulai transaksi database
                            DB::beginTransaction();
                            
                            // Debug log untuk melihat data awal
                            Log::info('Starting payment approval process', [
                                'payment_id' => $record->id,
                                'saving_id' => $record->saving_id,
                                'amount' => $record->amount
                            ]);
                            
                            // 1. Update status pembayaran
                            $record->status = 'approved';
                            $record->reviewed_by = auth()->id();
                            $record->save();
                            
                            // 2. Update saldo rekening simpanan
                            $saving = Saving::find($record->saving_id);
                            if (!$saving) {
                                throw new \Exception("Saving account not found with ID: {$record->saving_id}");
                            }
                            
                            Log::info('Found saving account', [
                                'saving_id' => $saving->id,
                                'current_balance' => $saving->balance
                            ]);
                            
                            $saving->balance += $record->payment_type === 'withdrawal' ? -$record->amount : $record->amount;
                            $saving->save();
                            
                            Log::info('Updated saving balance', [
                                'saving_id' => $saving->id,
                                'new_balance' => $saving->balance
                            ]);
                            
                            // 3. Proses jurnal akuntansi
                            $savingProduct = $saving->savingProduct;
                            if (!$savingProduct) {
                                throw new \Exception("Saving product not found for saving ID: {$saving->id}");
                            }
                            
                            Log::info('Found saving product', [
                                'product_id' => $savingProduct->id,
                                'product_name' => $savingProduct->savings_product_name
                            ]);
                            
                            // 3.1 Proses jurnal untuk setoran/penarikan
                            if ($record->payment_type === 'withdrawal') {
                                // Untuk penarikan:

                                // Akun debit (Simpanan - posisi normal kredit)
                                $debitAccount = JournalAccount::find($savingProduct->journal_account_withdrawal_debit_id);
                                if (!$debitAccount) {
                                    throw new \Exception("Withdrawal debit journal account not found");
                                }
                                
                                // Akun kredit (Kas - posisi normal debit)
                                $creditAccount = JournalAccount::find($savingProduct->journal_account_withdrawal_credit_id);
                                if (!$creditAccount) {
                                    throw new \Exception("Withdrawal credit journal account not found");
                                }

                                // Update saldo akun debit (Simpanan - posisi normal kredit)
                                $oldBalance = $debitAccount->balance;
                                if ($debitAccount->account_position === 'debit') {
                                    $debitAccount->balance += $record->amount; // Berkurang di debit
                                } else {
                                    $debitAccount->balance -= $record->amount; // Bertambah di kredit
                                }
                                $debitAccount->save();

                                // Update saldo akun kredit (Kas - posisi normal debit)
                                $oldBalance = $creditAccount->balance;
                                if ($creditAccount->account_position === 'credit') {
                                    $creditAccount->balance += $record->amount; // Bertambah di kredit
                                } else {
                                    $creditAccount->balance -= $record->amount; // Berkurang di debit
                                }
                                $creditAccount->save();
                            } else {
                                // Proses jurnal untuk setoran (kode yang sudah ada)
                                if (!$savingProduct->journal_account_deposit_debit_id || 
                                    !$savingProduct->journal_account_deposit_credit_id) {
                                    throw new \Exception("Deposit journal accounts not configured");
                                }
                                
                                // Akun debit (biasanya Kas)
                                $debitAccount = JournalAccount::find($savingProduct->journal_account_deposit_debit_id);
                                if (!$debitAccount) {
                                    throw new \Exception("Debit journal account not found with ID: {$savingProduct->journal_account_deposit_debit_id}");
                                }
                                
                                Log::info('Found debit account', [
                                    'account_id' => $debitAccount->id,
                                    'account_name' => $debitAccount->account_name,
                                    'current_balance' => $debitAccount->balance
                                ]);
                                
                                // Debit kas (bertambah jika posisi normal debit, berkurang jika kredit)
                                $oldBalance = $debitAccount->balance;
                                if ($debitAccount->account_position === 'debit') {
                                    $debitAccount->balance += $record->amount;
                                } else {
                                    $debitAccount->balance -= $record->amount;
                                }
                                $debitAccount->save();
                                
                                Log::info('Updated debit account', [
                                    'account_id' => $debitAccount->id,
                                    'old_balance' => $oldBalance,
                                    'new_balance' => $debitAccount->balance
                                ]);
                                
                                // Akun kredit (biasanya Simpanan)
                                $creditAccount = JournalAccount::find($savingProduct->journal_account_deposit_credit_id);
                                if (!$creditAccount) {
                                    throw new \Exception("Credit journal account not found with ID: {$savingProduct->journal_account_deposit_credit_id}");
                                }
                                
                                Log::info('Found credit account', [
                                    'account_id' => $creditAccount->id,
                                    'account_name' => $creditAccount->account_name,
                                    'current_balance' => $creditAccount->balance
                                ]);
                                
                                // Kredit simpanan (bertambah jika posisi normal kredit, berkurang jika debit)
                                $oldBalance = $creditAccount->balance;
                                if ($creditAccount->account_position === 'credit') {
                                    $creditAccount->balance += $record->amount;
                                } else {
                                    $creditAccount->balance -= $record->amount;
                                }
                                $creditAccount->save();
                                
                                Log::info('Updated credit account', [
                                    'account_id' => $creditAccount->id,
                                    'old_balance' => $oldBalance,
                                    'new_balance' => $creditAccount->balance
                                ]);
                            }
                            
                            // 3.2 Proses jurnal untuk denda keterlambatan (jika ada)
                            if (isset($record->fine) && $record->fine > 0 && 
                                $savingProduct->journal_account_penalty_debit_id && 
                                $savingProduct->journal_account_penalty_credit_id) {
                                
                                // Process late payment fine journal entries
                                Log::info('Processing late payment fine journal entries', [
                                    'fine_amount' => $record->fine
                                ]);
                                
                                // Get the debit account for penalty
                                $penaltyDebitAccount = JournalAccount::find($savingProduct->journal_account_penalty_debit_id);
                                if (!$penaltyDebitAccount) {
                                    throw new \Exception("Penalty debit journal account not found with ID: {$savingProduct->journal_account_penalty_debit_id}");
                                }
                                
                                // Get the credit account for penalty
                                $penaltyCreditAccount = JournalAccount::find($savingProduct->journal_account_penalty_credit_id);
                                if (!$penaltyCreditAccount) {
                                    throw new \Exception("Penalty credit journal account not found with ID: {$savingProduct->journal_account_penalty_credit_id}");
                                }
                                
                                // Update debit account balance
                                $oldBalance = $penaltyDebitAccount->balance;
                                if ($penaltyDebitAccount->account_position === 'debit') {
                                    $penaltyDebitAccount->balance += $record->fine;
                                } else {
                                    $penaltyDebitAccount->balance -= $record->fine;
                                }
                                $penaltyDebitAccount->save();
                                
                                Log::info('Updated penalty debit account', [
                                    'account_id' => $penaltyDebitAccount->id,
                                    'old_balance' => $oldBalance,
                                    'new_balance' => $penaltyDebitAccount->balance
                                ]);
                                
                                // Update credit account balance
                                $oldBalance = $penaltyCreditAccount->balance;
                                if ($penaltyCreditAccount->account_position === 'credit') {
                                    $penaltyCreditAccount->balance += $record->fine;
                                } else {
                                    $penaltyCreditAccount->balance -= $record->fine;
                                }
                                $penaltyCreditAccount->save();
                                
                                Log::info('Updated penalty credit account', [
                                    'account_id' => $penaltyCreditAccount->id,
                                    'old_balance' => $oldBalance,
                                    'new_balance' => $penaltyCreditAccount->balance
                                ]);
                            }
                            
                            // Commit transaksi jika semua berhasil
                            DB::commit();
                            
                            Log::info('Payment approval completed successfully', [
                                'payment_id' => $record->id
                            ]);
                            
                            Notification::make()
                                ->title('Payment approved successfully')
                                ->success()
                                ->send();
                                
                        } catch (\Exception $e) {
                            // Rollback transaksi jika terjadi kesalahan
                            DB::rollBack();
                            
                            Log::error('Error approving payment: ' . $e->getMessage(), [
                                'payment_id' => $record->id,
                                'exception' => $e,
                                'trace' => $e->getTraceAsString()
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
                    ->iconButton()
                    ->visible(fn (SavingPayment $record) => $record->status === 'pending' && auth()->user()->hasRole('kepala_cabang'))
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('rejection_notes')
                            ->label('Reason for Rejection')
                            ->required(),
                    ])
                    ->action(function (SavingPayment $record, array $data) {
                        $record->status = 'rejected';
                        $record->reviewed_by = auth()->id();
                        $record->notes = ($record->notes ? $record->notes . "\n\n" : '') . "Rejected: " . $data['rejection_notes'];
                        $record->save();
                        
                        Notification::make()
                            ->title('Payment rejected')
                            ->success()
                            ->send();
                    }),
            ], position: ActionsPosition::BeforeColumns)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSavingPayments::route('/'),
            'create' => Pages\CreateSavingPayment::route('/create'),
            'view' => Pages\ViewSavingPayment::route('/{record}'),
            'edit' => Pages\EditSavingPayment::route('/{record}/edit'),
        ];
    }
}




