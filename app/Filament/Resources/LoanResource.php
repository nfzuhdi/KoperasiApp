<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoanResource\Pages;
use App\Models\Loan;
use App\Models\LoanProduct;
use App\Models\JournalAccount;
use App\Models\JurnalUmum;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Enums\ActionsPosition;
use App\Filament\Resources\LoanResource\Widgets\LoansStatsOverview;

class LoanResource extends Resource
{
    protected static ?string $model = Loan::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Rekening Simpanan & Pinjaman';

    protected static ?int $navigationSort = 2;


    public static function getModelLabel(): string
    {
        return 'Rekening Pinjaman';
    }

    public static function getPluralLabel(): string
    {
        return 'Rekening Pinjaman';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('LoanTabs')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Informasi Pinjaman')
                            ->schema([
                                Forms\Components\Section::make('Informasi Anggota')
                                    ->schema([
                                        Forms\Components\Select::make('member_id')
                                            ->label('ANGGOTA')
                                            ->relationship('member', 'full_name', function ($query) {
                                                return $query->where('member_status', 'active');
                                            })
                                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->member_id} - {$record->full_name}")
                                            ->searchable()
                                            ->preload()
                                            ->required(),
                                    ]),
                                Forms\Components\Section::make('Informasi Produk')
                                    ->schema([
                                        Forms\Components\Select::make('loan_product_id')
                                            ->label('PRODUK PINJAMAN')
                                            ->relationship('loanProduct', 'name')
                                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->code} - {$record->name}")
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(fn (callable $set) => $set('product_preview_visible', true)),
                                    ]),
                                Section::make('Detail Produk')
                                    ->schema([
                                        Forms\Components\Placeholder::make('contract_type')
                                            ->label('NAMA PEMBIAYAAN')
                                            ->content(function ($get) {
                                                $product = LoanProduct::find($get('loan_product_id'));
                                                return $product ? $product->name : '-';
                                            }),

                                        Forms\Components\Placeholder::make('usage_purposes')
                                            ->label('TUJUAN')
                                            ->content(function ($get) {
                                                $product = LoanProduct::find($get('loan_product_id'));
                                                return $product ? $product->usage_purposes : '-';
                                            }),
                                        Forms\Components\Placeholder::make('min_amount')
                                            ->label('JUMLAH MINIMAL')
                                            ->content(function ($get) {
                                                $product = LoanProduct::find($get('loan_product_id'));
                                                return $product && $product->min_amount ? 'Rp ' . number_format($product->min_amount, 2) : '-';
                                            }),
                                        Forms\Components\Placeholder::make('max_amount')
                                            ->label('JUMLAH MAKSIMAL')
                                            ->content(function ($get) {
                                                $product = LoanProduct::find($get('loan_product_id'));
                                                return $product && $product->max_amount ? 'Rp ' . number_format($product->max_amount, 2) : '-';
                                            }),
                                        Forms\Components\Placeholder::make('min_rate')
                                            ->label('MARGIN MINIMAL')
                                            ->content(function ($get) {
                                                $product = LoanProduct::find($get('loan_product_id'));
                                                return $product && $product->min_rate ? $product->min_rate . '%' : '-';
                                            }),
                                        Forms\Components\Placeholder::make('max_rate')
                                            ->label('MARGIN MAKSIMAL')
                                            ->content(function ($get) {
                                                $product = LoanProduct::find($get('loan_product_id'));
                                                return $product && $product->max_rate ? $product->max_rate . '%' : '-';
                                            }),
                                        Forms\Components\Placeholder::make('tenor_months')
                                            ->label('TENOR (BULAN)')
                                            ->content(function ($get) {
                                                $product = LoanProduct::find($get('loan_product_id'));
                                                return $product ? $product->tenor_months . ' Bulan' : '-';
                                            }),
                                    ])
                                    ->visible(fn ($get) => $get('product_preview_visible'))
                                    ->columns(2)
                                    ->columnSpanFull(),

                                Forms\Components\Section::make('Informasi Pinjaman')
                                    ->schema([
                                        Forms\Components\TextInput::make('loan_amount')
                                            ->label('JUMLAH PINJAMAN YANG DIAJUKAN')
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
                                            ->label('MARGIN (%)')
                                            ->required()
                                            ->numeric()
                                            ->suffix('%')
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

                                        Forms\Components\TextInput::make('purchase_price')
                                            ->label('HARGA BELI')
                                            ->required()
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(function (callable $set, callable $get) {
                                                if ($get('purchase_price') && $get('margin_amount')) {
                                                    $purchasePrice = floatval($get('purchase_price'));
                                                    $marginAmount = floatval($get('margin_amount'));
                                                    $set('selling_price', $purchasePrice + $marginAmount);
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
                                            ->label('MARGIN')
                                            ->required()
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->live(debounce: 500)
                                            ->afterStateUpdated(function (callable $set, callable $get) {
                                                if ($get('purchase_price') && $get('margin_amount')) {
                                                    $purchasePrice = floatval($get('purchase_price'));
                                                    $marginAmount = floatval($get('margin_amount'));
                                                    $set('selling_price', $purchasePrice + $marginAmount);
                                                }
                                            })
                                            ->visible(function ($get) {
                                                $product = LoanProduct::find($get('loan_product_id'));
                                                return $product && $product->contract_type === 'Murabahah';
                                            }),

                                        Forms\Components\TextInput::make('selling_price')
                                            ->label('HARGA JUAL')
                                            ->required()
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->live(debounce: 500)
                                            ->disabled()
                                            ->dehydrated(true)
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
                                            ->label('JENIS JAMINAN')
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
                                            ->label('NILAI JAMINAN')
                                            ->required()
                                            ->numeric()
                                            ->prefix('Rp'),

                                        Forms\Components\TextInput::make('bpkb_owner_name')
                                            ->label('NAMA PEMILIK')
                                            ->required()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('bpkb_number')
                                            ->label('NOMOR BPKB')
                                            ->required()
                                            ->maxLength(50),

                                        Forms\Components\TextInput::make('bpkb_vehicle_number')
                                            ->label('NOMOR POLISI')
                                            ->required()
                                            ->maxLength(20),

                                        Forms\Components\TextInput::make('bpkb_vehicle_brand')
                                            ->label('MEREK KENDARAAN')
                                            ->required()
                                            ->maxLength(50),

                                        Forms\Components\TextInput::make('bpkb_vehicle_type')
                                            ->label('TIPE KENDARAAN')
                                            ->required()
                                            ->maxLength(50),

                                        Forms\Components\TextInput::make('bpkb_vehicle_year')
                                            ->label('TAHUN KENDARAAN')
                                            ->required()
                                            ->numeric()
                                            ->minValue(1980)
                                            ->maxValue(date('Y')),

                                        Forms\Components\TextInput::make('bpkb_frame_number')
                                            ->label('NOMOR RANGKA')
                                            ->required()
                                            ->maxLength(50),

                                        Forms\Components\TextInput::make('bpkb_engine_number')
                                            ->label('NOMOR MESIN')
                                            ->required()
                                            ->maxLength(50),
                                    ])
                                    ->columns(2)
                                    ->visible(fn (callable $get) => $get('collateral_type') === 'bpkb'),

                                Forms\Components\Section::make('Jaminan Sertifikat Hak Milik')
                                    ->schema([
                                        Forms\Components\TextInput::make('shm_collateral_value')
                                            ->label('NILAI JAMINAN')
                                            ->required()
                                            ->numeric()
                                            ->prefix('Rp'),

                                        Forms\Components\TextInput::make('shm_owner_name')
                                            ->label('NAMA PEMILIK')
                                            ->required()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('shm_certificate_number')
                                            ->label('NOMOR SERTIFIKAT')
                                            ->required()
                                            ->maxLength(50),

                                        Forms\Components\TextInput::make('shm_land_area')
                                            ->label('LUAS TANAH (m²)')
                                            ->required()
                                            ->numeric()
                                            ->minValue(1),

                                        Forms\Components\Textarea::make('shm_land_location')
                                            ->label('LOKASI TANAH')
                                            ->required()
                                            ->columnSpanFull()
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
                Tables\Columns\TextColumn::make('account_number')
                    ->label('Nomor Akun')
                    ->searchable(),
                Tables\Columns\TextColumn::make('member.full_name')
                    ->label('Nama Anggota')
                    ->searchable(),
                Tables\Columns\TextColumn::make('loanProduct.name')
                    ->label('Nama Pembiayaan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('loan_amount')
                    ->label('Nominal Pinjaman')
                    ->searchable()
                    ->numeric()
                    ->money('IDR')
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        if ($record->loanProduct && $record->loanProduct->contract_type === 'Murabahah') {
                            return $record->selling_price;
                        }
                        return $record->loan_amount;
                    })
                    ->tooltip(function ($record) {
                        if ($record->loanProduct && $record->loanProduct->contract_type === 'Murabahah') {
                            return 'Harga Jual (Selling Price)';
                        }
                        return 'Jumlah Pembiayaan (Loan Amount)';
                    }),
                    
                Tables\Columns\TextColumn::make('margin_amount')
                    ->label('Margin')
                    ->numeric()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status Persetujuan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'declined' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'approved' => 'Disetujui',
                        'pending' => 'Menunggu',
                        'declined' => 'Ditolak',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('disbursement_status')
                    ->label('Status Pencairan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'not_disbursed' => 'warning',
                        'disbursed' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'not_disbursed' => 'Belum Dicairkan',
                        'disbursed' => 'Sudah Dicairkan',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Status Pembayaran')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'not_paid' => 'gray',
                        'on_going' => 'warning',
                        'paid' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'not_paid' => 'Belum Dibayar',
                        'on_going' => 'Dalam Masa Pembayaran',
                        'paid' => 'Lunas',
                        default => $state,
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('contract_type')
                    ->relationship('loanProduct', 'contract_type')
                    ->label('Jenis Pembiayaan')
                    ->options([
                        'Mudharabah' => 'Mudharabah',
                        'Murabahah' => 'Murabahah',
                        'Musyarakah' => 'Musyarakah',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status Persetujuan')
                    ->options([
                        'approved' => 'Disetujui',
                        'pending' => 'Menunggu',
                        'rejected' => 'Ditolak',
                    ]),
                Tables\Filters\SelectFilter::make('disbursement_status')
                    ->label('Status Pencairan')
                    ->options([
                        'not_disbursed' => 'Belum Dicairkan',
                        'disbursed' => 'Sudah Dicairkan',
                    ]),
            ])
            ->actions([
                Action::make('approve')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->iconButton()
                    ->visible(fn (?Loan $record) => $record && $record->status === 'pending' && auth()->user()->hasRole('kepala_cabang'))
                    ->requiresConfirmation()
                    ->modalHeading('Setujui Pinjaman')
                    ->modalDescription('Apakah anda yakin ingin menyetujui pinjaman ini?')
                    ->modalSubmitActionLabel('Setujui')
                    ->modalCancelActionLabel('Batal')
                    ->action(function (Loan $record) {
                        $record->status = 'approved';
                        $record->reviewed_by = auth()->id();
                        $record->approved_at = now();
                        $record->save();

                        Notification::make()
                            ->title('Pinjaman telah disetujui')
                            ->success()
                            ->send();
                    }),
                Action::make('reject')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->iconButton()
                    ->visible(fn (?Loan $record) => $record && $record->status === 'pending' && auth()->user()->hasRole('kepala_cabang'))
                    ->form([
                        Textarea::make('rejected_reason')
                            ->label('Alasan Penolakan')
                            ->required(),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Tolak Pinjaman')
                    ->modalDescription('Apakah anda yakin ingin menolak pinjaman ini?')
                    ->modalSubmitActionLabel('Tolak')
                    ->modalCancelActionLabel('Batal')
                    ->action(function (Loan $record, array $data) {
                        $record->status = 'declined';
                        $record->reviewed_by = auth()->id();
                        $record->rejected_reason = $data['rejected_reason'];
                        $record->save();

                        Notification::make()
                            ->title('Pinjaman ditolak')
                            ->success()
                            ->send();
                    }),
                Action::make('disburse')
                    ->icon('heroicon-m-banknotes')
                    ->color('success')
                    ->iconButton()
                    ->visible(fn (?Loan $record) => $record && $record->status === 'approved' && $record->disbursement_status === 'not_disbursed')
                    ->requiresConfirmation()
                    ->modalHeading('Pencairan Pinjaman')
                    ->modalDescription('Apakah kamu yakin ingin mencairkan pinjaman ini?')
                    ->modalSubmitActionLabel('Cairkan')
                    ->modalCancelActionLabel('Batal')
                    ->action(function (Loan $record) {
                        try {
                            DB::beginTransaction();
                            
                            $record->disbursement_status = 'disbursed';
                            $record->disbursed_at = now();
                            $record->save();

                            $loanProduct = $record->loanProduct;
                            if ($loanProduct) {
                                if ($loanProduct->contract_type === 'Murabahah') {
                                    $purchasePrice = $record->purchase_price;
                                    $sellingPrice = $record->selling_price;
                                    $marginAmount = $sellingPrice - $purchasePrice;
                                    
                                    $piutangAccount = JournalAccount::find($loanProduct->journal_account_balance_debit_id);
                                    if (!$piutangAccount) {
                                        throw new \Exception("Akun Piutang Murabahah tidak ditemukan");
                                    }
                                    
                                    if ($piutangAccount->account_position === 'debit') {
                                        $piutangAccount->balance += $sellingPrice;
                                    } else {
                                        $piutangAccount->balance -= $sellingPrice;
                                    }
                                    $piutangAccount->save();
                                    
                                    $kasAccount = JournalAccount::find($loanProduct->journal_account_balance_credit_id);
                                    if (!$kasAccount) {
                                        throw new \Exception("Akun Kas/Bank tidak ditemukan");
                                    }
                                    
                                    if ($kasAccount->account_position === 'credit') {
                                        $kasAccount->balance += $purchasePrice;
                                    } else {
                                        $kasAccount->balance -= $purchasePrice;
                                    }
                                    $kasAccount->save();
                                    
                                    $pendapatanAccount = JournalAccount::find($loanProduct->journal_account_income_credit_id);
                                    if (!$pendapatanAccount) {
                                        throw new \Exception("Akun Pendapatan Margin tidak ditemukan");
                                    }
                                    
                                    if ($pendapatanAccount->account_position === 'credit') {
                                        $pendapatanAccount->balance += $marginAmount;
                                    } else {
                                        $pendapatanAccount->balance -= $marginAmount;
                                    }
                                    $pendapatanAccount->save();
                                    
                                    $transactionNumber = 'LOAN-DISB-' . $record->id . '-' . now()->format('Ymd-His');
                                    
                                    JurnalUmum::create([
                                        'tanggal_bayar' => now(),
                                        'no_ref' => $record->account_number,
                                        'no_transaksi' => $transactionNumber,
                                        'akun_id' => $piutangAccount->id,
                                        'keterangan' => "Pencairan pembiayaan Murabahah {$record->account_number}",
                                        'debet' => $sellingPrice,
                                        'kredit' => 0,
                                    ]);
                                    
                                    JurnalUmum::create([
                                        'tanggal_bayar' => now(),
                                        'no_ref' => $record->account_number,
                                        'no_transaksi' => $transactionNumber,
                                        'akun_id' => $kasAccount->id,
                                        'keterangan' => "Pencairan pembiayaan Murabahah {$record->account_number}",
                                        'debet' => 0,
                                        'kredit' => $purchasePrice,
                                    ]);
                                    
                                    JurnalUmum::create([
                                        'tanggal_bayar' => now(),
                                        'no_ref' => $record->account_number,
                                        'no_transaksi' => $transactionNumber,
                                        'akun_id' => $pendapatanAccount->id,
                                        'keterangan' => "Pendapatan margin Murabahah {$record->account_number}",
                                        'debet' => 0,
                                        'kredit' => $marginAmount,
                                    ]);
                                } else if ($loanProduct->contract_type === 'Mudharabah') {
                                    $amount = $record->loan_amount;
                                    
                                    $piutangAccount = JournalAccount::find($loanProduct->journal_account_balance_debit_id);
                                    if (!$piutangAccount) {
                                        throw new \Exception("Akun Piutang Mudharabah tidak ditemukan");
                                    }
                                    
                                    if ($piutangAccount->account_position === 'debit') {
                                        $piutangAccount->balance += $amount;
                                    } else {
                                        $piutangAccount->balance -= $amount;
                                    }
                                    $piutangAccount->save();
                                    
                                    $kasAccount = JournalAccount::find($loanProduct->journal_account_balance_credit_id);
                                    if (!$kasAccount) {
                                        throw new \Exception("Akun Kas/Bank tidak ditemukan");
                                    }
                                    
                                    if ($kasAccount->account_position === 'credit') {
                                        $kasAccount->balance += $amount;
                                    } else {
                                        $kasAccount->balance -= $amount;
                                    }
                                    $kasAccount->save();
                                    
                                    $transactionNumber = 'LOAN-DISB-' . $record->id . '-' . now()->format('Ymd-His');
                                    
                                    JurnalUmum::create([
                                        'tanggal_bayar' => now(),
                                        'no_ref' => $record->account_number,
                                        'no_transaksi' => $transactionNumber,
                                        'akun_id' => $piutangAccount->id,
                                        'keterangan' => "Pencairan pembiayaan Mudharabah {$record->account_number}",
                                        'debet' => $amount,
                                        'kredit' => 0,
                                    ]);
                                    
                                    JurnalUmum::create([
                                        'tanggal_bayar' => now(),
                                        'no_ref' => $record->account_number,
                                        'no_transaksi' => $transactionNumber,
                                        'akun_id' => $kasAccount->id,
                                        'keterangan' => "Pencairan pembiayaan Mudharabah {$record->account_number}",
                                        'debet' => 0,
                                        'kredit' => $amount,
                                    ]);
                                } else if ($loanProduct->contract_type === 'Musyarakah') {
                                    $amount = $record->loan_amount;
                                    
                                    $piutangAccount = JournalAccount::find($loanProduct->journal_account_balance_debit_id);
                                    if (!$piutangAccount) {
                                        throw new \Exception("Piutang Musyarakah account not found");
                                    }
                                    
                                    if ($piutangAccount->account_position === 'debit') {
                                        $piutangAccount->balance += $amount;
                                    } else {
                                        $piutangAccount->balance -= $amount;
                                    }
                                    $piutangAccount->save();
                                    
                                    $kasAccount = JournalAccount::find($loanProduct->journal_account_balance_credit_id);
                                    if (!$kasAccount) {
                                        throw new \Exception("Akun Kas/Bank tidak ditemukan");
                                    }
                                    
                                    if ($kasAccount->account_position === 'credit') {
                                        $kasAccount->balance += $amount;
                                    } else {
                                        $kasAccount->balance -= $amount;
                                    }
                                    $kasAccount->save();
                                    
                                    $transactionNumber = 'LOAN-DISB-' . $record->id . '-' . now()->format('Ymd-His');
                                    
                                    JurnalUmum::create([
                                        'tanggal_bayar' => now(),
                                        'no_ref' => $record->account_number,
                                        'no_transaksi' => $transactionNumber,
                                        'akun_id' => $piutangAccount->id,
                                        'keterangan' => "Pencairan pembiayaan Musyarakah {$record->account_number}",
                                        'debet' => $amount,
                                        'kredit' => 0,
                                    ]);
                                    
                                    JurnalUmum::create([
                                        'tanggal_bayar' => now(),
                                        'no_ref' => $record->account_number,
                                        'no_transaksi' => $transactionNumber,
                                        'akun_id' => $kasAccount->id,
                                        'keterangan' => "Pencairan pembiayaan Musyarakah {$record->account_number}",
                                        'debet' => 0,
                                        'kredit' => $amount,
                                    ]);
                                } else {
                                    if ($loanProduct->journal_account_balance_debit_id &&
                                        $loanProduct->journal_account_balance_credit_id) {

                                        $debitAccount = JournalAccount::find($loanProduct->journal_account_balance_debit_id);
                                        if (!$debitAccount) {
                                            throw new \Exception("Akun Jurnal Debit tidak ditemukan");
                                        }

                                        $creditAccount = JournalAccount::find($loanProduct->journal_account_balance_credit_id);
                                        if (!$creditAccount) {
                                            throw new \Exception("Akun Jurnal Kredit tidak ditemukan");
                                        }

                                        $amount = $record->loan_amount;
                                        
                                        $debitAccount->balance += $amount;
                                        $debitAccount->save();
                                        $creditAccount->balance -= $amount;
                                        $creditAccount->save();
                                        
                                        $transactionNumber = 'LOAN-DISB-' . $record->id . '-' . now()->format('Ymd-His');
                                        
                                        JurnalUmum::create([
                                            'tanggal_bayar' => now(),
                                            'no_ref' => $record->account_number,
                                            'no_transaksi' => $transactionNumber,
                                            'akun_id' => $debitAccount->id,
                                            'keterangan' => "Pencairan pembiayaan {$record->account_number} ({$loanProduct->contract_type})",
                                            'debet' => $amount,
                                            'kredit' => 0,
                                        ]);
                                        
                                        JurnalUmum::create([
                                            'tanggal_bayar' => now(),
                                            'no_ref' => $record->account_number,
                                            'no_transaksi' => $transactionNumber,
                                            'akun_id' => $creditAccount->id,
                                            'keterangan' => "Pencairan pembiayaan {$record->account_number} ({$loanProduct->contract_type})",
                                            'debet' => 0,
                                            'kredit' => $amount,
                                        ]);
                                    }
                                }
                            }
                            
                            DB::commit();
                            
                            Notification::make()
                                ->title('Pencairan Pinjaman Berhasil')
                                ->success()
                                ->send();
                                
                        } catch (\Exception $e) {
                            DB::rollBack();

                            Notification::make()
                                ->title('Pencairan Pinjaman Gagal')
                                ->body('An error occurred: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\ViewAction::make()
                    ->icon('heroicon-m-eye')
                    ->iconButton(),
            ])
            ->actionsPosition(ActionsPosition::AfterColumns)
            ->bulkActions([])
            ->emptyStateHeading('Tidak ada data pinjaman yang ditemukan');
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
            'view' => Pages\ViewLoan::route('/{record}'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            LoansStatsOverview::class,
        ];
    }
}