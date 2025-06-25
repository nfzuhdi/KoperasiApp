<?php

namespace App\Filament\Resources\JurnalManualResource\Pages;

use App\Filament\Resources\JurnalManualResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditJurnalManual extends EditRecord
{
    protected static string $resource = JurnalManualResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->status === 'rejected'),
        ];
    }

    public function mount($record): void
    {
        parent::mount($record);

        // Redirect ke halaman index jika status masih pending
        if ($this->record->status === 'pending') {
            Notification::make()
                ->title('Jurnal dengan status pending tidak dapat diedit')
                ->warning()
                ->send();

            $this->redirect(JurnalManualResource::getUrl('index'));
        }

        // Redirect ke halaman index jika status sudah approved
        if ($this->record->status === 'approved') {
            Notification::make()
                ->title('Jurnal yang sudah disetujui tidak dapat diedit')
                ->warning()
                ->send();

            $this->redirect(JurnalManualResource::getUrl('index'));
        }
    }
}
