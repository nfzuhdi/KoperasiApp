<?php

namespace App\Filament\Resources\LoanProductResource\Pages;

use App\Filament\Resources\LoanProductResource;
use App\Models\LoanProduct;
use Filament\Resources\Pages\CreateRecord;

class CreateLoanProduct extends CreateRecord
{
    protected static string $resource = LoanProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Validasi data sebelum membuat record
        validator($data, LoanProduct::rules())->validate();
        
        return $data;
    }
}

