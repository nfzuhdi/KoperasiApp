<?php

namespace App\Filament\Resources\JournalAccountResource\Pages;

use App\Filament\Resources\JournalAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJournalAccount extends EditRecord
{
    protected static string $resource = JournalAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
