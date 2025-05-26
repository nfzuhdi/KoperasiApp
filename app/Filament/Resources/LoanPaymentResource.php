<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoanPaymentResource\Pages;
use App\Models\LoanPayment;
use App\Models\Loan;
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

class LoanPaymentResource extends Resource
{
    protected static ?string $model = LoanPayment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Payments';

    protected static ?string $navigationLabel = 'Loan Payments';

    protected static ?string $modelLabel = 'Loan Payment';

    protected static ?string $pluralModelLabel = 'Posting Pembayaran Pinjaman';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Loan Account')
                    ->schema([
                        Forms\Components\Select::make('loan_id')
                            ->label('Loan Account')
                            ->relationship('loan', 'account_number')
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
                                $loan = Loan::with('loanProduct')->find($get('loan_id'));
                                return $loan && $loan->loanProduct ? $loan->loanProduct->name : '-';
                            }),
                        Forms\Components\Placeholder::make('contract_type')
                            ->label('Contract Type')
                            ->content(function ($get) {
                                $loan = Loan::with('loanProduct')->find($get('loan_id'));
                                return $loan && $loan->loanProduct ? $loan->loanProduct->contract_type : '-';
                            }),
                        Forms\Components\Placeholder::make('loan_amount')
                            ->label('Loan Amount')
                            ->content(function ($get) {
                                $loan = Loan::with('loanProduct')->find($get('loan_id'));
                                if (!$loan) return '-';

                                // For Murabahah, show selling price (harga jual)
                                if ($loan->loanProduct && $loan->loanProduct->contract_type === 'Murabahah') {
                                    $sellingPrice = $loan->selling_price;

                                    // If selling price is not set, calculate it from purchase price and margin
                                    if (!$sellingPrice && $loan->purchase_price && $loan->margin_amount) {
                                        $marginAmount = $loan->purchase_price * ($loan->margin_amount / 100);
                                        $sellingPrice = $loan->purchase_price + $marginAmount;
                                    }

                                    return $sellingPrice ? 'Rp ' . number_format($sellingPrice, 2) : 'Rp 0.00';
                                }

                                // For other contract types, show loan amount
                                return $loan->loan_amount ? 'Rp ' . number_format($loan->loan_amount, 2) : 'Rp 0.00';
                            }),
                        Forms\Components\Placeholder::make('margin_amount')
                            ->label('Margin/Rate (%)')
                            ->content(function ($get) {
                                $loan = Loan::with('loanProduct')->find($get('loan_id'));
                                return $loan ? $loan->margin_amount . '%' : '-';
                            }),
                        Forms\Components\Placeholder::make('tenor_months')
                            ->label('Tenor (Months)')
                            ->content(function ($get) {
                                $loan = Loan::with('loanProduct')->find($get('loan_id'));
                                return $loan && $loan->loanProduct ? $loan->loanProduct->tenor_months . ' months' : '-';
                            }),
                        Forms\Components\Placeholder::make('payment_status')
                            ->label('Payment Status')
                            ->content(function ($get) {
                                $loan = Loan::with('loanProduct')->find($get('loan_id'));
                                return $loan ? ucfirst(str_replace('_', ' ', $loan->payment_status)) : '-';
                            }),
                    ])
                    ->visible(fn ($get) => $get('product_preview_visible'))
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Payment Details')
                    ->schema([
                        Forms\Components\Select::make('payment_period')
                            ->label('Payment Period')
                            ->options(function ($get) {
                                $loan = Loan::find($get('loan_id'));
                                if (!$loan) return [];

                                $tenor = (int) $loan->loanProduct->tenor_months;
                                $options = [];

                                // Get already paid or pending periods
                                $paidPeriods = LoanPayment::where('loan_id', $loan->id)
                                    ->whereIn('status', ['approved', 'pending'])
                                    ->pluck('payment_period')
                                    ->toArray();

                                // Only show periods that haven't been paid yet
                                for ($i = 1; $i <= $tenor; $i++) {
                                    if (!in_array($i, $paidPeriods)) {
                                        $options[$i] = "Period $i";
                                    }
                                }
                                return $options;
                            })
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (callable $set, callable $get, $state) {
                                if ($state && $get('loan_id')) {
                                    $loanId = $get('loan_id');
                                    $loan = Loan::with(['loanProduct'])->find($loanId);
                                    if ($loan && $loan instanceof Loan) {
                                        // Calculate due date (1 month after disbursement + period months)
                                        $periodNumber = (int) $state;
                                        $dueDate = $loan->disbursed_at
                                            ? $loan->disbursed_at->copy()->addMonths($periodNumber)->format('Y-m-d')
                                            : now()->addMonths($periodNumber)->format('Y-m-d');

                                        $set('due_date', $dueDate);
                                        $set('due_date_display', $dueDate);

                                        // Calculate payment amount based on contract type
                                        $amount = self::calculatePaymentAmount($loan, $periodNumber);
                                        $set('amount', $amount);
                                        $set('amount_display', $amount);
                                    }
                                }
                            }),
                        Forms\Components\Hidden::make('due_date'),
                        Forms\Components\TextInput::make('due_date_display')
                            ->label('Due Date')
                            ->disabled()
                            ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y') : '-'),
                        Forms\Components\Hidden::make('amount'),
                        Forms\Components\TextInput::make('amount_display')
                            ->label('Payment Amount')
                            ->prefix('Rp')
                            ->disabled()
                            ->formatStateUsing(fn ($state) => $state ? 'Rp ' . number_format($state, 2) : 'Rp 0.00'),
                        Forms\Components\TextInput::make('fine')
                            ->label('Late Payment Fine')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0),
                        Forms\Components\Select::make('payment_method')
                            ->options([
                                'cash' => 'Cash',
                                'transfer' => 'Bank Transfer',
                            ])
                            ->required()
                            ->placeholder('Select payment method'),
                    ])
                    ->visible(fn ($get) => $get('product_preview_visible'))
                    ->columns(2),

                Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Additional Notes')
                            ->placeholder('Additional notes or comments')
                            ->rows(3),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Calculate payment amount based on contract type and period
     */
    public static function calculatePaymentAmount(Loan $loan, int $period): float
    {
        $contractType = $loan->loanProduct->contract_type;
        $tenor = (int) $loan->loanProduct->tenor_months;

        switch ($contractType) {
            case 'Mudharabah':
                // For Mudharabah: margin per period, principal returned at the end
                $marginAmount = $loan->loan_amount * ($loan->margin_amount / 100);
                $marginPerPeriod = $marginAmount / $tenor;

                if ($period == $tenor) {
                    // Last period: margin + principal
                    return round($marginPerPeriod + $loan->loan_amount, 2);
                } else {
                    // Regular period: only margin
                    return round($marginPerPeriod, 2);
                }

            case 'Murabahah':
                // For Murabahah: selling price divided by tenor
                $sellingPrice = $loan->selling_price;

                // If selling price is not set, calculate it from purchase price and margin
                if (!$sellingPrice && $loan->purchase_price && $loan->margin_amount) {
                    $marginAmount = $loan->purchase_price * ($loan->margin_amount / 100);
                    $sellingPrice = $loan->purchase_price + $marginAmount;
                }

                return round($sellingPrice ? $sellingPrice / $tenor : 0, 2);

            case 'Musyarakah':
                // For Musyarakah: to be implemented later
                return 0;

            default:
                return 0;
        }
    }

    /**
     * Update loan payment status based on approved payments
     */
    public static function updateLoanPaymentStatus(Loan $loan): void
    {
        // Refresh the loan to get latest data
        $loan->refresh();

        $approvedPayments = $loan->payments()->where('status', 'approved')->count();
        $totalPeriods = (int) $loan->loanProduct->tenor_months;

        \Log::info('Updating loan payment status', [
            'loan_id' => $loan->id,
            'approved_payments' => $approvedPayments,
            'total_periods' => $totalPeriods,
            'current_status' => $loan->payment_status
        ]);

        if ($approvedPayments == 0) {
            $loan->payment_status = 'not_paid';
        } elseif ($approvedPayments < $totalPeriods) {
            $loan->payment_status = 'on_going';
        } else {
            $loan->payment_status = 'paid';
            $loan->paid_off_at = now();
        }

        $loan->save();

        \Log::info('Loan payment status updated', [
            'loan_id' => $loan->id,
            'new_status' => $loan->payment_status
        ]);
    }

    /**
     * Process journal entries for approved payment
     */
    public static function processJournalEntries(LoanPayment $payment): void
    {
        // Journal entries for loan payments have been disabled
        // as the journal account configuration was removed from loan products

        \Log::info('Loan payment approved - journal entries disabled', [
            'payment_id' => $payment->id,
            'loan_id' => $payment->loan_id,
            'amount' => $payment->amount,
            'fine' => $payment->fine
        ]);

        // TODO: Implement manual journal entries or alternative accounting method
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('loan.account_number')
                    ->label('Loan Account')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('loan.member.full_name')
                    ->label('Member')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('loan.loanProduct.contract_type')
                    ->label('Contract Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Mudharabah' => 'success',
                        'Murabahah' => 'info',
                        'Musyarakah' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('payment_period')
                    ->label('Period')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Payment Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Method')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'cash' => 'success',
                        'transfer' => 'info',
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
                Tables\Columns\TextColumn::make('loan.payment_status')
                    ->label('Loan Payment Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'not_paid' => 'gray',
                        'on_going' => 'warning',
                        'paid' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'not_paid' => 'Not Paid',
                        'on_going' => 'Progress',
                        'paid' => 'Paid',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    }),

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
                    ]),
                Tables\Filters\SelectFilter::make('contract_type')
                    ->relationship('loan.loanProduct', 'contract_type')
                    ->options([
                        'Mudharabah' => 'Mudharabah',
                        'Murabahah' => 'Murabahah',
                        'Musyarakah' => 'Musyarakah',
                    ]),
                Tables\Filters\SelectFilter::make('loan_payment_status')
                    ->relationship('loan', 'payment_status')
                    ->options([
                        'not_paid' => 'Not Paid',
                        'on_going' => 'Progress',
                        'paid' => 'Paid',
                    ]),
                Tables\Filters\Filter::make('payment_date')
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
                    ->visible(fn (LoanPayment $record) => $record->status === 'pending' && auth()->user()->hasRole('kepala_cabang'))
                    ->requiresConfirmation()
                    ->modalHeading('Approve Payment')
                    ->modalDescription('Are you sure you want to approve this payment?')
                    ->action(function (LoanPayment $record) {
                        try {
                            DB::beginTransaction();

                            // Update payment status
                            $record->status = 'approved';
                            $record->reviewed_by = auth()->id();
                            $record->save();

                            $loan = $record->loan;
                            self::updateLoanPaymentStatus($loan);
                            self::processJournalEntries($record);

                            DB::commit();

                            Notification::make()
                                ->title('Payment approved successfully')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            DB::rollBack();

                            Log::error('Error approving loan payment: ' . $e->getMessage(), [
                                'payment_id' => $record->id,
                                'exception' => $e,
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
                    ->visible(fn (LoanPayment $record) => $record->status === 'pending' && auth()->user()->hasRole('kepala_cabang'))
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('rejection_notes')
                            ->label('Reason for Rejection')
                            ->required(),
                    ])
                    ->action(function (LoanPayment $record, array $data) {
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
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
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
            'index' => Pages\ListLoanPayments::route('/'),
            'create' => Pages\CreateLoanPayment::route('/create'),
            'view' => Pages\ViewLoanPayment::route('/{record}'),
            'edit' => Pages\EditLoanPayment::route('/{record}/edit'),
        ];
    }

    public static function getValidationRules(): array
    {
        return [
            'loan_id' => [
                'required',
                'exists:loans,id',
            ],
            'payment_period' => [
                'required',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) {
                    if (!request()->has('loan_id')) {
                        return;
                    }

                    $loanId = request()->input('loan_id');
                    $existingPayment = LoanPayment::where('loan_id', $loanId)
                        ->where('payment_period', $value)
                        ->whereIn('status', ['approved', 'pending']);

                    if (request()->route('record')) {
                        $existingPayment->where('id', '!=', request()->route('record'));
                    }

                    if ($existingPayment->exists()) {
                        $fail("Payment for period {$value} has already been made.");
                    }
                },
            ],
        ];
    }
}