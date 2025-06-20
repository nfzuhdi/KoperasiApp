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
                ->visible(fn () => $this->record->status === 'pending'),
        ];
    }
    
    public function mount($record): void
    {
        parent::mount($record);
        
        // Redirect ke halaman view jika status sudah approved
        if ($this->record->status === 'approved') {
            Notification::make()
                ->title('Jurnal yang sudah disetujui tidak dapat diedit')
                ->warning()
                ->send();
                
            $this->redirect(JurnalManualResource::getUrl('index'));
        }
    }
}
