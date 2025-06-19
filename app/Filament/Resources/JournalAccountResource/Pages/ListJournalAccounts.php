<?php

namespace App\Filament\Resources\JournalAccountResource\Pages;

use App\Filament\Resources\JournalAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJournalAccounts extends ListRecords
{
    protected static string $resource = JournalAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
            ->label('Tambah Akun Jurnal')
        ];
    }
}


