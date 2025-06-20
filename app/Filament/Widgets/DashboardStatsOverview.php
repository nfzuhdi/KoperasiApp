<?php

namespace App\Filament\Widgets;

use App\Models\Loan;
use App\Models\Member;
use App\Models\Saving;
use App\Models\LoanPayment;
use App\Models\SavingPayment;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget;

class DashboardStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        // Total anggota aktif
        $totalActiveMembers = Member::where('member_status', 'active')->count();
        
        // Total pinjaman aktif
        $totalActiveLoans = Loan::where('status', 'approved')
            ->count();
        
        // Total simpanan aktif
        $totalActiveSavings = Saving::where('status', 'active')->count();
        
        // Total nilai pinjaman aktif
        $totalLoanValue = Loan::where('status', 'approved')
            ->where('payment_status', '!=', 'paid')
            ->sum('loan_amount');
            
        // Total nilai simpanan
        $totalSavingsValue = Saving::where('status', 'active')
            ->sum('balance');
            
        // Transaksi hari ini
        $todayTransactions = LoanPayment::whereDate('created_at', now())->count() + 
                            SavingPayment::whereDate('created_at', now())->count();
        
        return [
            Stat::make('Total Anggota Aktif', $totalActiveMembers)
                ->description('Anggota dengan status aktif')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, 17]),
                
            Stat::make('Total Pinjaman Disetujui', $totalActiveLoans)
                ->description('Rekening pinjaman disetujui')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),
                
            Stat::make('Total Simpanan Aktif', $totalActiveSavings)
                ->description('Rekening simpanan aktif')
                ->descriptionIcon('heroicon-m-building-library')
                ->color('info'),
                
            Stat::make('Nilai Pinjaman', 'Rp ' . number_format($totalLoanValue, 0, ',', '.'))
                ->description('Total nilai pinjaman aktif')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
                
            Stat::make('Nilai Simpanan', 'Rp ' . number_format($totalSavingsValue, 0, ',', '.'))
                ->description('Total nilai simpanan aktif')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
                
            Stat::make('Transaksi Hari Ini', $todayTransactions)
                ->description('Pembayaran dan setoran hari ini')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('secondary'),
        ];
    }
}