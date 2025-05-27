<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoanProductResource\Pages;
use App\Filament\Resources\LoanProductResource\RelationManagers;
use App\Models\LoanProduct;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LoanProductResource extends Resource
{
    protected static ?string $model = LoanProduct::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    
    protected static ?string $navigationGroup = 'Master Data';
    
    protected static ?string $PluralModelLabel = "Produk Pembiayaan";

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

                                        Forms\Components\TextInput::make('admin_fee')
                                            ->label('BIAYA ADMIN')
                                            ->numeric()
                                            ->placeholder('Masukkan biaya admin (Bila ada)'),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),
                            ]),
                        Forms\Components\Tabs\Tab::make('Parameter Akun Jurnal')
                            ->schema([
                                // Akun Jurnal Pembiayaan
                                Forms\Components\Section::make('Akun Jurnal Pembiayaan')
                                    ->description('Akun yang digunakan untuk pencatatan pembiayaan')
                                    ->schema([
                                        Forms\Components\Select::make('journal_account_balance_debit_id')
                                            ->label(function ($get) {
                                                $contractType = $get('contract_type');
                                                return "Akun Pembiayaan {$contractType}";
                                            })
                                            ->relationship('balanceDebitAccount', 'account_name', function ($query) {
                                                return $query->where('is_active', true);
                                            })
                                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->account_number} - {$record->account_name}")
                                            ->searchable()
                                            ->required()
                                            ->preload(),

                                        Forms\Components\Select::make('journal_account_balance_credit_id')
                                            ->label('Akun Kas/Bank')
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

                                // Akun Jurnal Kas/Bank
                                Forms\Components\Section::make('Akun Jurnal Kas/Bank')
                                    ->description('Akun kas atau bank yang digunakan untuk pencairan dan penerimaan pembayaran')
                                    ->schema([
                                        Forms\Components\Select::make('journal_account_principal_debit_id')
                                            ->label('Akun Kas/Bank')
                                            ->relationship('principalDebitAccount', 'account_name', function ($query) {
                                                return $query->where('is_active', true);
                                            })
                                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->account_number} - {$record->account_name}")
                                            ->searchable()
                                            ->required()
                                            ->preload(),
                                        
                                        Forms\Components\Select::make('journal_account_principal_credit_id')
                                            ->label('Akun Kas/Bank')
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

                                // Akun Jurnal Pendapatan
                                Forms\Components\Section::make('Akun Jurnal Pendapatan')
                                    ->description(function ($get) {
                                        $contractType = $get('contract_type');
                                        if ($contractType === 'Mudharabah' || $contractType === 'Musyarakah') {
                                            return "Akun pendapatan untuk bagi hasil, denda, dan biaya admin";
                                        } else {
                                            return "Akun pendapatan untuk margin, denda, dan biaya admin";
                                        }
                                    })
                                    ->schema([
                                        Forms\Components\Select::make('journal_account_income_debit_id')
                                            ->label('Akun Pendapatan')
                                            ->relationship('incomeDebitAccount', 'account_name', function ($query) {
                                                return $query->where('is_active', true);
                                            })
                                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->account_number} - {$record->account_name}")
                                            ->searchable()
                                            ->required()
                                            ->preload(),
                                        
                                        Forms\Components\Select::make('journal_account_income_credit_id')
                                            ->label('Akun Pendapatan')
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
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('min_amount')
                    ->money('IDR')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_amount')
                    ->money('IDR')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('min_rate')
                    ->suffix('%')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_rate')
                    ->suffix('%')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('usage_purposes')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('contract_type'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
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
            'index' => Pages\ListLoanProducts::route('/'),
            'create' => Pages\CreateLoanProduct::route('/create'),
            'edit' => Pages\EditLoanProduct::route('/{record}/edit'),
        ];
    }
}