<?php

namespace App\Filament\Resources\SavingProductResource\Pages;

use App\Filament\Resources\SavingProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSavingProduct extends EditRecord
{
    protected static string $resource = SavingProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

