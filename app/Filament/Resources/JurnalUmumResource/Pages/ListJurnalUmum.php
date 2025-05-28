<?php

namespace App\Filament\Resources\JurnalUmumResource\Pages;

use App\Filament\Resources\JurnalUmumResource;
use Filament\Resources\Pages\ListRecords;

class ListJurnalUmum extends ListRecords
{
    protected static string $resource = JurnalUmumResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}