<?php

namespace App\Filament\Resources\SavingPaymentResource\Pages;

use App\Filament\Resources\SavingPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSavingPayment extends EditRecord
{
    protected static string $resource = SavingPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
