<?php

namespace App\Filament\Resources\LoanPaymentResource\Pages;

use App\Filament\Resources\LoanPaymentResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateLoanPayment extends CreateRecord
{
    protected static string $resource = LoanPaymentResource::class;

    protected static ?string $title = 'Buat Pembayaran Pinjaman';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $currentDate = \Carbon\Carbon::now();
        $data['month'] = $currentDate->month;
        $data['year'] = $currentDate->year;

        return $data;
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Pembayaran Pinjaman berhasil dibuat!')
            ->success();
    }
}