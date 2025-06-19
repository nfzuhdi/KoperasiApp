<?php

namespace App\Filament\Resources\JurnalManualResource\Pages;

use App\Filament\Resources\JurnalManualResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateJurnalManual extends CreateRecord
{
    protected static string $resource = JurnalManualResource::class;
    protected static ?string $title = 'Buat Jurnal Manual';
}
