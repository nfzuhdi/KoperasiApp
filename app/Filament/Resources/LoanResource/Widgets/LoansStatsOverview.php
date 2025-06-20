<?php

namespace App\Filament\Resources\LoanResource\Widgets;

use App\Models\Loan;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LoansStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Pinjaman Aktif', Loan::where('status', 'approved')->count())
                ->description('Rekening pinjaman aktif')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('Aktivitas Hari Ini', Loan::whereDate('created_at', now())->count())
                ->description('Pembuatan Rekening Pinjaman Hari Ini')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('secondary'),

            Stat::make('Menunggu Persetujuan', Loan::where('status', 'pending')->count())
                ->description('Menunggu persetujuan')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}