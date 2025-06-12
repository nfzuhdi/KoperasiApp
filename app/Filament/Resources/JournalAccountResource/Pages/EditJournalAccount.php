<?php

namespace App\Filament\Resources\JournalAccountResource\Pages;

use App\Filament\Resources\JournalAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;

class EditJournalAccount extends EditRecord
{
    protected static string $resource = JournalAccountResource::class;

    // Nonaktifkan notifikasi default "Saved"
    protected function getSavedNotificationTitle(): ?string
    {
        return null;
    }

    // Nonaktifkan notifikasi default "Deleted"
    protected function getDeletedNotificationTitle(): ?string
    {
        return null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function () {
                    // Cek apakah akun jurnal masih digunakan dalam transaksi
                    $jurnalUmumCount = \App\Models\JurnalUmum::where('akun_id', $this->record->id)->count();
                    
                    // Cek apakah akun jurnal digunakan sebagai parent account
                    $childAccountsCount = \App\Models\JournalAccount::where('parent_account_id', $this->record->id)->count();
                    
                    // Cek apakah akun jurnal digunakan dalam produk simpanan
                    $savingProductsCount = \App\Models\SavingProduct::where('journal_account_deposit_debit_id', $this->record->id)
                        ->orWhere('journal_account_deposit_credit_id', $this->record->id)
                        ->orWhere('journal_account_withdrawal_debit_id', $this->record->id)
                        ->orWhere('journal_account_withdrawal_credit_id', $this->record->id)
                        ->orWhere('journal_account_penalty_debit_id', $this->record->id)
                        ->orWhere('journal_account_penalty_credit_id', $this->record->id)
                        ->count();
                    
                    // Cek apakah akun jurnal digunakan dalam produk pembiayaan
                    $loanProductsCount = \App\Models\LoanProduct::where('journal_account_balance_debit_id', $this->record->id)
                        ->orWhere('journal_account_balance_credit_id', $this->record->id)
                        ->count();
                    
                    if ($jurnalUmumCount > 0 || $childAccountsCount > 0 || $savingProductsCount > 0 || $loanProductsCount > 0) {
                        Notification::make()
                            ->title('Akun jurnal tidak dapat dihapus karena masih digunakan')
                            ->body('Akun ini digunakan dalam transaksi, sebagai parent account, atau dalam konfigurasi produk.')
                            ->danger()
                            ->send();
                        
                        $this->halt();
                    }
                })
                ->after(function () {
                    // Tampilkan notifikasi sukses setelah berhasil menghapus akun
                    Notification::make()
                        ->title('Akun jurnal berhasil dihapus')
                        ->success()
                        ->send();
                }),
        ];
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

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return parent::handleRecordUpdate($record, $data);
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
                
                throw $exception;
            }
            
            throw $exception;
        }
    }

    protected function afterSave(): void
    {
        // Tampilkan notifikasi sukses setelah berhasil mengedit akun
        Notification::make()
            ->title('Akun jurnal berhasil diperbarui')
            ->success()
            ->send();
    }
}
