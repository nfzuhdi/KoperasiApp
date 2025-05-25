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
                                        // Fields for all contract types
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
                                        
                                        // Fields only for non-Murabahah
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
                                
                                        // Common field for all contract types
                                        Forms\Components\Select::make('tenor_months')
                                            ->label('JANGKA WAKTU (BULAN)')
                                            ->options([
                                                '6' => '6 Bulan',
                                                '12' => '12 Bulan',
                                                '24' => '24 Bulan',
                                            ])
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
                                Forms\Components\Section::make('Pengaturan Akun Jurnal')
                                    ->schema([
                                        Forms\Components\Select::make('journal_account_balance_id')
                                            ->label('Akun Saldo Pembiayaan')
                                            ->relationship('balanceAccount', 'account_name', function ($query) {
                                                return $query->where('is_active', true);
                                            })
                                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->account_number} - {$record->account_name}")
                                            ->searchable()
                                            ->required()
                                            ->preload(),
                                        
                                        Forms\Components\Select::make('journal_account_income_id')
                                            ->label('Akun Pendapatan')
                                            ->relationship('incomeAccount', 'account_name', function ($query) {
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
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('contract_type'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
