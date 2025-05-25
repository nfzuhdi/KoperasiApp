<?php

namespace App\Filament\Resources\SavingPaymentResource\Pages;

use App\Filament\Resources\SavingPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSavingPayments extends ListRecords
{
    protected static string $resource = SavingPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
