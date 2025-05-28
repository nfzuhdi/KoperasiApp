<?php

namespace App\Filament\Resources\LoanPaymentResource\Pages;

use App\Filament\Resources\LoanPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateLoanPayment extends CreateRecord
{
    protected static string $resource = LoanPaymentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-populate month and year from current date (created_at)
        $currentDate = \Carbon\Carbon::now();
        $data['month'] = $currentDate->month;
        $data['year'] = $currentDate->year;

        return $data;
    }
}

