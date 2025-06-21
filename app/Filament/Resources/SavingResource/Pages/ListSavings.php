<?php

namespace App\Filament\Resources\SavingResource\Pages;

use App\Filament\Resources\SavingResource;
use App\Filament\Resources\SavingResource\Widgets\SavingsStatsOverview;
use App\Filament\Resources\SavingResource\Widgets\TotalMudharabahDeposits;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use Filament\Forms;
use App\Models\Saving;
use App\Models\SavingPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class ListSavings extends ListRecords
{
    protected static string $resource = SavingResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            SavingsStatsOverview::class,
        ];
    }

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Buat Rekening Simpanan Baru')
                ->icon('heroicon-m-plus')
                ->color('primary'),
                
            Actions\Action::make('distributeProfitSharing')
                ->label('Distribusi Bagi Hasil')
                ->icon('heroicon-o-currency-dollar')
                ->modalHeading('Distribusi Bagi Hasil Mudharabah')
                ->color('success')
                ->form([
                    Forms\Components\Select::make('saving_product_id')
                        ->label('Pilih Produk Simpanan')
                        ->options(function () {
                            return \App\Models\SavingProduct::where('contract_type', 'mudharabah')
                                ->pluck('savings_product_name', 'id');
                        })
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                $product = \App\Models\SavingProduct::find($state);
                                $totalSavings = Saving::where('saving_product_id', $state)
                                    ->where('status', 'active')
                                    ->sum('balance');
                                $set('total_savings', $totalSavings);
                                $set('member_ratio', $product->member_ratio);
                            }
                        }),
                        
                    Forms\Components\Placeholder::make('total_savings_info')
                        ->label('Total Simpanan')
                        ->content(function ($get) {
                            $amount = $get('total_savings');
                            return $amount ? 'Rp ' . number_format($amount, 2) : '-';
                        }),

                    Forms\Components\Placeholder::make('profit_sharing_ratio_info')
                        ->label('Nisbah Bagi Hasil Anggota')
                        ->content(function ($get) {
                            if (!$get('saving_product_id')) return '-';
                            
                            $product = \App\Models\SavingProduct::find($get('saving_product_id'));
                            if (!$product) return '-';
                            
                            return $product->member_ratio . '%';
                        }),

                    Forms\Components\TextInput::make('profit')
                        ->label('Laba Usaha Bulan Ini')
                        ->numeric()
                        ->required()
                        ->prefix('Rp'),
                ])
                ->action(function (array $data): void {
                    try {
                        DB::beginTransaction();

                        $product = \App\Models\SavingProduct::find($data['saving_product_id']);
                        
                        // Get all active mudharabah savings for selected product
                        $mudharabahSavings = Saving::where('saving_product_id', $data['saving_product_id'])
                            ->where('status', 'active')
                            ->get();
                            
                        $totalSavings = $mudharabahSavings->sum('balance');
                        
                        if ($totalSavings <= 0) {
                            Notification::make()
                                ->warning()
                                ->title('Tidak ada simpanan aktif untuk produk ini')
                                ->send();
                            return;
                        }
                        
                        // Calculate member's portion of profit using product's ratio
                        $memberProfit = ($data['profit'] * $product->member_ratio) / 100;
                        
                        // Get current month and year
                        $currentMonth = now()->month;
                        $currentYear = now()->year;
                        
                        // Create profit sharing payments for each member
                        foreach ($mudharabahSavings as $saving) {
                            $shareRatio = $saving->balance / $totalSavings;
                            $profitShare = $memberProfit * $shareRatio;
                            
                            // Create profit sharing payment record
                            SavingPayment::create([
                                'saving_id' => $saving->id,
                                'amount' => $profitShare,
                                'payment_type' => 'profit_sharing',
                                'status' => 'pending',
                                'payment_method' => 'system',
                                'description' => sprintf(
                                    'Bagi hasil %s bulan %s - Saldo: Rp %s (%.2f%%)', 
                                    $product->savings_product_name,
                                    now()->format('F Y'),
                                    number_format($saving->balance, 2),
                                    $shareRatio * 100
                                ),
                                'month' => $currentMonth,
                                'year' => $currentYear,
                                'created_by' => auth()->id(),
                                'reference_number' => 'PS-' . now()->format('Ymd') . '-' . $saving->id,
                            ]);
                        }

                        DB::commit();
                        
                        Notification::make()
                            ->success()
                            ->title('Bagi hasil telah didistribusikan dan menunggu persetujuan kepala cabang')
                            ->send();

                    } catch (\Exception $e) {
                        DB::rollBack();
                        
                        Notification::make()
                            ->danger()
                            ->title('Error')
                            ->body($e->getMessage())
                            ->send();
                    }
                })
        ];
    }
}
