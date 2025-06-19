<?php

namespace App\Filament\Resources\SavingResource\Widgets;

use App\Models\Saving;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SavingsStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Active Savings', Saving::where('status', 'active')->count())
                ->description('Active saving accounts')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('Aktivitas Hari Ini', Saving::whereDate('created_at', now())->count())
                ->description('Pembuatan Rekening Simpanan Hari Ini')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('secondary'),

            Stat::make('Pending Applications', Saving::where('status', 'pending')->count())
                ->description('Waiting for approval')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}