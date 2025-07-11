<?php

namespace App\Filament\Resources\LoanResource\Pages;

use App\Filament\Resources\LoanResource;
use App\Filament\Resources\LoanPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Support\Colors\Color;
use Filament\Infolists\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use App\Models\JournalAccount;
use Filament\Infolists\Components\RepeatableEntry;

class ViewLoan extends ViewRecord
{
    protected static string $resource = LoanResource::class;

    protected static ?string $title = 'Detail Pinjaman';

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Tabs::make('LoanTabs')
                    ->tabs([
                        Tabs\Tab::make('Informasi Pembiayaan')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        Section::make('Informasi Pinjaman')
                                            ->schema([
                                                TextEntry::make('account_number')
                                                    ->label('NOMOR REKENING'),
                                                    
                                                TextEntry::make('member.full_name')
                                                    ->label('NAMA ANGGOTA'),
                                        
                                                TextEntry::make('loanProduct.name')
                                                    ->label('NAMA PEMBIAYAAN'),
                                                    
                                                TextEntry::make('status')
                                                    ->label('STATUS PERSETUJUAN')
                                                    ->badge()
                                                    ->formatStateUsing(fn (string $state) => match ($state) {
                                                        'approved' => 'Disetujui',
                                                        'pending' => 'Menunggu',
                                                        'rejected' => 'Ditolak',
                                                        default => ucfirst($state),
                                                    })
                                                    ->color(fn (string $state): string => match ($state) {
                                                        'approved' => 'success',
                                                        'pending' => 'warning',
                                                        'rejected' => 'danger',
                                                        default => 'gray',
                                                    }),
                                                    
                                                TextEntry::make('rejected_reason')
                                                    ->label('ALASAN PENOLAKAN')
                                                    ->visible(fn ($record) => $record->status === 'rejected' && $record->rejected_reason !== null),
                                            ])
                                            ->columns(2)
                                            ->columnSpan(2),
                                            
                                        Section::make('Jumlah')
                                            ->schema([
                                                TextEntry::make('loan_amount')
                                                    ->label('JUMLAH PEMBIAYAAN')
                                                    ->money('IDR')
                                                    ->color(Color::Emerald)
                                                    ->weight('bold')
                                                    ->size(TextEntry\TextEntrySize::Large)
                                                    ->visible(fn ($record) => $record->loanProduct && $record->loanProduct->contract_type !== 'Murabahah'),
                                                
                                                TextEntry::make('purchase_price')
                                                    ->label('HARGA BELI')
                                                    ->money('IDR')
                                                    ->color(Color::Emerald)
                                                    ->weight('bold')
                                                    ->size(TextEntry\TextEntrySize::Large)
                                                    ->visible(fn ($record) => $record->loanProduct && $record->loanProduct->contract_type === 'Murabahah'),
                                                
                                                TextEntry::make('selling_price')
                                                    ->label('HARGA JUAL')
                                                    ->money('IDR')
                                                    ->color(Color::Emerald)
                                                    ->weight('bold')
                                                    ->size(TextEntry\TextEntrySize::Large)
                                                    ->visible(fn ($record) => $record->loanProduct && $record->loanProduct->contract_type === 'Murabahah'),
                                            ])
                                            ->columnSpan(1),
                                    ]),
                                    
                                Grid::make(3)
                                    ->schema([
                                        Section::make('Detail Pinjaman')
                                            ->schema([
                                                TextEntry::make('margin_amount')
                                                    ->label('MARGIN')
                                                    ->suffix('%'),
                                                    
                                                TextEntry::make('disbursement_status')
                                                    ->label('STATUS PENCAIRAN')
                                                    ->badge()
                                                    ->formatStateUsing(fn (string $state) => match ($state) {
                                                        'not_disbursed' => 'Belum Dicairkan',
                                                        'disbursed' => 'Sudah Dicairkan',
                                                        default => $state,
                                                    })
                                                    ->color(fn (string $state): string => match ($state) {
                                                        'disbursed' => 'success',
                                                        'not_disbursed' => 'warning',
                                                        default => 'gray',
                                                    }),
                                                    
                                                TextEntry::make('disbursed_at')
                                                    ->label('TANGGAL PENCAIRAN')
                                                    ->dateTime('d/m/Y'),
                                                    
                                                TextEntry::make('approved_at')
                                                    ->label('TANGGAL PERSETUJUAN')
                                                    ->dateTime('d/m/Y'),
                                                    
                                                TextEntry::make('rejected_reason')
                                                    ->label('ALASAN PENOLAKAN')
                                                    ->visible(fn ($record) => $record->status === 'rejected'),
                                            ])
                                            ->columns(2)
                                            ->columnSpan(2),
                                            
                                        Section::make('Metadata')
                                            ->schema([
                                                TextEntry::make('created_at')
                                                    ->label('DIBUAT TANGGAL')
                                                    ->dateTime('d/m/Y H:i:s')
                                                    ->timezone('Asia/Jakarta'),
                                                    
                                                TextEntry::make('creator.name')
                                                    ->label('DIBUAT OLEH')
                                                    ->placeholder('N/A'),
                                                    
                                                TextEntry::make('reviewer.name')
                                                    ->label('DITINJAU OLEH')
                                                    ->placeholder('N/A'),
                                            ])
                                            ->columnSpan(1),
                                    ]),
                            ]),
                            
                        Tabs\Tab::make('Jaminan')
                            ->schema([
                                Grid::make(1)
                                    ->schema([
                                        Section::make('Informasi Jaminan')
                                            ->schema([
                                                TextEntry::make('collateral_type')
                                                    ->label('JENIS JAMINAN')
                                                    ->badge()
                                                    ->formatStateUsing(fn (string $state) => match ($state) {
                                                        'bpkb' => 'BPKB Kendaraan',
                                                        'shm' => 'Sertifikat Hak Milik (SHM)',
                                                        default => 'Tidak Ada Jaminan',
                                                    })
                                                    ->color(fn (string $state): string => match ($state) {
                                                        'bpkb' => 'warning',
                                                        'shm' => 'success',
                                                        default => 'gray',
                                                    }),
                                            ]),
                                            
                                        Section::make('Detail BPKB Kendaraan')
                                            ->schema([
                                                TextEntry::make('bpkb_collateral_value')
                                                    ->label('NILAI JAMINAN')
                                                    ->money('IDR'),
                                                    
                                                TextEntry::make('bpkb_owner_name')
                                                    ->label('NAMA PEMILIK'),
                                                    
                                                TextEntry::make('bpkb_number')
                                                    ->label('NOMOR BPKB'),
                                                    
                                                TextEntry::make('bpkb_vehicle_number')
                                                    ->label('NOMOR POLISI'),
                                                    
                                                TextEntry::make('bpkb_vehicle_brand')
                                                    ->label('MERK KENDARAAN'),
                                                    
                                                TextEntry::make('bpkb_vehicle_type')
                                                    ->label('TIPE KENDARAAN'),
                                                    
                                                TextEntry::make('bpkb_vehicle_year')
                                                    ->label('TAHUN KENDARAAN'),
                                                    
                                                TextEntry::make('bpkb_frame_number')
                                                    ->label('NOMOR RANGKA'),
                                                    
                                                TextEntry::make('bpkb_engine_number')
                                                    ->label('NOMOR MESIN'),
                                            ])
                                            ->columns(2)
                                            ->visible(fn ($record) => $record->collateral_type === 'bpkb'),
                                            
                                        Section::make('Detail Sertifikat Hak Milik')
                                            ->schema([
                                                TextEntry::make('shm_collateral_value')
                                                    ->label('NILAI JAMINAN')
                                                    ->money('IDR'),
                                                    
                                                TextEntry::make('shm_owner_name')
                                                    ->label('NAMA PEMILIK'),
                                                    
                                                TextEntry::make('shm_certificate_number')
                                                    ->label('NOMOR SERTIFIKAT'),
                                                    
                                                TextEntry::make('shm_land_area')
                                                    ->label('LUAS TANAH')
                                                    ->suffix(' m²'),
                                                    
                                                TextEntry::make('shm_land_location')
                                                    ->label('LOKASI TANAH')
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns(2)
                                            ->visible(fn ($record) => $record->collateral_type === 'shm'),
                                    ]),
                            ]),
                        Tabs\Tab::make('Riwayat Pembayaran')
                            ->schema([
                                Grid::make(1)
                                    ->schema([
                                        Section::make('Riwayat Pembayaran')
                                            ->schema([                              
                                                RepeatableEntry::make('loanPayments')
                                                    ->label('')
                                                    ->schema([
                                                        TextEntry::make('payment_period')
                                                            ->label('PERIODE')
                                                            ->formatStateUsing(fn ($state, $record) => 
                                                                $record->is_principal_return ? 'Pengembalian Modal' : "Periode ke-$state"),
                                                        
                                                        TextEntry::make('amount')
                                                            ->label('JUMLAH')
                                                            ->money('IDR'),
                                                            
                                                        TextEntry::make('created_at')
                                                            ->label('TANGGAL BAYAR')
                                                            ->dateTime('d/m/Y H:i:s')
                                                            ->timezone('Asia/Jakarta'),
                                                            
                                                        TextEntry::make('status')
                                                            ->label('STATUS PERSETUJUAN')
                                                            ->badge()
                                                            ->formatStateUsing(fn (string $state) => match ($state) {
                                                                'approved' => 'Disetujui',
                                                                'pending' => 'Menunggu',
                                                                'rejected' => 'Ditolak',
                                                                default => $state,
                                                            })
                                                            ->color(fn (string $state): string => match ($state) {
                                                                'approved' => 'success',
                                                                'pending' => 'warning',
                                                                'rejected' => 'danger',
                                                                default => 'gray',
                                                            }),
                                                    ])
                                                    ->columns(4)
                                                    ->visible(fn ($record) => $record->loanPayments->count() > 0)
                                                    ->extraAttributes([
                                                        'class' => 'border rounded-xl p-0 overflow-hidden',
                                                        'style' => 'border-collapse: collapse;'
                                                    ])
                                                    ->columnSpanFull(),
                                                
                                                TextEntry::make('no_payments')
                                                    ->label('')
                                                    ->formatStateUsing(fn () => 'Belum ada riwayat pembayaran')
                                                    ->visible(fn ($record) => $record->loanPayments->count() === 0),
                                            ]),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('createPayment')
                ->label('Buat Pembayaran Pinjaman')
                ->color('primary')
                ->visible(fn () => $this->record->status === 'approved' && 
                                $this->record->disbursement_status === 'disbursed' && 
                                $this->record->payment_status !== 'paid')
                ->url(fn () => LoanPaymentResource::getUrl('create', ['loan_id' => $this->record->id])),
        ];
    }

    public function getRelationManagers(): array
    {
        return [

        ];
    }
}