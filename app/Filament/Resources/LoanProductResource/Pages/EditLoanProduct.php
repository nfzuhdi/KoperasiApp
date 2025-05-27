<?php

namespace App\Filament\Resources\LoanProductResource\Pages;

use App\Filament\Resources\LoanProductResource;
use App\Models\LoanProduct;
use Filament\Resources\Pages\EditRecord;

class EditLoanProduct extends EditRecord
{
    protected static string $resource = LoanProductResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Validasi data sebelum menyimpan record
        validator($data, LoanProduct::rules($this->record->id))->validate();
        
        return $data;
    }
}