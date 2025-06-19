<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SavingResource\Pages;
use App\Filament\Resources\SavingResource\RelationManagers;
use App\Models\Saving;
use App\Models\SavingProduct;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

class SavingResource extends Resource
{
    protected static ?string $model = Saving::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Loans & Savings';

    protected static ?string $pluralLabel = 'Rekening Simpanan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Buat Rekening Baru')
                    ->description('Pilih Anggota dan Produk yang ingin dipilih')
                    ->schema([
                        Forms\Components\Select::make('member_id')
                            ->label('Pilih Anggota')
                            ->relationship('member', 'full_name')
                            ->getOptionLabelFromRecordUsing(
                                fn ($record) => "{$record->full_name} ({$record->member_id})"
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->hint('Cari berdasarkan nama atau ID anggota')
                            ->hintColor('info'),

                        Forms\Components\Select::make('saving_product_id')
                            ->label('Pilih Produk Simpanan')
                            ->relationship('savingProduct', 'savings_product_name')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required()
                            ->afterStateUpdated(fn (callable $set) => $set('product_preview_visible', true))
                            ->hint('Pilih jenis produk simpanan yang tersedia')
                            ->hintColor('info'),
                    ]),

                // Product Preview Section
                Section::make('Product Details')
                    ->schema([
                        Forms\Components\Placeholder::make('min_deposit')
                            ->label('Min Deposit')
                            ->content(function ($get) {
                                $product = SavingProduct::find($get('saving_product_id'));
                                return $product ? number_format($product->min_deposit, 2) : '-';
                            }),
                        Forms\Components\Placeholder::make('max_deposit')
                            ->label('Max Deposit')
                            ->content(function ($get) {
                                $product = SavingProduct::find($get('saving_product_id'));
                                return $product && $product->max_deposit ? number_format($product->max_deposit, 2) : '-';
                            }),
                        Forms\Components\Placeholder::make('admin_fee')
                            ->label('Admin Fee')
                            ->content(function ($get) {
                                $product = SavingProduct::find($get('saving_product_id'));
                                return $product && $product->admin_fee ? number_format($product->admin_fee, 2) : '-';
                            }),
                        Forms\Components\Placeholder::make('is_withdrawable')
                            ->label('Withdrawable')
                            ->content(function ($get) {
                                $product = SavingProduct::find($get('saving_product_id'));
                                return $product ? ($product->is_withdrawable ? 'Yes' : 'No') : '-';
                            }),
                        Forms\Components\Placeholder::make('is_mandatory_routine')
                            ->label('Mandatory Routine')
                            ->content(function ($get) {
                                $product = SavingProduct::find($get('saving_product_id'));
                                return $product ? ($product->is_mandatory_routine ? 'Yes' : 'No') : '-';
                            }),
                        Forms\Components\Placeholder::make('deposit_period')
                            ->label('Deposit Period')
                            ->content(function ($get) {
                                $product = SavingProduct::find($get('saving_product_id'));
                                return $product && $product->deposit_period ? ucfirst($product->deposit_period) : '-';
                            }),
                        Forms\Components\Placeholder::make('contract_type')
                            ->label('Contract Type')
                            ->content(function ($get) {
                                $product = SavingProduct::find($get('saving_product_id'));
                                return $product ? $product->contract_type : '-';
                            })
                            ->visible(function ($get) {
                                $product = SavingProduct::find($get('saving_product_id'));
                                return $product && in_array($product->savings_type, ['deposit', 'time_deposit']);
                            }),
                    ])
                    ->visible(fn ($get) => $get('product_preview_visible'))
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('account_number')
                    ->label('ID Anggota')
                    ->searchable(),
                Tables\Columns\TextColumn::make('member.full_name')
                    ->label('Nama Anggota')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('savingProduct.savings_product_name')
                    ->label('Produk Simpanan')
                    ->sortable(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Saldo Rekening')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status Anggota')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'pending' => 'warning',
                        'closed' => 'danger',
                        'blocked' => 'danger',
                        'declined' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('next_due_date')
                    ->date()
                    ->sortable()
                    ->visible(function ($livewire, $state, $column) {
                        // Get the record from the column
                        $record = $column?->getRecord();
                        
                        // Only show if record exists and has a mandatory routine saving product
                        return $record && 
                               $record->savingProduct && 
                               $record->savingProduct->is_mandatory_routine;
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'pending' => 'Pending',
                        'closed' => 'Closed',
                        'blocked' => 'Blocked',
                        'declined' => 'Declined',
                    ])
                    ->label('Status')
                    ->multiple()
                    ->indicator('Status'),

                Tables\Filters\SelectFilter::make('saving_product_id')
                    ->relationship('savingProduct', 'savings_product_name')
                    ->label('Product Type')
                    ->preload()
                    ->searchable()
                    ->indicator('Product'),

                Tables\Filters\SelectFilter::make('created_by')
                    ->relationship('creator', 'name')
                    ->label('Created By')
                    ->preload()
                    ->searchable()
                    ->indicator('Creator'),
], 
    layout: \Filament\Tables\Enums\FiltersLayout::Modal
)
->filtersFormColumns(3)
->filtersTriggerAction(
    fn (\Filament\Tables\Actions\Action $action) => $action
        ->button()
        ->label('Filter')
        ->icon('heroicon-m-funnel')
)
            ->actions([
                Tables\Actions\ViewAction::make('view')
                    ->icon('heroicon-m-eye')
                    ->iconButton(),
                Action::make('approve')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->iconButton()
                    ->visible(fn (Saving $record) => $record->status === 'pending' && auth()->user()->hasRole('kepala_cabang'))
                    ->requiresConfirmation()
                    ->modalHeading('Approve Saving')
                    ->modalDescription('Are you sure you want to approve this saving? The status will be changed to active.')
                    ->action(function (Saving $record) {
                        $record->status = 'active';
                        $record->reviewed_by = auth()->id();
                        $record->save();
                        
                        Notification::make()
                            ->title('Saving approved successfully')
                            ->success()
                            ->send();
                    }),
                Action::make('reject')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->iconButton()
                    ->visible(fn (Saving $record) => $record->status === 'pending' && auth()->user()->hasRole('kepala_cabang'))
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('rejected_reason')
                            ->label('Reason for Rejection')
                            ->required(),
                    ])
                    ->action(function (Saving $record, array $data) {
                        $record->status = 'declined';
                        $record->reviewed_by = auth()->id();
                        $record->rejected_reason = $data['rejected_reason'];
                        $record->save();
                        
                        Notification::make()
                            ->title('Saving rejected')
                            ->success()
                            ->send();
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
            RelationManagers\SavingPaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSavings::route('/'),
            'create' => Pages\CreateSaving::route('/create'),
            // 'edit' => Pages\EditSaving::route('/{record}/edit'),
            'view' => Pages\ViewSaving::route('/{record}'),
        ];
    }

    // Add these methods after getPages()
    public static function getNavigationBadge(): ?string
    {
        // Only show badge for kepala_cabang role
        if (auth()->user()->hasRole('kepala_cabang')) {
            $pendingCount = Saving::where('status', 'pending')->count();
            return $pendingCount > 0 ? (string) $pendingCount : null;
        }
        
        return null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        if (auth()->user()->hasRole('kepala_cabang')) {
            return 'Pending Rekening Simpanan';
        }
        
        return null;
    }
}