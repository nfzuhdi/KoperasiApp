<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoanResource\Pages;
use App\Models\Loan;
use App\Models\LoanProduct;
use App\Models\JournalAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Forms\Components\Section;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class LoanResource extends Resource
{
    protected static ?string $model = Loan::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Loans & Savings';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('LoanTabs')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Informasi Pembiayaan')
                            ->schema([
                                Forms\Components\Section::make('Informasi Anggota')
                                    ->schema([
                                        Forms\Components\Select::make('member_id')
                                            ->relationship('member', 'full_name')
                                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->full_name} - {$record->member_id}")
                                            ->searchable()
                                            ->preload()
                                            ->required(),
                                    ]),
                                
                                Forms\Components\Section::make('Informasi Produk')
                                    ->schema([
                                        Forms\Components\Select::make('loan_product_id')
                                            ->relationship('loanProduct', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(fn (callable $set) => $set('product_preview_visible', true)),
                                    ]),
                                
                                // Preview Produk Pembiayaan
                                Section::make('Detail Produk')
                                    ->schema([
                                        Forms\Components\Placeholder::make('contract_type')
                                            ->label('Jenis Akad')
                                            ->content(function ($get) {
                                                $product = LoanProduct::find($get('loan_product_id'));
                                                return $product ? $product->contract_type : '-';
                                            }),
                                        
                                        // Common fields for all contract types
                                        Forms\Components\Placeholder::make('min_amount')
                                            ->label('Jumlah Minimal')
                                            ->content(function ($get) {
                                                $product = LoanProduct::find($get('loan_product_id'));
                                                return $product && $product->min_amount ? 'Rp ' . number_format($product->min_amount, 2) : '-';
                                            }),
                                        Forms\Components\Placeholder::make('max_amount')
                                            ->label('Jumlah Maksimal')
                                            ->content(function ($get) {
                                                $product = LoanProduct::find($get('loan_product_id'));
                                                return $product && $product->max_amount ? 'Rp ' . number_format($product->max_amount, 2) : '-';
                                            }),
                                        
                                        // Rate fields only for non-Murabahah
                                        Forms\Components\Placeholder::make('min_rate')
                                            ->label('Rate Minimal')
                                            ->content(function ($get) {
                                                $product = LoanProduct::find($get('loan_product_id'));
                                                return $product && $product->min_rate ? $product->min_rate . '%' : '-';
                                            }),
                                        Forms\Components\Placeholder::make('max_rate')
                                            ->label('Rate Maksimal')
                                            ->content(function ($get) {
                                                $product = LoanProduct::find($get('loan_product_id'));
                                                return $product && $product->max_rate ? $product->max_rate . '%' : '-';
                                            }),
                                        
                                        // Common field for all contract types
                                        Forms\Components\Placeholder::make('tenor_months')
                                            ->label('Jangka Waktu')
                                            ->content(function ($get) {
                                                $product = LoanProduct::find($get('loan_product_id'));
                                                return $product ? $product->tenor_months . ' Bulan' : '-';
                                            }),

                                        Forms\Components\Placeholder::make('usage_purposes')
                                            ->label('Tujuan Pembiayaan')
                                            ->content(function ($get) {
                                                $product = LoanProduct::find($get('loan_product_id'));
                                                return $product ? $product->usage_purposes : '-';
                                            })
                                    ])
                                    ->visible(fn ($get) => $get('product_preview_visible'))
                                    ->columns(2)
                                    ->columnSpanFull(),
                                
                                Forms\Components\Section::make('Informasi Pembiayaan')
                                    ->schema([
                                        Forms\Components\TextInput::make('loan_amount')
                                            ->label('Jumlah Pembiayaan')
                                            ->required()
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->rules([
                                                function (callable $get) {
                                                    return function (string $attribute, $value, \Closure $fail) use ($get) {
                                                        $product = LoanProduct::find($get('loan_product_id'));
                                                        
                                                        if (!$product) return;
                                                        
                                                        if ($product->min_amount && $value < $product->min_amount) {
                                                            $fail("Jumlah pembiayaan tidak boleh kurang dari Rp " . number_format($product->min_amount, 2));
                                                        }
                                                        
                                                        if ($product->max_amount && $value > $product->max_amount) {
                                                            $fail("Jumlah pembiayaan tidak boleh lebih dari Rp " . number_format($product->max_amount, 2));
                                                        }
                                                    };
                                                }
                                            ])
                                            ->visible(function ($get) {
                                                $product = LoanProduct::find($get('loan_product_id'));
                                                return !$product || $product->contract_type !== 'Murabahah';
                                            }),
                                        
                                        Forms\Components\TextInput::make('margin_amount')
                                            ->label('Jumlah Margin')
                                            ->required()
                                            ->numeric()
                                            ->prefix('%')
                                            ->rules([
                                                function (callable $get) {
                                                    return function (string $attribute, $value, \Closure $fail) use ($get) {
                                                        $product = LoanProduct::find($get('loan_product_id'));
                                                        
                                                        if (!$product || $product->contract_type === 'Murabahah') return;
                                                        
                                                        if ($product->min_rate && $value < $product->min_rate) {
                                                            $fail("Margin tidak boleh kurang dari " . $product->min_rate . "%");
                                                        }
                                                        
                                                        if ($product->max_rate && $value > $product->max_rate) {
                                                            $fail("Margin tidak boleh lebih dari " . $product->max_rate . "%");
                                                        }
                                                    };
                                                }
                                            ])
                                            ->visible(function ($get) {
                                                $product = LoanProduct::find($get('loan_product_id'));
                                                return !$product || $product->contract_type !== 'Murabahah';
                                            }),
                                            
                                        // Fields for Murabahah
                                        Forms\Components\TextInput::make('purchase_price')
                                            ->label('Harga Beli')
                                            ->required()
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->live()
                                            ->afterStateUpdated(function (callable $set, callable $get) {
                                                if ($get('purchase_price') && $get('margin_amount')) {
                                                    $purchasePrice = floatval($get('purchase_price'));
                                                    $marginPercent = floatval($get('margin_amount'));
                                                    $marginAmount = $purchasePrice * ($marginPercent / 100);
                                                    $sellingPrice = $purchasePrice + $marginAmount;
                                                    $set('selling_price', $sellingPrice);
                                                }
                                            })
                                            ->rules([
                                                function (callable $get) {
                                                    return function (string $attribute, $value, \Closure $fail) use ($get) {
                                                        $product = LoanProduct::find($get('loan_product_id'));
                                                        
                                                        if (!$product || $product->contract_type !== 'Murabahah') return;
                                                        
                                                        if ($product->min_amount && $value < $product->min_amount) {
                                                            $fail("Harga beli tidak boleh kurang dari Rp " . number_format($product->min_amount, 2));
                                                        }
                                                        
                                                        if ($product->max_amount && $value > $product->max_amount) {
                                                            $fail("Harga beli tidak boleh lebih dari Rp " . number_format($product->max_amount, 2));
                                                        }
                                                    };
                                                }
                                            ])
                                            ->visible(function ($get) {
                                                $product = LoanProduct::find($get('loan_product_id'));
                                                return $product && $product->contract_type === 'Murabahah';
                                            }),
                                            
                                        Forms\Components\TextInput::make('margin_amount')
                                            ->label('Margin (%)')
                                            ->required()
                                            ->numeric()
                                            ->suffix('%')
                                            ->live()
                                            ->afterStateUpdated(function (callable $set, callable $get) {
                                                if ($get('purchase_price') && $get('margin_amount')) {
                                                    $purchasePrice = floatval($get('purchase_price'));
                                                    $marginPercent = floatval($get('margin_amount'));
                                                    $marginAmount = $purchasePrice * ($marginPercent / 100);
                                                    $sellingPrice = $purchasePrice + $marginAmount;
                                                    $set('selling_price', $sellingPrice);
                                                }
                                            })
                                            ->visible(function ($get) {
                                                $product = LoanProduct::find($get('loan_product_id'));
                                                return $product && $product->contract_type === 'Murabahah';
                                            }),
                                            
                                        Forms\Components\TextInput::make('selling_price')
                                            ->label('Harga Jual')
                                            ->required()
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->disabled()
                                            ->dehydrated(true) // Pastikan nilai ini disimpan meskipun disabled
                                            ->visible(function ($get) {
                                                $product = LoanProduct::find($get('loan_product_id'));
                                                return $product && $product->contract_type === 'Murabahah';
                                            }),
                                    ])
                                    ->columns(2)
                                    ->visible(fn ($get) => $get('product_preview_visible')),
                            ]),
                            
                        Forms\Components\Tabs\Tab::make('Jaminan')
                            ->schema([
                                Forms\Components\Section::make('Informasi Jaminan')
                                    ->schema([
                                        Forms\Components\Select::make('collateral_type')
                                            ->label('Jenis Jaminan')
                                            ->options([
                                                'bpkb' => 'BPKB Kendaraan',
                                                'shm' => 'Sertifikat Hak Milik (SHM)',
                                            ])
                                            ->nullable()
                                            ->live()
                                            ->searchable()
                                            ->required()
                                            ->preload()
                                            ->helperText('Pilih jenis jaminan yang digunakan'),
                                    ]),
                                    
                                Forms\Components\Section::make('Jaminan BPKB Kendaraan')
                                    ->schema([
                                        Forms\Components\TextInput::make('bpkb_collateral_value')
                                            ->label('Nilai Jaminan')
                                            ->required()
                                            ->numeric()
                                            ->prefix('Rp'),
                                            
                                        Forms\Components\TextInput::make('bpkb_owner_name')
                                            ->label('Nama Pemilik')
                                            ->required()
                                            ->maxLength(255),
                                            
                                        Forms\Components\TextInput::make('bpkb_number')
                                            ->label('Nomor BPKB')
                                            ->required()
                                            ->maxLength(50),
                                            
                                        Forms\Components\TextInput::make('bpkb_vehicle_number')
                                            ->label('Nomor Polisi')
                                            ->required()
                                            ->maxLength(20),
                                            
                                        Forms\Components\TextInput::make('bpkb_vehicle_brand')
                                            ->label('Merk Kendaraan')
                                            ->required()
                                            ->maxLength(50),
                                            
                                        Forms\Components\TextInput::make('bpkb_vehicle_type')
                                            ->label('Tipe Kendaraan')
                                            ->required()
                                            ->maxLength(50),
                                            
                                        Forms\Components\TextInput::make('bpkb_vehicle_year')
                                            ->label('Tahun Kendaraan')
                                            ->required()
                                            ->numeric()
                                            ->minValue(1980)
                                            ->maxValue(date('Y')),
                                            
                                        Forms\Components\TextInput::make('bpkb_frame_number')
                                            ->label('Nomor Rangka')
                                            ->required()
                                            ->maxLength(50),
                                            
                                        Forms\Components\TextInput::make('bpkb_engine_number')
                                            ->label('Nomor Mesin')
                                            ->required()
                                            ->maxLength(50),
                                    ])
                                    ->columns(2)
                                    ->visible(fn (callable $get) => $get('collateral_type') === 'bpkb'),
                                    
                                Forms\Components\Section::make('Jaminan Sertifikat Hak Milik')
                                    ->schema([
                                        Forms\Components\TextInput::make('shm_collateral_value')
                                            ->label('Nilai Jaminan')
                                            ->required()
                                            ->numeric()
                                            ->prefix('Rp'),
                                            
                                        Forms\Components\TextInput::make('shm_owner_name')
                                            ->label('Nama Pemilik')
                                            ->required()
                                            ->maxLength(255),
                                            
                                        Forms\Components\TextInput::make('shm_certificate_number')
                                            ->label('Nomor Sertifikat')
                                            ->required()
                                            ->maxLength(50),
                                            
                                        Forms\Components\TextInput::make('shm_land_area')
                                            ->label('Luas Tanah (m²)')
                                            ->required()
                                            ->numeric()
                                            ->minValue(1),
                                            
                                        Forms\Components\Textarea::make('shm_land_location')
                                            ->label('Lokasi Tanah')
                                            ->required()
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2)
                                    ->visible(fn (callable $get) => $get('collateral_type') === 'shm'),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('account_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('member.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('loanProduct.name')
                    ->numeric()
                    ->sortable(),  
                Tables\Columns\TextColumn::make('loan_amount')
                    ->numeric()
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('margin_amount')
                    ->numeric()
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('disbursement_status')
                ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'not_disbursed' => 'warning',
                        'disbursed' => 'success',
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->icon('heroicon-m-eye')
                    ->iconButton(),
                Action::make('approve')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->iconButton()
                    ->visible(fn (Loan $record) => $record->status === 'pending' && auth()->user()->hasRole('kepala_cabang'))
                    ->requiresConfirmation()
                    ->modalHeading('Approve Loan')
                    ->modalDescription('Are you sure you want to approve this loan? The status will be changed to approved.')
                    ->action(function (Loan $record) {
                        $record->status = 'approved';
                        $record->reviewed_by = auth()->id();
                        $record->approved_at = now();
                        $record->save();
                        
                        Notification::make()
                            ->title('Loan approved successfully')
                            ->success()
                            ->send();
                    }),
                Action::make('reject')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->iconButton()
                    ->visible(fn (Loan $record) => $record->status === 'pending' && auth()->user()->hasRole('kepala_cabang'))
                    ->form([
                        Textarea::make('rejected_reason')
                            ->label('Reason for Rejection')
                            ->required(),
                    ])
                    ->action(function (Loan $record, array $data) {
                        $record->status = 'rejected';
                        $record->reviewed_by = auth()->id();
                        $record->rejected_reason = $data['rejected_reason'];
                        $record->save();
                        
                        Notification::make()
                            ->title('Loan rejected')
                            ->success()
                            ->send();
                    }),
                Action::make('disburse')
                    ->icon('heroicon-m-banknotes')
                    ->color('warning')
                    ->iconButton()
                    ->visible(fn (Loan $record) => $record->status === 'approved' && $record->disbursement_status === 'not_disbursed')
                    ->requiresConfirmation()
                    ->modalHeading('Disburse Loan')
                    ->modalDescription('Are you sure you want to disburse this loan? This will transfer funds to the member and change the status to disbursed.')
                    ->action(function (Loan $record) {
                        try {
                            DB::beginTransaction();
                            $record->disbursement_status = 'disbursed';
                            $record->disbursed_at = now();
                            $record->save();
                            
                            $loanProduct = $record->loanProduct;
                            if ($loanProduct && 
                                $loanProduct->journal_account_balance_debit_id && 
                                $loanProduct->journal_account_balance_credit_id) {
                                
                                $debitAccount = JournalAccount::find($loanProduct->journal_account_balance_debit_id);
                                if (!$debitAccount) {
                                    throw new \Exception("Debit journal account not found");
                                }
                                
                                $creditAccount = JournalAccount::find($loanProduct->journal_account_balance_credit_id);
                                if (!$creditAccount) {
                                    throw new \Exception("Credit journal account not found");
                                }
                                
                                $amount = $record->loan_amount;
                                if ($loanProduct->contract_type === 'Murabahah') {
                                    $amount = $record->purchase_price;
                                }
                                
                                if ($debitAccount->account_position === 'debit') {
                                    $debitAccount->balance += $amount;
                                } else {
                                    $debitAccount->balance -= $amount;
                                }
                                $debitAccount->save();
                                
                                if ($creditAccount->account_position === 'credit') {
                                    $creditAccount->balance += $amount;
                                } else {
                                    $creditAccount->balance -= $amount;
                                }
                                $creditAccount->save();
                            }

                            DB::commit();
                            
                            Notification::make()
                                ->title('Loan disbursed successfully')
                                ->success()
                                ->send();
                                
                        } catch (\Exception $e) {
                            DB::rollBack();
                            
                            Notification::make()
                                ->title('Error disbursing loan')
                                ->body('An error occurred: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ], position: ActionsPosition::BeforeColumns)
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoans::route('/'),
            'create' => Pages\CreateLoan::route('/create'),
            // 'edit' => Pages\EditLoan::route('/{record}/edit'),
            'view' => Pages\ViewLoan::route('/{record}'),
        ];
    }
}