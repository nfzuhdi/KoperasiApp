<?php

namespace App\Filament\Resources\LoanProductResource\Pages;

use App\Filament\Resources\LoanProductResource;
use App\Models\LoanProduct;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateLoanProduct extends CreateRecord
{
    protected static string $resource = LoanProductResource::class;

    protected static ?string $title = 'Buat Produk Pinjaman';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Validasi data sebelum membuat record
        validator($data, LoanProduct::rules())->validate();
        
        return $data;
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Produk Pinjaman berhasil dibuat!')
            ->success();
    }
}