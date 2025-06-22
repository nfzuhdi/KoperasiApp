<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class CalendarWidget extends Widget
{
    protected static string $view = 'filament.widgets.calendar-widget';
    protected static ?int $sort = 7;
    
    public function getViewData(): array
    {
        $today = Carbon::today();
        $startOfMonth = $today->copy()->startOfMonth();
        $endOfMonth = $today->copy()->endOfMonth();
        
        // Mendapatkan tanggal awal dan akhir untuk tampilan kalender
        $startDate = $startOfMonth->copy()->startOfWeek(Carbon::SUNDAY);
        $endDate = $endOfMonth->copy()->endOfWeek(Carbon::SATURDAY);
        
        $dates = collect();
        $currentDate = $startDate->copy();
        
        while ($currentDate <= $endDate) {
            $dates->push([
                'date' => $currentDate->copy(),
                'isCurrentMonth' => $currentDate->month === $today->month,
                'isToday' => $currentDate->isToday(),
            ]);
            
            $currentDate->addDay();
        }
        
        return [
            'currentMonth' => $today->format('F Y'),
            'dates' => $dates,
            'weeks' => $dates->chunk(7),
        ];
    }
}