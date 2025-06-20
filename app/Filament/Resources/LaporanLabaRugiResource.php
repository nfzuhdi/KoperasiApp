<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LaporanLabaRugiResource\Pages;
use App\Models\JournalAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LaporanLabaRugiResource extends Resource
{
    protected static ?string $model = JournalAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Laporan Laba Rugi';

    protected static ?string $modelLabel = 'Laporan Laba Rugi';

    protected static ?string $pluralModelLabel = 'Laporan Laba Rugi';

    protected static ?string $slug = 'laporan-laba-rugi';

    // Grouping menu
    protected static ?string $navigationGroup = 'Laporan Keuangan';

    // Urutan menu
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Form tidak diperlukan karena ini adalah laporan
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Table tidak diperlukan karena ini menggunakan custom page
            ])
            ->filters([
                //
            ])
            ->actions([
                // Actions tidak diperlukan
            ])
            ->bulkActions([
                // Bulk actions tidak diperlukan
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
            'index' => Pages\ListLaporanLabaRugi::route('/'),
        ];
    }

    // Disable create, edit, delete karena ini adalah laporan
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
