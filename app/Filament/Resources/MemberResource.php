<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MemberResource\Pages;
use App\Models\Member;
use Filament\Forms;
use Filament\Forms\Components\Actions;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\Village;
use Filament\Tables\Enums\ActionsPosition;
class MemberResource extends Resource
{
    protected static ?string $model = Member::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Manage Members';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('member_id')->searchable(),
                Tables\Columns\TextColumn::make('full_name')->searchable(),
                Tables\Columns\TextColumn::make('telephone_number')->searchable(),
                Tables\Columns\TextColumn::make('member_status')->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make('view')
                    ->icon('heroicon-m-eye')
                    ->iconButton(),
                Tables\Actions\EditAction::make('edit')
                    ->icon('heroicon-m-pencil-square')
                    ->iconButton(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMembers::route('/'),
            'create' => Pages\CreateMember::route('/create'),
            'edit' => Pages\EditMember::route('/{record}/edit'),
            'view' => Pages\ViewMember::route('/{record}'),
        ];
    }
}