<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JournalAccountResource\Pages;
use App\Models\JournalAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Enums\ActionsPosition;
use App\Filament\Resources\JournalAccountResource\Relations;

class JournalAccountResource extends Resource
{
    protected static ?string $model = JournalAccount::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?int $navigationSort = 2;
    protected static ?string $pluralLabel = 'Akun Jurnal Koperasi';
    protected static ?string $navigationGroup = 'Data Master';

    // Kustomisasi pesan kosong untuk tabel
    protected static ?string $emptyStateMessage = 'Tidak ada data akun jurnal yang ditemukan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Section::make('Struktur Akun')
                                    ->schema([
                                        Forms\Components\Toggle::make('is_sub_account')
                                            ->label('Sub Akun')
                                            ->live()
                                            ->default(false),
                                        Forms\Components\Select::make('parent_account_id')
                                            ->relationship('parentAccount', 'account_name', function ($query) {
                                                return $query->where('is_active', true)
                                                            ->where('is_sub_account', false); // Only main accounts can be parents
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->label('Akun Sub')
                                            ->visible(fn (callable $get) => $get('is_sub_account'))
                                            ->live()
                                            ->afterStateUpdated(function (callable $set, $state) {
                                                if ($state) {
                                                    $parentAccount = JournalAccount::find($state);
                                                    if ($parentAccount) {
                                                        $prefix = $parentAccount->account_number . '.';
                                                        $set('account_number', $prefix);
                                                        $set('account_type', $parentAccount->account_type);
                                                        $set('account_position', $parentAccount->account_position);
                                                    }
                                                }
                                            }),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make('Informasi Akun')
                                    ->schema([
                                        Forms\Components\TextInput::make('account_number')
                                            ->required()
                                            ->maxLength(255)
                                            ->label('Nomor Akun')
                                            ->unique(table: 'journal_accounts', ignoreRecord: true)
                                            ->validationMessages([
                                                'unique' => 'Nomor akun ini sudah digunakan. Silakan gunakan nomor akun lain.',
                                            ]),
                                        Forms\Components\TextInput::make('account_name')
                                            ->required()
                                            ->maxLength(255)
                                            ->label('Nama Akun'),
                                        Forms\Components\Select::make('account_type')
                                            ->required()
                                            ->options([
                                                'asset' => 'Asset',
                                                'liability' => 'Liability',
                                                'equity' => 'Equity',
                                                'income' => 'Income',
                                                'expense' => 'Expense',
                                            ])
                                            ->label('Tipe Akun')
                                            ->visible(fn (callable $get) => !$get('is_sub_account')),
                                        Forms\Components\Select::make('account_position')
                                            ->required()
                                            ->options([
                                                'debit' => 'Debit',
                                                'credit' => 'Credit',
                                            ])
                                            ->label('Posisi Normal')
                                            ->visible(fn (callable $get) => !$get('is_sub_account')),
                                        Forms\Components\Hidden::make('account_type')
                                            ->visible(fn (callable $get) => $get('is_sub_account')),
                                        Forms\Components\Hidden::make('account_position')
                                            ->visible(fn (callable $get) => $get('is_sub_account')),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make('Informasi Saldo')
                                    ->schema([
                                        Forms\Components\TextInput::make('opening_balance')
                                            ->required()
                                            ->numeric()
                                            ->default(0.00)
                                            ->label('Saldo Awal'),
                                        Forms\Components\DatePicker::make('opening_balance_date')
                                            ->label('Tanggal Saldo Awal'),
                                    ])
                                    ->columns(2),
                            ])
                            ->columnSpan(2),

                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Section::make('Status')
                                    ->schema([
                                        Forms\Components\Toggle::make('is_active')
                                            ->required()
                                            ->default(true)
                                            ->label('Aktif')
                                            ->helperText('Enable or disable this account'),
                                    ])
                            ])
                            ->columnSpan(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('account_number')
                    ->searchable()
                    ->label('Nomor Akun')
                    ->sortable(),
                Tables\Columns\TextColumn::make('account_name')
                    ->searchable()
                    ->label('Nama Akun')
                    ->sortable()
                    ->formatStateUsing(function ($record) {
                        if ($record->is_sub_account) {
                            return '↳ ' . $record->account_name;
                        }
                        return $record->account_name;
                    }),
                Tables\Columns\TextColumn::make('account_type')
                    ->badge()
                    ->label('Tipe Akun')
                    ->color(fn (string $state): string => match ($state) {
                        'asset' => 'success',
                        'liability' => 'danger',
                        'equity' => 'warning',
                        'income' => 'info',
                        'expense' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('account_position')
                    ->badge()
                    ->label('Posisi Akun')
                    ->color(fn (string $state): string => match ($state) {
                        'debit' => 'success',
                        'credit' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('balance')
                    ->money('IDR')
                    ->badge()
                    ->label('Saldo'),
                Tables\Columns\IconColumn::make('is_sub_account')
                    ->boolean()
                    ->label('Sub Akun'),
                Tables\Columns\TextColumn::make('parentAccount.account_name')
                    ->label('Parent Account')
                    ->placeholder('N/A')
                    ->label('Akun Sub'),
                Tables\Columns\TextColumn::make('opening_balance')
                    ->money('IDR')
                    ->sortable()
                    ->label('Saldo Awal'),
                Tables\Columns\TextColumn::make('opening_balance_date')
                    ->date()
                    ->sortable()
                    ->label('Tanggal Saldo Awal'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Aktif'),
            ])
            ->defaultSort('account_number')
            ->filters([
                Tables\Filters\SelectFilter::make('account_type')
                    ->options([
                        'asset' => 'Asset',
                        'liability' => 'Liability',
                        'equity' => 'Equity',
                        'income' => 'Income',
                        'expense' => 'Expense',
                    ])
                    ->label('Tipe Akun'),
                Tables\Filters\SelectFilter::make('account_position')
                    ->options([
                        'debit' => 'Debit',
                        'credit' => 'Credit',
                    ])
                    ->label('Posisi Normal'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->icon('heroicon-m-eye')
                    ->iconButton(),
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-m-pencil-square')
                    ->iconButton(),
            ], position: ActionsPosition::BeforeColumns)
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ])
            ->emptyStateHeading('Tidak ada data akun jurnal yang ditemukan');
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
            'index' => Pages\ListJournalAccounts::route('/'),
            'create' => Pages\CreateJournalAccount::route('/create'),
            'edit' => Pages\EditJournalAccount::route('/{record}/edit'),
        ];
    }
}