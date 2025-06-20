<?php

namespace App\Filament\Widgets;

use App\Models\Member;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class MembershipChart extends ChartWidget
{
    protected static ?string $heading = 'Pertumbuhan Keanggotaan';
    protected static ?int $sort = 2;
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $months = collect(range(0, 5))->map(function ($i) {
            $date = Carbon::now()->subMonths($i);

            return [
                'month' => $date->format('M'),
                'count' => Member::whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->count(),
            ];
        })->reverse()->values();

        return [
            'datasets' => [
                [
                    'label' => 'Anggota Baru',
                    'data' => $months->pluck('count')->toArray(),
                    'backgroundColor' => '#10B981',
                    'borderColor' => '#10B981',
                    'fill' => false,
                ],
            ],
            'labels' => $months->pluck('month')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }
}