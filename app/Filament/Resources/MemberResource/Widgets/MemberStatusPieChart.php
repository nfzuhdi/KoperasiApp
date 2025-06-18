<?php

namespace App\Filament\Resources\MemberResource\Widgets;

use App\Models\Member;
use Filament\Widgets\ChartWidget;

class MemberStatusPieChart extends ChartWidget
{
    protected static ?string $heading = 'Distribusi Status Anggota';
    protected static ?string $description = 'Persentase status keanggotaan';
    // Membuat widget bisa di-collapse
    protected static bool $isCollapsible = true;
    
    // Set default state (true = collapsed, false = expanded)
    protected static bool $isCollapsed = false;
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $statusCounts = Member::selectRaw('member_status, COUNT(*) as count')
            ->groupBy('member_status')
            ->pluck('count', 'member_status')
            ->toArray();

        $labels = [];
        $data = [];
        $colors = [];

        foreach ($statusCounts as $status => $count) {
            $labels[] = match ($status) {
                'pending' => 'Dalam Proses',
                'active' => 'Aktif',
                'delinquent' => 'Bermasalah',
                'terminated' => 'Keluar',
                default => $status,
            };
            $data[] = $count;
            $colors[] = match ($status) {
                'active' => '#10b981',
                'terminated' => '#ef4444',
                'pending' => '#f59e0b',
                'delinquent' => '#f97316',
                default => '#6b7280',
            };
        }

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderColor' => $colors,
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}