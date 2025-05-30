<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BukuBesarResource\Pages;
use App\Models\BukuBesar;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BukuBesarResource extends Resource
{
    protected static ?string $model = BukuBesar::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'Buku Besar';

    protected static ?string $modelLabel = 'Buku Besar';

    protected static ?string $pluralModelLabel = 'Buku Besar';

    protected static ?string $slug = 'buku-besar';

    // Grouping menu (opsional)
    protected static ?string $navigationGroup = 'Laporan Keuangan';

    // Urutan menu
    protected static ?int $navigationSort = 2;

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
            'index' => Pages\ListBukuBesar::route('/'),
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