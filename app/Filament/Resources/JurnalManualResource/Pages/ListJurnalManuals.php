<?php

namespace App\Filament\Resources\JurnalManualResource\Pages;

use App\Filament\Resources\JurnalManualResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJurnalManuals extends ListRecords
{
    protected static string $resource = JurnalManualResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
