<?php

namespace App\Filament\Resources\SavingResource\Pages;

use App\Filament\Resources\SavingResource;
use App\Filament\Resources\SavingResource\Widgets\SavingsStatsOverview;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListSavings extends ListRecords
{
    protected static string $resource = SavingResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            SavingsStatsOverview::class,
        ];
    }

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Buat Rekening Simpanan Baru')
                ->icon('heroicon-m-plus')
                ->color('primary'),
        ];
    }
}
