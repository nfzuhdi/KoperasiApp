<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoanProductResource\Pages;
use App\Models\LoanProduct;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;

class LoanProductResource extends Resource
{
    protected static ?string $model = LoanProduct::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationGroup = 'Master Data';
    
    public static function getModelLabel(): string
    {
        return 'Produk Pinjaman';
    }

    public static function getPluralLabel(): string
    {
        return 'Produk Pinjaman';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('LoanProductTabs')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Informasi Produk')
                            ->schema([
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\Section::make('Informasi')
                                            ->schema([
                                                Forms\Components\TextInput::make('name')
                                                    ->label('NAMA PEMBIAYAAN')
                                                    ->required()
                                                    ->unique(table: 'loan_products', column: 'name', ignoreRecord: true)
                                                    ->maxLength(255),
                                                Forms\Components\Textarea::make('usage_purposes')
                                                    ->label('TUJUAN PEMBIAYAAN')
                                                    ->columnSpanFull()
                                                    ->required(),
                                            ])
                                            ->columnSpan(2),
                                        Forms\Components\Section::make('Jenis Kontrak')
                                            ->schema([
                                                Select::make('contract_type')
                                                    ->label('JENIS PEMBIAYAAN')
                                                    ->options([
                                                        'Mudharabah' => 'Mudharabah',
                                                        'Musyarakah' => 'Musyarakah',
                                                        'Murabahah' => 'Murabahah',
                                                    ])
                                                    ->searchable()
                                                    ->required()
                                                    ->live(),
                                            ])
                                            ->columnSpan(1),
                                    ])
                                    ->columnSpanFull(),
                            ]),
                        Forms\Components\Tabs\Tab::make('Ketentuan Pembiayaan')
                            ->schema([
                                Forms\Components\Section::make('Pengaturan Umum')
                                    ->schema([
                                        Forms\Components\TextInput::make('min_amount')
                                            ->label('JUMLAH MINIMAL')
                                            ->numeric()
                                            ->default(0)
                                            ->placeholder(0),
                                        Forms\Components\TextInput::make('max_amount')
                                            ->label('JUMLAH MAKSIMAL')
                                            ->required()
                                            ->numeric()
                                            ->placeholder('Max Pinjaman'),
                                        Forms\Components\TextInput::make('min_rate')
                                            ->label('RATE MINIMAL (%)')
                                            ->required()
                                            ->numeric()
                                            ->default(0),
                                        Forms\Components\TextInput::make('max_rate')
                                            ->label('RATE MAKSIMAL (%)')
                                            ->required()
                                            ->numeric()
                                            ->placeholder('Max Rate'),                              
                                        Forms\Components\Select::make('tenor_months')
                                            ->label('JANGKA WAKTU (BULAN)')
                                            ->options([
                                                '6' => '6 Bulan',
                                                '12' => '12 Bulan',
                                                '24' => '24 Bulan',
                                            ])
                                            ->searchable()
                                            ->required(),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),
                            ]),
                        Forms\Components\Tabs\Tab::make('Parameter Akun Jurnal')
                            ->schema([
                                Forms\Components\Section::make('Akun Jurnal Pembiayaan')
                                    ->schema([
                                        Forms\Components\Select::make('journal_account_balance_debit_id')
                                            ->label('Pilih Akun Jurnal')
                                            ->relationship('balanceDebitAccount', 'account_name', function ($query) {
                                                return $query->where('is_active', true);
                                            })
                                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->account_number} - {$record->account_name}")
                                            ->searchable()
                                            ->required()
                                            ->preload(),
                                        Forms\Components\Select::make('journal_account_balance_credit_id')
                                            ->label('Pilih Akun Jurnal')
                                            ->relationship('balanceCreditAccount', 'account_name', function ($query) {
                                                return $query->where('is_active', true);
                                            })
                                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->account_number} - {$record->account_name}")
                                            ->searchable()
                                            ->required()
                                            ->preload(),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),
                                Forms\Components\Section::make('Akun Jurnal Kas/Bank')
                                    ->schema([
                                        Forms\Components\Select::make('journal_account_principal_debit_id')
                                            ->label('Pilih Akun Jurnal')
                                            ->relationship('principalDebitAccount', 'account_name', function ($query) {
                                                return $query->where('is_active', true);
                                            })
                                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->account_number} - {$record->account_name}")
                                            ->searchable()
                                            ->required()
                                            ->preload(),                                     
                                        Forms\Components\Select::make('journal_account_principal_credit_id')
                                            ->label('Pilih Akun Jurnal')
                                            ->relationship('principalCreditAccount', 'account_name', function ($query) {
                                                return $query->where('is_active', true);
                                            })
                                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->account_number} - {$record->account_name}")
                                            ->searchable()
                                            ->required()
                                            ->preload(),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),
                                Forms\Components\Section::make('Akun Jurnal Pendapatan')
                                    ->schema([
                                        Forms\Components\Select::make('journal_account_income_debit_id')
                                            ->label('Pilih Akun Jurnal')
                                            ->relationship('incomeDebitAccount', 'account_name', function ($query) {
                                                return $query->where('is_active', true);
                                            })
                                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->account_number} - {$record->account_name}")
                                            ->searchable()
                                            ->required()
                                            ->preload(),                                      
                                        Forms\Components\Select::make('journal_account_income_credit_id')
                                            ->label('Pilih Akun Jurnal')
                                            ->relationship('incomeCreditAccount', 'account_name', function ($query) {
                                                return $query->where('is_active', true);
                                            })
                                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->account_number} - {$record->account_name}")
                                            ->searchable()
                                            ->required()
                                            ->preload(),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),
                                Forms\Components\Section::make('Akun Jurnal Denda')
                                    ->schema([
                                        Forms\Components\Select::make('journal_account_fine_debit_id')
                                            ->label('Pilih Akun Jurnal')
                                            ->relationship('fineDebitAccount', 'account_name', function ($query) {
                                                return $query->where('is_active', true);
                                            })
                                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->account_number} - {$record->account_name}")
                                            ->searchable()
                                            ->required()
                                            ->preload(),                                      
                                        Forms\Components\Select::make('journal_account_fine_credit_id')
                                            ->label('Pilih Akun Jurnal')
                                            ->relationship('fineCreditAccount', 'account_name', function ($query) {
                                                return $query->where('is_active', true);
                                            })
                                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->account_number} - {$record->account_name}")
                                            ->searchable()
                                            ->required()
                                            ->preload(),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),
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
                    ->label('Kode')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                Tables\Columns\TextColumn::make('min_amount')
                    ->label('Jumlah Minimal')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_amount')
                    ->label('Jumlah Maksimal')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('min_rate')
                    ->label('Rate Minimal')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_rate')
                    ->label('Rate Maksimal')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('usage_purposes')
                    ->label('Tujuan Pembiayaan'),   
                Tables\Columns\TextColumn::make('contract_type')
                    ->label('Tipe Kontrak')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Mudharabah' => 'success',
                        'Musyarakah' => 'warning',
                        'Murabahah' => 'info',
                    }),
            ])
            ->filters([
                SelectFilter::make('contract_type')
                    ->options([
                        'Mudharabah' => 'Mudharabah',
                        'Murabahah' => 'Murabahah',
                        'Musyarakah' => 'Musyarakah',
                    ])
                    ->label('Jenis Kontrak'),
                SelectFilter::make('tenor_months')
                    ->options([
                        '6' => '6 Bulan',
                        '12' => '12 Bulan',
                        '24' => '24 Bulan',
                    ])
                    ->label('Jangka Waktu'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->icon('heroicon-m-eye')
                    ->iconButton(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoanProducts::route('/'),
            'create' => Pages\CreateLoanProduct::route('/create'),
            'view' => Pages\ViewLoanProduct::route('/{record}'),
        ];
    }
}