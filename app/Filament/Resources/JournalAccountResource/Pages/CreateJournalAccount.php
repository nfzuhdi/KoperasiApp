<?php

namespace App\Filament\Resources\JournalAccountResource\Pages;

use App\Filament\Resources\JournalAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;

class CreateJournalAccount extends CreateRecord
{
    protected static string $resource = JournalAccountResource::class;

    // Nonaktifkan notifikasi default "Created"
    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function onValidationError(ValidationException $exception): void
    {
        Notification::make()
            ->title('Data tidak valid. Periksa kembali input Anda.')
            ->danger()
            ->send();
        
        parent::onValidationError($exception);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Pastikan data valid sebelum disimpan
        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return parent::handleRecordCreation($data);
        } catch (QueryException $exception) {
            if (str_contains($exception->getMessage(), 'Duplicate entry') || 
                str_contains($exception->getMessage(), 'UNIQUE constraint failed') ||
                str_contains($exception->getMessage(), 'Integrity constraint violation')) {
                
                Notification::make()
                    ->title('Data tidak valid. Periksa kembali input Anda.')
                    ->danger()
                    ->send();
                
                // Ekstrak nomor akun dari pesan error
                preg_match("/Duplicate entry '([^']+)'/", $exception->getMessage(), $matches);
                $accountNumber = $matches[1] ?? 'yang dimasukkan';
                
                // Tambahkan error pada form
                $this->addError('account_number', "Nomor akun {$accountNumber} sudah digunakan.");
                
                throw $exception; // Throw exception kembali agar Filament dapat menanganinya
            }
            
            throw $exception;
        }
    }

    protected function afterCreate(): void
    {
        // Tampilkan notifikasi sukses setelah berhasil membuat akun
        Notification::make()
            ->title('Akun jurnal berhasil dibuat')
            ->success()
            ->send();
    }
}
