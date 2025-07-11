<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SavingProductResource\Pages;
use App\Filament\Resources\SavingProductResource\RelationManagers;
use App\Models\SavingProduct;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SavingProductResource extends Resource
{
    protected static ?string $model = SavingProduct::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationGroup = 'Data Master';

    protected static ?int $navigationSort = 3;

    protected static ?string $pluralLabel = 'Produk Simpanan Koperasi';

    protected static ?string $PluralModelLabel = "Produk Simpanan";
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('SavingProductTabs')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Informasi Produk')
                            ->schema([
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\Section::make('Informasi')
                                            ->schema([
                                                Forms\Components\TextInput::make('savings_product_name')
                                                    ->label('NAMA PRODUK')
                                                    ->required()
                                                    ->maxLength(255),
                                                Forms\Components\Textarea::make('description')
                                                    ->label('DESKRIPSI PRODUK')
                                                    ->columnSpanFull(),
                                            ])
                                            ->columnSpan(2),
                                        Forms\Components\Section::make('Jenis Simpanan')
                                            ->schema([
                                                Select::make('savings_type')
                                                    ->label('JENIS SIMPANAN')
                                                    ->required()
                                                    ->options([
                                                        'principal' => 'SIMPANAN POKOK',
                                                        'mandatory' => 'SIMPANAN WAJIB',
                                                        'deposit' => 'TABUNGAN',
                                                        'time_deposit' => 'TABUNGAN BERJANGKA',                                              
                                                    ])
                                                    ->live(),
                                                Forms\Components\Select::make('contract_type')
                                                    ->label('JENIS AKAD')
                                                    ->options([
                                                        'Mudharabah' => 'Mudharabah',
                                                        'Wadiah' => 'Wadiah',
                                                    ])
                                                    ->visible(fn (callable $get) => 
                                                        in_array($get('savings_type'), ['deposit', 'time_deposit'])
                                                    )
                                                    ->live()
                                                    ->nullable(),
                                            ])
                                            ->columnSpan(1),
                                    ])
                                    ->columnSpanFull(),
                            ]),
                        Forms\Components\Tabs\Tab::make('Ketentuan Simpanan')
                            ->schema([
                                Forms\Components\Section::make('Pengaturan Umum')
                                    ->schema([
                                        Forms\Components\TextInput::make('min_deposit')
                                            ->label('Setoran Minimal')
                                            ->numeric()
                                            ->placeholder('-')
                                            ->required(),
                                        Forms\Components\TextInput::make('max_deposit')
                                            ->label('Setoran Maksimal')
                                            ->numeric()
                                            ->nullable()
                                            ->placeholder('-'),
                                        Forms\Components\TextInput::make('minimal_balance')
                                            ->label('Saldo Minimal')
                                            ->numeric()
                                            ->required()
                                            ->placeholder('-')
                                            ->helperText('Isi minimal sald pada rekening'),
                                        Forms\Components\Toggle::make('is_withdrawable')
                                            ->label('Dapat Ditarik')
                                            ->default(true),
                                        Forms\Components\Toggle::make('is_mandatory_routine')
                                            ->label('Wajib Rutin')
                                            ->default(false)
                                            ->live(),
                                        Forms\Components\Select::make('deposit_period')
                                            ->label('Periode Setoran')
                                            ->options([
                                                'weekly' => 'Mingguan',
                                                'monthly' => 'Bulanan',
                                                'yearly' => 'Tahunan',
                                            ])
                                            ->visible(fn (callable $get) => $get('is_mandatory_routine') === true)
                                            ->nullable(),
                                        
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),
                            ]),
                        Forms\Components\Tabs\Tab::make('Bagi Hasil')
                            ->schema([
                                Forms\Components\Section::make('Pengaturan Bagi Hasil')
                                    ->schema([
                                        Forms\Components\Hidden::make('profit_sharing_type')
                                            ->default('ratio'),
                                        Forms\Components\TextInput::make('member_ratio')
                                            ->label('Rasio Anggota (%)')
                                            ->numeric()
                                            ->required()
                                            ->default(60)
                                            ->minValue(1)
                                            ->maxValue(99)
                                            ->live()
                                            ->afterStateUpdated(function (callable $set, $state) {
                                                if ($state) {
                                                    $set('koperasi_ratio', 100 - (int)$state);
                                                }
                                            }),
                                        Forms\Components\TextInput::make('koperasi_ratio')
                                            ->label('Rasio Koperasi (%)')
                                            ->numeric()
                                            ->required()
                                            ->default(40)
                                            ->readonly()
                                            ->dehydrated(true) // Pastikan nilai ini disimpan ke database
                                            ->helperText('Otomatis dihitung dari rasio anggota'),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),
                            ])
                            ->visible(fn (callable $get) => $get('contract_type') === 'Mudharabah'),
                        Forms\Components\Tabs\Tab::make('Tenor Simpanan')
                            ->schema([
                                Forms\Components\Section::make('Ketentuan Tenor')
                                    ->schema([
                                        Forms\Components\TextInput::make('tenor_months')
                                            ->label('Jangka Waktu (Bulan)')
                                            ->numeric()
                                            ->nullable()
                                            ->required()
                                            ->helperText('Jangka waktu simpanan berjangka dalam bulan'),
                                        Forms\Components\TextInput::make('early_withdrawal_penalty')
                                            ->label('Denda Pencairan Dini')
                                            ->numeric()
                                            ->helperText('Denda jika dicairkan sebelum jatuh tempo'),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),
                            ])
                            ->visible(fn (callable $get) => $get('savings_type') === 'time_deposit'),
                        Forms\Components\Tabs\Tab::make('Parameter Akun Jurnal')
                            ->schema([
                                Forms\Components\Section::make('Akun Jurnal Setoran')
                                    ->schema([
                                        Forms\Components\Select::make('journal_account_deposit_debit_id')
                                            ->label('Akun Debit Setoran')
                                            ->relationship('depositDebitAccount', 'account_name', function ($query) {
                                                return $query->where('is_active', true);
                                            })
                                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->account_number} - {$record->account_name}")
                                            ->searchable()
                                            ->preload()
                                            ->helperText('Biasanya akun Kas/Bank'),
                                        
                                        Forms\Components\Select::make('journal_account_deposit_credit_id')
                                            ->label('Akun Kredit Setoran')
                                            ->relationship('depositCreditAccount', 'account_name', function ($query) {
                                                return $query->where('is_active', true);
                                            })
                                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->account_number} - {$record->account_name}")
                                            ->searchable()
                                            ->preload()
                                            ->helperText('Biasanya akun Simpanan'),
                                    ])
                                    ->columns(2),
                                    
                                Forms\Components\Section::make('Akun Jurnal Penarikan')
                                    ->schema([
                                        Forms\Components\Select::make('journal_account_withdrawal_debit_id')
                                            ->label('Akun Debit Penarikan')
                                            ->relationship('withdrawalDebitAccount', 'account_name', function ($query) {
                                                return $query->where('is_active', true);
                                            })
                                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->account_number} - {$record->account_name}")
                                            ->searchable()
                                            ->preload()
                                            ->helperText('Biasanya akun Simpanan'),
                                        
                                        Forms\Components\Select::make('journal_account_withdrawal_credit_id')
                                            ->label('Akun Kredit Penarikan')
                                            ->relationship('withdrawalCreditAccount', 'account_name', function ($query) {
                                                return $query->where('is_active', true);
                                            })
                                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->account_number} - {$record->account_name}")
                                            ->searchable()
                                            ->preload()
                                            ->helperText('Biasanya akun Kas/Bank'),
                                    ])
                                    ->columns(2),
                                    
                                Forms\Components\Section::make('Akun Jurnal Bagi Hasil')
                                    ->schema([
                                        Forms\Components\Select::make('journal_account_profitsharing_debit_id')
                                            ->label('Akun Debit Bagi Hasil')
                                            ->relationship('profitSharingDebitAccount', 'account_name', function ($query) {
                                                return $query->where('is_active', true);
                                            })
                                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->account_number} - {$record->account_name}")
                                            ->searchable()
                                            ->preload()
                                            ->helperText('Biasanya akun Bagi Hasil'),
                                        
                                        Forms\Components\Select::make('journal_account_profitsharing_credit_id')
                                            ->label('Akun Kredit Bagi Hasil')
                                            ->relationship('profitSharingCreditAccount', 'account_name', function ($query) {
                                                return $query->where('is_active', true);
                                            })
                                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->account_number} - {$record->account_name}")
                                            ->searchable()
                                            ->preload()
                                            ->helperText('Biasanya akun Kas/Pendapatan'),
                                    ])
                                    ->columns(2)
                                    ->visible(fn (callable $get) => $get('contract_type') === 'Mudharabah'),
                                    
                                Forms\Components\Section::make('Akun Jurnal Denda')
                                    ->schema([
                                        Forms\Components\Select::make('journal_account_penalty_debit_id')
                                            ->label('Akun Debit Denda')
                                            ->relationship('penaltyDebitAccount', 'account_name', function ($query) {
                                                return $query->where('is_active', true);
                                            })
                                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->account_number} - {$record->account_name}")
                                            ->searchable()
                                            ->preload()
                                            ->helperText('Biasanya akun Kas/Bank'),
                                        
                                        Forms\Components\Select::make('journal_account_penalty_credit_id')
                                            ->label('Akun Kredit Denda')
                                            ->relationship('penaltyCreditAccount', 'account_name', function ($query) {
                                                return $query->where('is_active', true);
                                            })
                                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->account_number} - {$record->account_name}")
                                            ->searchable()
                                            ->preload()
                                            ->helperText('Biasanya akun Pendapatan Denda'),
                                    ])
                                    ->columns(2),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode Produk')
                    ->searchable(),
                Tables\Columns\TextColumn::make('savings_product_name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('savings_type')
                    ->label('Jenis Simpanan')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'principal' => 'Simpanan Pokok',
                        'mandatory' => 'Simpanan Wajib',
                        'deposit' => 'Tabungan',
                        'time_deposit' => 'Tabungan Berjangka',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('min_deposit')
                    ->label('Setoran Minimal')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_withdrawable')
                    ->label('Dapat Ditarik')
                    ->boolean(),
                Tables\Columns\TextColumn::make('contract_type')
                    ->label('Jenis Akad')
                    ->placeholder('-'),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Tidak ada data produk simpanan yang ditemukan');
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
            'index' => Pages\ListSavingProducts::route('/'),
            'create' => Pages\CreateSavingProduct::route('/create'),
            'edit' => Pages\EditSavingProduct::route('/{record}/edit'),
        ];
    }
}



