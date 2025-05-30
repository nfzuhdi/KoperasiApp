<?php

namespace App\Filament\Resources\JurnalUmumResource\Pages;

use App\Filament\Resources\JurnalUmumResource;
use Filament\Resources\Pages\Page;
use App\Models\JurnalUmum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Carbon\Carbon;

class ListJurnalUmum extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = JurnalUmumResource::class;
    protected static string $view = 'filament.resources.jurnal-umum-resource.pages.list-jurnal-umum';
    
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'dari_tanggal' => now()->startOfMonth(),
            'sampai_tanggal' => now()->endOfMonth(),
        ]);
    }

    public function form(Form $form): Form 
    {
        return $form
            ->schema([
                DatePicker::make('dari_tanggal')
                    ->label('Dari Tanggal')
                    ->required()
                    ->native(false)
                    ->live(),
                DatePicker::make('sampai_tanggal')
                    ->label('Sampai Tanggal') 
                    ->required()
                    ->native(false)
                    ->live(),
            ])
            ->columns(2)
            ->statePath('data');
    }

    protected function getViewData(): array
    {
        $dari_tanggal = Carbon::parse($this->data['dari_tanggal'] ?? now()->startOfMonth());
        $sampai_tanggal = Carbon::parse($this->data['sampai_tanggal'] ?? now()->endOfMonth());

        // Ambil data tanpa grouping terlebih dahulu
        $records = JurnalUmum::query()
            ->with('akun')
            ->whereBetween('tanggal_bayar', [$dari_tanggal, $sampai_tanggal])
            ->orderBy('tanggal_bayar')
            ->orderBy('no_transaksi')
            ->get();

        return [
            'records' => $records,
            'periode' => $dari_tanggal->format('d/m/Y') . ' - ' . $sampai_tanggal->format('d/m/Y'),
        ];
    }
}