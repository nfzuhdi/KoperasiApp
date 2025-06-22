<?php

namespace App\Filament\Widgets;

use App\Models\JournalAccount;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class FinancialSummaryWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        // Ambil data bulan berjalan
        $currentMonth = now()->month;
        $currentYear = now()->year;
        
        // Hitung total aktiva
        $totalAktiva = JournalAccount::whereIn('account_type', ['asset'])
            ->sum('balance');
            
        // Hitung total kewajiban
        $totalKewajiban = JournalAccount::whereIn('account_type', ['liability'])
            ->sum('balance');
            
        // Hitung total ekuitas
        $totalEkuitas = JournalAccount::whereIn('account_type', ['equity'])
            ->sum('balance');
            
        // Cek keseimbangan
        $isBalanced = abs($totalAktiva - ($totalKewajiban + $totalEkuitas)) < 0.01;
        
        return [
            Stat::make('Total Aset', 'Rp ' . number_format($totalAktiva, 0, ',', '.'))
                ->description('Posisi bulan ' . now()->locale('id')->monthName)
                ->descriptionIcon('heroicon-m-building-library')
                ->color('success'),
                
            Stat::make('Total Kewajiban', 'Rp ' . number_format($totalKewajiban, 0, ',', '.'))
                ->description('Posisi bulan ' . now()->locale('id')->monthName)
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),
                
            Stat::make('Total Ekuitas', 'Rp ' . number_format($totalEkuitas, 0, ',', '.'))
                ->description('Posisi bulan ' . now()->locale('id')->monthName)
                ->descriptionIcon('heroicon-m-scale')
                ->color('info'),
                
            Stat::make('Status Neraca', $isBalanced ? 'SEIMBANG' : 'TIDAK SEIMBANG')
                ->description($isBalanced ? 'Neraca dalam kondisi seimbang' : 'Perlu verifikasi data')
                ->descriptionIcon($isBalanced ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-circle')
                ->color($isBalanced ? 'success' : 'danger'),
        ];
    }
}