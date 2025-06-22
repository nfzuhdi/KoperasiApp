<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DashboardStatsOverview;
use App\Filament\Widgets\MembershipChart;
use App\Filament\Widgets\LoanSavingChart;
use App\Filament\Widgets\LatestTransactions;
use App\Filament\Widgets\FinancialSummaryWidget;
use App\Filament\Widgets\UpcomingDueLoans;
use App\Filament\Widgets\OverdueMandatorySavings;
use App\Filament\Widgets\CalendarWidget;
use Filament\Pages\Page;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static string $view = 'filament.pages.dashboard';
    protected static ?string $title = 'Dashboard';
    protected static ?int $navigationSort = -2;

    public function getWidgets(): array
    {
        return [
            DashboardStatsOverview::class,
            FinancialSummaryWidget::class,
            MembershipChart::class,
            CalendarWidget::class,
        ];
    }
}
