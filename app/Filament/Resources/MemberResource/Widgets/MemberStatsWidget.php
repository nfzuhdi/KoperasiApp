<?php

namespace App\Filament\Resources\MemberResource\Widgets;

use App\Models\Member;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MemberStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Anggota', Member::count())
                ->description('Semua anggota terdaftar')
                ->descriptionIcon('heroicon-m-users')
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, 17]) // Optional chart data
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),

            Stat::make('Anggota Aktif', Member::where('member_status', 'active')->count())
                ->description('Anggota dengan status aktif')
                ->descriptionIcon('heroicon-m-user-circle')
                ->color('success')
                ->chart([15, 4, 10, 2, 12, 4, 12]),

            Stat::make('Anggota Bermasalah', Member::where('member_status', 'delinquent')->count())
                ->description('Membutuhkan perhatian')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('danger')
                ->chart([7, 3, 4, 5, 6, 3, 5]),

            Stat::make('Anggota Baru', Member::where('created_at', '>=', now()->subDays(30))->count())
                ->description('30 hari terakhir')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('info')
                ->chart([2, 3, 4, 3, 5, 4, 6]),
        ];
    }

    protected function getColumns(): int
    {
        return 4; // Jumlah kolom untuk layout widget
    }
}