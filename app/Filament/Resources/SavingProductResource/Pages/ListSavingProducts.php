<?php

namespace App\Filament\Resources\SavingProductResource\Pages;

use App\Filament\Resources\SavingProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSavingProducts extends ListRecords
{
    protected static string $resource = SavingProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Produk Simpanan Baru')
        ];
    }
}
