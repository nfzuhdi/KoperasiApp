<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoanPaymentResource\Pages;
use App\Models\LoanPayment;
use App\Models\Loan;
use App\Models\JournalAccount;
use App\Models\JurnalUmum;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

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
                            ->relationship('loan', 'account_number', function ($query) {
                                return $query->where('payment_status', '!=', 'paid');
                            })
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->account_number} - {$record->loanProduct->name} - {$record->member->full_name}")
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (callable $set) {
                                $set('product_preview_visible', true);
                            }),
                    ])
                    ->columnSpanFull(),

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

                                if ($loan->loanProduct && $loan->loanProduct->contract_type === 'Murabahah') {
                                    $sellingPrice = $loan->selling_price;
                                    if (!$sellingPrice && $loan->purchase_price && $loan->margin_amount) {
                                        $marginAmount = $loan->purchase_price * ($loan->margin_amount / 100);
                                        $sellingPrice = $loan->purchase_price + $marginAmount;
                                    }

                                    return $sellingPrice ? 'Rp ' . number_format($sellingPrice, 2) : 'Rp 0.00';
                                }

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
                    ->visible(fn ($get) => (bool) $get('loan_id'))
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
                                    ->where('is_principal_return', true)
                                    ->exists();
                                    
                                if (($loan->loanProduct->contract_type === 'Mudharabah' || 
                                     $loan->loanProduct->contract_type === 'Musyarakah') && 
                                    !$hasPrincipalReturn) {
                                    $options[(string)($tenor + 1)] = "Period " . ($tenor + 1) . " - Principal Return";
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
                                        $periodNumber = (int) $state;
                                        $tenor = (int) $loan->loanProduct->tenor_months;
                                        
                                        $isPrincipalReturn = ($periodNumber > $tenor) || 
                                                            (is_string($state) && str_contains(strtolower($state), 'principal return'));
                                        $set('is_principal_return', $isPrincipalReturn);
                                        
                                        $dueDate = $loan->disbursed_at
                                            ? $loan->disbursed_at->copy()->addMonths($periodNumber)->format('Y-m-d')
                                            : now()->addMonths($periodNumber)->format('Y-m-d');

                                        $set('due_date', $dueDate);
                                        $set('due_date_display', $dueDate);
                                        
                                        if ($isPrincipalReturn) {
                                            $set('amount', $loan->loan_amount);
                                            $set('amount_display', $loan->loan_amount);
                                            $set('member_profit', 0);
                                            $set('koperasi_profit', 0);
                                        } else if (
                                            $loan->loanProduct->contract_type === 'Mudharabah' || 
                                            $loan->loanProduct->contract_type === 'Musyarakah'
                                        ) {
                                            $set('member_profit', null);
                                            $set('koperasi_profit', null);
                                            $set('amount', null);
                                        } else {
                                            $amount = self::calculatePaymentAmount($loan, $periodNumber);
                                            $set('amount', $amount);
                                            $set('amount_display', $amount);
                                        }
                                    }
                                }
                            }),
                        Forms\Components\Hidden::make('due_date'),
                        Forms\Components\TextInput::make('due_date_display')
                            ->label('Due Date')
                            ->disabled()
                            ->formatStateUsing(fn ($get) => $get('due_date') ? \Carbon\Carbon::parse($get('due_date'))->format('d/m/Y') : '-'),
                        
                        Forms\Components\TextInput::make('member_profit')
                            ->label('Keuntungan Anggota')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->live(onBlur: true)
                            ->visible(function ($get) {
                                if (!(bool) $get('loan_id')) return false;

                                $loan = Loan::find($get('loan_id'));
                                if (!$loan || !$loan->loanProduct) return false;

                                return ($loan->loanProduct->contract_type === 'Mudharabah' || 
                                        $loan->loanProduct->contract_type === 'Musyarakah') && 
                                       !$get('is_principal_return');
                            })
                            ->afterStateUpdated(function (callable $set, callable $get, $state) {
                                if ($state && $get('loan_id')) {
                                    $loanId = $get('loan_id');
                                    $loan = Loan::with(['loanProduct'])->find($loanId);
                                    
                                    if ($loan && $loan instanceof Loan) {
                                        $rate = $loan->margin_amount / 100;
                                        $tenor = (int) $loan->loanProduct->tenor_months;
                                        $kooperasiProfit = round((float) $state * $rate / $tenor, 2);
                                        $set('koperasi_profit', $kooperasiProfit);
                                        $set('amount', $kooperasiProfit);
                                        $set('amount_display', $kooperasiProfit);
                                    }
                                }
                            }),
                        
                        Forms\Components\Hidden::make('koperasi_profit'),
                        Forms\Components\Hidden::make('amount'),
                        
                        Forms\Components\TextInput::make('amount_display')
                            ->label(fn ($get) => 
                                (bool) $get('loan_id') && 
                                ($loan = Loan::find($get('loan_id'))) && 
                                ($loan->loanProduct->contract_type === 'Mudharabah' || 
                                 $loan->loanProduct->contract_type === 'Musyarakah') &&
                                !$get('is_principal_return') ? 'Payment Amount (Keuntungan Koperasi)' : 'Payment Amount')
                            ->prefix('Rp')
                            ->disabled()
                            ->numeric()
                            ->default(0)
                            ->reactive()
                            ->afterStateHydrated(function (callable $set, callable $get) {
                                if ((bool) $get('loan_id') && 
                                    ($loan = Loan::find($get('loan_id'))) && 
                                    ($loan->loanProduct->contract_type === 'Mudharabah' || 
                                     $loan->loanProduct->contract_type === 'Musyarakah') &&
                                    !$get('is_principal_return')) {
                                    if ($get('member_profit')) {
                                        $loan = Loan::with(['loanProduct'])->find($get('loan_id'));
                                        if ($loan) {
                                            $rate = $loan->margin_amount / 100;
                                            $tenor = (int) $loan->loanProduct->tenor_months;
                                            $kooperasiProfit = round((float) $get('member_profit') * $rate / $tenor, 2);                                          
                                            $set('amount_display', $kooperasiProfit);
                                            $set('amount', $kooperasiProfit);
                                            $set('koperasi_profit', $kooperasiProfit);
                                        }
                                    } else if ($get('koperasi_profit')) {
                                        $set('amount_display', $get('koperasi_profit'));
                                    }
                                } else {
                                    $set('amount_display', $get('amount'));
                                }
                            }),
                        
                        Forms\Components\TextInput::make('fine')
                            ->label('Late Payment Fine')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->visible(function ($get) {
                                $dueDate = $get('due_date');
                                if (!$dueDate) return false;
                                
                                return now()->isAfter(\Carbon\Carbon::parse($dueDate));
                            })
                            ->hint('Fine for late payment')
                            ->hintColor('danger')
                            ->afterStateUpdated(function (callable $set, $state) {
                                $set('fine', (float)$state);
                            }),
                        Forms\Components\Select::make('payment_method')
                            ->options([
                                'cash' => 'Cash',
                                'transfer' => 'Bank Transfer',
                            ])
                            ->required()
                            ->placeholder('Select payment method'),
                        Forms\Components\Hidden::make('is_principal_return')
                            ->default(false),
                        Forms\Components\Hidden::make('is_late')
                            ->default(function ($get) {
                                $dueDate = $get('due_date');
                                if (!$dueDate) return false;
                                
                                return now()->isAfter(\Carbon\Carbon::parse($dueDate));
                            }),
                    ])
                    ->visible(fn ($get) => (bool) $get('loan_id'))
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

    public static function calculatePaymentAmount(Loan $loan, int $period): float
    {
        $contractType = $loan->loanProduct->contract_type;
        $tenor = (int) $loan->loanProduct->tenor_months;

        switch ($contractType) {
            case 'Mudharabah':
                $marginAmount = $loan->loan_amount * ($loan->margin_amount / 100);
                $marginPerPeriod = $marginAmount / $tenor;

                return round($marginPerPeriod, 2);

            case 'Murabahah':
                $sellingPrice = $loan->selling_price;

                if (!$sellingPrice && $loan->purchase_price && $loan->margin_amount) {
                    $marginAmount = $loan->purchase_price * ($loan->margin_amount / 100);
                    $sellingPrice = $loan->purchase_price + $marginAmount;
                }

                return round($sellingPrice ? $sellingPrice / $tenor : 0, 2);

            case 'Musyarakah':
                $marginAmount = $loan->loan_amount * ($loan->margin_amount / 100);
                $marginPerPeriod = $marginAmount / $tenor;

                return round($marginPerPeriod, 2);

            default:
                return 0;
        }
    }

    /**
     * Update loan payment status based on approved payments
     * 
     * @param Loan $loan
     * @return void
     */
    public static function updateLoanPaymentStatus(Loan $loan)
    {
        $totalApprovedPayments = LoanPayment::where('loan_id', $loan->id)
            ->where('status', 'approved')
            ->sum('amount');

        $totalToBePaid = $loan->loanProduct->contract_type === 'Murabahah' 
            ? $loan->selling_price : $loan->loan_amount;

        if ($totalApprovedPayments >= $totalToBePaid) {
            $loan->payment_status = 'paid';
            $loan->paid_off_at = now();
        } else if ($totalApprovedPayments > 0) {
            $loan->payment_status = 'on_going';
        } else {
            $loan->payment_status = 'not_paid';
        }
        
        $loan->save();
    }

    public static function processJournalEntries(LoanPayment $payment): void
    {
        $loan = $payment->loan;
        $loanProduct = $loan->loanProduct;
        
        if (!$loanProduct) {
            return;
        }

        $payment = LoanPayment::find($payment->id);
        $paymentAmount = (float)($payment->amount ?? 0);
        
        try {
            DB::beginTransaction();

            $cashAccount = JournalAccount::find($loanProduct->journal_account_principal_credit_id);
            
            if (!$cashAccount) {
                DB::rollBack();
                return;
            }

            $loanAccount = JournalAccount::find($loanProduct->journal_account_balance_debit_id);
            
            if (!$loanAccount) {
                DB::rollBack();
                return;
            }
            
            $loanAccount->balance += $paymentAmount;
            $loanAccount->save();
            
            $cashAccount->balance -= $paymentAmount;
            $cashAccount->save();
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
        }
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
                Tables\Columns\TextColumn::make('fine')
                    ->label('Fine')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Method')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'cash' => 'success',
                        'transfer' => 'info',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('loan.payment_status')
                    ->label('Payment Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'not_paid' => 'gray',
                        'on_going' => 'warning',
                        'paid' => 'success',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'not_paid' => 'Not Paid',
                        'on_going' => 'Progress',
                        'paid' => 'Paid',
                    }),

            ])
            ->filters([
    Tables\Filters\Filter::make('payment_date')
        ->form([
            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\DatePicker::make('from')
                        ->label('From')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->placeholder('dd/mm/yyy'),
                    Forms\Components\DatePicker::make('until')
                        ->label('Until')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->placeholder('dd/mm/yyy'),
                ]),
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
])

            ->actions([
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

                            $record->status = 'approved';
                            $record->reviewed_by = auth()->id();
                            $record->save();
                            $loan = Loan::find($record->loan_id);
                            if (!$loan) {
                                throw new \Exception("Loan not found with ID: {$record->loan_id}");
                            }
                            
                            if ($loan->loanProduct->contract_type === 'Mudharabah') {
                                $record->processJournalMudharabah($loan);
                            } else if ($loan->loanProduct->contract_type === 'Murabahah') {
                                $record->processJournalMurabahah($loan);
                            } else if ($loan->loanProduct->contract_type === 'Musyarakah') {
                                $record->processJournalMusyarakah($loan);
                            } else {
                                self::processJournalEntries($record);
                            }
                            
                            if ($record->fine > 0) {
                                $record->processFineJournalFixed($loan);
                            }
                            
                            self::updateLoanPaymentStatus($loan);
                            $transactionNumber = 'LOAN-PMT-' . $loan->id . '-' . now()->format('Ymd-His');
                            $loanProduct = $loan->loanProduct;
                            $debitAccount = JournalAccount::find($loanProduct->journal_account_principal_debit_id);
                            $creditAccount = JournalAccount::find($loanProduct->journal_account_balance_debit_id);
                            
                            if ($debitAccount && $creditAccount) {
                                JurnalUmum::create([
                                    'tanggal_bayar' => $record->created_at,
                                    'no_ref' => $record->reference_number,
                                    'no_transaksi' => $transactionNumber,
                                    'akun_id' => $debitAccount->id,
                                    'keterangan' => "Pembayaran pinjaman {$loan->account_number} periode {$record->payment_period}",
                                    'debet' => $record->amount,
                                    'kredit' => 0,
                                    'loan_payment_id' => $record->id,
                                ]);
                                
                                JurnalUmum::create([
                                    'tanggal_bayar' => $record->created_at,
                                    'no_ref' => $record->reference_number,
                                    'no_transaksi' => $transactionNumber,
                                    'akun_id' => $creditAccount->id,
                                    'keterangan' => "Pembayaran pinjaman {$loan->account_number} periode {$record->payment_period}",
                                    'debet' => 0,
                                    'kredit' => $record->amount,
                                    'loan_payment_id' => $record->id,
                                ]);
                            }

                            if ($loan->payment_status === 'paid') {
                                if (Schema::hasColumn('loans', 'completed_at')) {
                                    $loan->completed_at = now();
                                    $loan->save();
                                }
                            }

                            DB::commit();
                            
                            Notification::make()
                                ->title('Payment approved successfully')
                                ->success()
                                ->send();
                                
                        } catch (\Exception $e) {
                            DB::rollBack();
                            
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
                Tables\Actions\ViewAction::make()
                    ->icon('heroicon-m-eye')
                    ->iconButton(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [            
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoanPayments::route('/'),
            'create' => Pages\CreateLoanPayment::route('/create'),
            'view' => Pages\ViewLoanPayment::route('/{record}'),
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