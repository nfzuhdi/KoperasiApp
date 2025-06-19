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
use Filament\Tables\Columns\TextColumn;

class LoanPaymentResource extends Resource
{
    protected static ?string $model = LoanPayment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Payments';

    public static function getModelLabel(): string
    {
        return 'Pembayaran Pinjaman';
    }

    public static function getPluralLabel(): string
    {
        return 'Pembayaran Pinjaman';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Akun')
                    ->schema([
                        Forms\Components\Select::make('loan_id')
                            ->label('AKUN PINJAMAN')
                            ->relationship('loan', 'account_number', function ($query, $get) {
                                if (request()->has('loan_id')) {
                                    return $query->where('id', request()->get('loan_id'));
                                }
                                return $query->where('payment_status', '!=', 'paid');
                            })
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->account_number} - {$record->loanProduct->name} - {$record->member->full_name}")
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->default(request()->get('loan_id'))
                            ->afterStateUpdated(function (callable $set) {
                                $set('product_preview_visible', true);
                            }),
                    ])
                    ->columnSpanFull(),

                Section::make('Detail Produk')
                    ->schema([
                        Forms\Components\Placeholder::make('product_name')
                            ->label('NAMA PRODUK')
                            ->content(function ($get) {
                                $loan = Loan::with('loanProduct')->find($get('loan_id'));
                                return $loan && $loan->loanProduct ? $loan->loanProduct->name : '-';
                            }),
                        Forms\Components\Placeholder::make('contract_type')
                            ->label('JENIS PEMBIAYAAN')
                            ->content(function ($get) {
                                $loan = Loan::with('loanProduct')->find($get('loan_id'));
                                return $loan && $loan->loanProduct ? $loan->loanProduct->contract_type : '-';
                            }),
                        Forms\Components\Placeholder::make('loan_amount')
                            ->label('JUMLAH PINJAMAN')
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
                            ->label('MARGIN(%)')
                            ->content(function ($get) {
                                $loan = Loan::with('loanProduct')->find($get('loan_id'));
                                return $loan ? $loan->margin_amount . '%' : '-';
                            }),
                        Forms\Components\Placeholder::make('tenor_months')
                            ->label('TENOR')
                            ->content(function ($get) {
                                $loan = Loan::with('loanProduct')->find($get('loan_id'));
                                return $loan && $loan->loanProduct ? $loan->loanProduct->tenor_months . ' Bulan' : '-';
                            }),
                        Forms\Components\Placeholder::make('payment_status')
                            ->label('STATUS PEMBAYARAN')
                            ->content(function ($get) {
                                $loan = Loan::with('loanProduct')->find($get('loan_id'));
                                return $loan ? ucfirst(str_replace('_', ' ', $loan->payment_status)) : '-';
                            }),
                    ])
                    ->visible(fn ($get) => (bool) $get('loan_id'))
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Detail Pembayaran')
                    ->schema([
                        Forms\Components\Select::make('payment_period')
                            ->label('PERIODE PEMBAYARAN')
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
                                    $options[(string)($tenor + 1)] = "Periode " . ($tenor + 1) . " - Pengembalian Modal";
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
                            ->label('JATUH TEMPO')
                            ->disabled()
                            ->formatStateUsing(fn ($get) => $get('due_date') ? \Carbon\Carbon::parse($get('due_date'))->format('d/m/Y') : '-'),
                        
                        Forms\Components\TextInput::make('member_profit')
                            ->label('KEUNTUNGAN ANGGOTA')
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
                                !$get('is_principal_return') ? 'KEUNTUNGAN KOPERASI' : 'Payment Amount')
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
                            ->label('DENDA KETERLAMBATAN')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->visible(function ($get) {
                                $dueDate = $get('due_date');
                                if (!$dueDate) return false;
                                
                                return now()->isAfter(\Carbon\Carbon::parse($dueDate));
                            })
                            ->hint('Denda keterlambatan')
                            ->hintColor('danger')
                            ->afterStateUpdated(function (callable $set, $state) {
                                $set('fine', (float)$state);
                            }),
                        Forms\Components\Select::make('payment_method')
                            ->label('METODE PEMBAYARAN')
                            ->options([
                                'cash' => 'Cash',
                                'transfer' => 'Transfer Bank',
                            ])
                            ->required()
                            ->placeholder('Pilih metode pembayaran'),
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

                Section::make('Informasi Tambahan')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('INFORMASI TAMBAHAN')
                            ->placeholder('Informasi atau komen')
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
                    ->label('Nomor Akun')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('loan.member.full_name')
                    ->label('Nama Anggota')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('loan.loanProduct.contract_type')
                    ->label('Jenis Pembiayaan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Mudharabah' => 'success',
                        'Murabahah' => 'info',
                        'Musyarakah' => 'warning',
                    }),
                Tables\Columns\TextColumn::make('payment_period')
                    ->label('Periode')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Pembayaran')
                    ->dateTime('d/m/Y H:i:s')
                    ->timezone('Asia/Jakarta'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('fine')
                    ->label('Denda')
                    ->state(function ($record): float {
                        return $record->fine ?? 0;
                    })
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Metode Pembayaran')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'cash' => 'success',
                        'transfer' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cash' => 'Cash',
                        'transfer' => 'Transfer Bank',
                        default => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'approved' => 'Disetujui',
                        'pending' => 'Menunggu',
                        'rejected' => 'Ditolak',
                        default => ucfirst($state),
                    }),
            ])
            ->filters([
    Tables\Filters\Filter::make('payment_date')
        ->form([
            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\DatePicker::make('from')
                        ->label('Dari')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->placeholder('dd/mm/yyy'),
                    Forms\Components\DatePicker::make('until')
                        ->label('Sampai')
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
            'approved' => 'Disetujui',
            'pending' => 'Menunggu',
            'rejected' => 'Ditolak',
        ]),

    Tables\Filters\SelectFilter::make('payment_method')
        ->label('Metode Pembayaran')
        ->options([
            'cash' => 'Cash',
            'transfer' => 'Transfer Bank',
        ]),

    Tables\Filters\SelectFilter::make('contract_type')
        ->label('Jenis Pembiayaan')
        ->relationship('loan.loanProduct', 'contract_type')
        ->options([
            'Mudharabah' => 'Mudharabah',
            'Murabahah' => 'Murabahah',
            'Musyarakah' => 'Musyarakah',
        ]),
])

            ->actions([
                Action::make('approve')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->iconButton()
                    ->visible(fn (LoanPayment $record) => $record->status === 'pending' && auth()->user()->hasRole('kepala_cabang'))
                    ->requiresConfirmation()
                    ->modalHeading('Setujui Pembayaran Pinjaman')
                    ->modalDescription('Apakah anda yakin ingin menyetujui pembayaran pinjaman ini?')
                    ->modalSubmitActionLabel('Setujui')
                    ->modalCancelActionLabel('Batal')
                    ->action(function (LoanPayment $record) {
                        try {
                            DB::beginTransaction();

                            JurnalUmum::where('loan_payment_id', $record->id)->delete();

                            $record->status = 'approved';
                            $record->reviewed_by = auth()->id();
                            $record->save();
                            
                            $loan = Loan::find($record->loan_id);
                            if (!$loan) {
                                throw new \Exception("Loan not found with ID: {$record->loan_id}");
                            }

                            $transactionNumber = 'LOAN-PAY-' . $loan->id . '-' . now()->format('Ymd-His');

                            if (empty($record->reference_number)) {
                                $record->reference_number = $loan->account_number;
                                $record->save();
                            }

                            $tenor = (int) $loan->loanProduct->tenor_months;
                            $isPrincipalReturn = ($record->payment_period == ($tenor + 1) || 
                                                $record->is_principal_return || 
                                                (is_numeric($record->payment_period) && (int)$record->payment_period > $tenor));

                            if ($isPrincipalReturn) {
                                $record->is_principal_return = true;
                                $record->save();
                            }

                            if ($loan->loanProduct->contract_type === 'Mudharabah' || $loan->loanProduct->contract_type === 'Musyarakah') {
                                $totalPayment = (float)($record->amount ?? 0);

                                if (!$isPrincipalReturn) {
                                    $debitAccount = JournalAccount::find($loan->loanProduct->journal_account_principal_debit_id);
                                    if ($debitAccount) {
                                        if ($debitAccount->account_position === 'debit') {
                                            $debitAccount->balance += $totalPayment;
                                        } else {
                                            $debitAccount->balance -= $totalPayment;
                                        }
                                        $debitAccount->save();

                                        JurnalUmum::create([
                                            'tanggal_bayar' => now(),
                                            'no_ref' => $record->reference_number,
                                            'no_transaksi' => $transactionNumber,
                                            'akun_id' => $debitAccount->id,
                                            'keterangan' => "Pembayaran bagi hasil {$loan->account_number} periode {$record->payment_period}",
                                            'debet' => $totalPayment,
                                            'kredit' => 0,
                                            'loan_payment_id' => $record->id
                                        ]);
                                    }

                                    $creditAccount = JournalAccount::find($loan->loanProduct->journal_account_income_credit_id);
                                    if ($creditAccount) {
                                        if ($creditAccount->account_position === 'credit') {
                                            $creditAccount->balance += $totalPayment;
                                        } else {
                                            $creditAccount->balance -= $totalPayment;
                                        }
                                        $creditAccount->save();

                                        JurnalUmum::create([
                                            'tanggal_bayar' => now(),
                                            'no_ref' => $record->reference_number,
                                            'no_transaksi' => $transactionNumber,
                                            'akun_id' => $creditAccount->id,
                                            'keterangan' => "Pembayaran bagi hasil {$loan->account_number} periode {$record->payment_period}",
                                            'debet' => 0,
                                            'kredit' => $totalPayment,
                                            'loan_payment_id' => $record->id
                                        ]);
                                    }
                                } else {
                                    $balanceDebitAccount = JournalAccount::find($loan->loanProduct->journal_account_balance_debit_id);
                                    $principalDebitAccount = JournalAccount::find($loan->loanProduct->journal_account_principal_debit_id);
                                    if ($principalDebitAccount) {
                                        if ($principalDebitAccount->account_position === 'debit') {
                                            $principalDebitAccount->balance += $totalPayment;
                                        } else {
                                            $principalDebitAccount->balance -= $totalPayment;
                                        }
                                        $principalDebitAccount->save();

                                        JurnalUmum::create([
                                            'tanggal_bayar' => now(),
                                            'no_ref' => $record->reference_number,
                                            'no_transaksi' => $transactionNumber,
                                            'akun_id' => $principalDebitAccount->id,
                                            'keterangan' => "Pengembalian modal {$loan->account_number}",
                                            'debet' => $totalPayment,
                                            'kredit' => 0,
                                            'loan_payment_id' => $record->id
                                        ]);
                                    }
                                    
                                    if ($balanceDebitAccount) {
                                        if ($balanceDebitAccount->account_position === 'debit') {
                                            $balanceDebitAccount->balance -= $totalPayment;
                                        } else {
                                            $balanceDebitAccount->balance += $totalPayment;
                                        }
                                        $balanceDebitAccount->save();

                                        JurnalUmum::create([
                                            'tanggal_bayar' => now(),
                                            'no_ref' => $record->reference_number,
                                            'no_transaksi' => $transactionNumber,
                                            'akun_id' => $balanceDebitAccount->id,
                                            'keterangan' => "Pengembalian modal {$loan->account_number}",
                                            'debet' => 0,
                                            'kredit' => $totalPayment,
                                            'loan_payment_id' => $record->id
                                        ]);
                                    }
                                    
                                }
                            } else if ($loan->loanProduct->contract_type === 'Murabahah') {
                                $totalPayment = (float)($record->amount ?? 0);
                                $loanProduct = $loan->loanProduct;

                                $kasAccount = JournalAccount::find($loanProduct->journal_account_principal_debit_id);
                                if ($kasAccount) {
                                    if ($kasAccount->account_position === 'debit') {
                                        $kasAccount->balance += $totalPayment;
                                    } else {
                                        $kasAccount->balance -= $totalPayment;
                                    }
                                    $kasAccount->save();

                                    JurnalUmum::create([
                                        'tanggal_bayar' => now(),
                                        'no_ref' => $record->reference_number,
                                        'no_transaksi' => $transactionNumber,
                                        'akun_id' => $kasAccount->id,
                                        'keterangan' => "Pembayaran Murabahah {$loan->account_number} periode {$record->payment_period}",
                                        'debet' => $totalPayment,
                                        'kredit' => 0,
                                        'loan_payment_id' => $record->id
                                    ]);
                                }

                                $piutangAccount = JournalAccount::find($loanProduct->journal_account_balance_debit_id);
                                if ($piutangAccount) {
                                    if ($piutangAccount->account_position === 'debit') {
                                        $piutangAccount->balance -= $totalPayment;
                                    } else {
                                        $piutangAccount->balance += $totalPayment;
                                    }
                                    $piutangAccount->save();
                                    
                                    JurnalUmum::create([
                                        'tanggal_bayar' => now(),
                                        'no_ref' => $record->reference_number,
                                        'no_transaksi' => $transactionNumber,
                                        'akun_id' => $piutangAccount->id,
                                        'keterangan' => "Pembayaran Murabahah {$loan->account_number} periode {$record->payment_period}",
                                        'debet' => 0,
                                        'kredit' => $totalPayment,
                                        'loan_payment_id' => $record->id
                                    ]);
                                }
                            } else {
                            }

                            if ($record->fine > 0) {
                                $record->processFineJournalFixed($loan);
                            }
                            
                            self::updateLoanPaymentStatus($loan);

                            if ($loan->payment_status === 'paid') {
                                if (Schema::hasColumn('loans', 'completed_at')) {
                                    $loan->completed_at = now();
                                    $loan->save();
                                }
                            }

                            DB::commit();
                            
                            Notification::make()
                                ->title('Pembayaran Pinjaman berhasil disetujui')
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
                            ->label('Alasan Penolakan')
                            ->required(),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Tolak Pembayaran Pinjaman')
                    ->modalDescription('Apakah anda yakin ingin menolak pembayaran pinjaman ini?')
                    ->modalSubmitActionLabel('Tolak')
                    ->modalCancelActionLabel('Batal')
                    ->action(function (LoanPayment $record, array $data) {
                        $record->status = 'rejected';
                        $record->reviewed_by = auth()->id();
                        $record->notes = ($record->notes ? $record->notes . "\n\n" : '') . "Rejected: " . $data['rejection_notes'];
                        $record->save();

                        Notification::make()
                            ->title('Pembayaran Pinjaman Ditolak')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\ViewAction::make()
                    ->icon('heroicon-m-eye')
                    ->iconButton(),
                Action::make('printInvoice')
                    ->icon('heroicon-m-document-text')
                    ->color('info')
                    ->iconButton()
                    ->url(fn (LoanPayment $record) => route('loan-payment.invoice', ['record' => $record->id]))
                    ->openUrlInNewTab()
                    ->visible(fn (LoanPayment $record) => $record->status === 'approved'),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Tidak ada data pembayaran pinjaman yang ditemukan');;
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