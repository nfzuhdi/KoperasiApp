<?php

namespace App\Filament\Resources\MemberResource\Pages;

use App\Filament\Resources\MemberResource;
use App\Filament\Resources\MemberResource\Widgets\MemberStatsWidget;
use App\Filament\Resources\MemberResource\Widgets\MemberGrowthChart;
use App\Filament\Resources\MemberResource\Widgets\MemberStatusPieChart;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMembers extends ListRecords
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Anggota Baru') // lebih singkat & to the point
                ->icon('heroicon-m-user-plus') // ikon lebih relevan untuk anggota
                ->button() // tombol penuh, bukan hanya ikon
                ->color('success') // warna hijau = positif / tambah
                ->tooltip('Klik untuk menambahkan anggota baru') // hover info
        ];
    }

    // Method untuk menampilkan widgets di header
    protected function getHeaderWidgets(): array
    {
        return [
            MemberStatsWidget::class,
            // MemberStatusPieChart::class,
        ];
    }

}