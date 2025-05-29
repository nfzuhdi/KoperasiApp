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
use Filament\Resources\RelationManagers;

class ViewLoan extends ViewRecord
{
    protected static string $resource = LoanResource::class;

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
                                        Section::make('Loan Information')
                                            ->schema([
                                                TextEntry::make('account_number')
                                                    ->label('NOMOR REKENING'),
                                                    
                                                TextEntry::make('member.full_name')
                                                    ->label('NAMA ANGGOTA'),
                                        
                                                TextEntry::make('loanProduct.name')
                                                    ->label('PEMBIAYAAN'),
                                                    
                                                TextEntry::make('status')
                                                    ->label('STATUS')
                                                    ->badge()
                                                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                                                    ->color(fn (string $state): string => match ($state) {
                                                        'approved' => 'success',
                                                        'pending' => 'warning',
                                                        'rejected' => 'danger',
                                                        'completed' => 'info',
                                                        default => 'gray',
                                                    }),
                                                
                                                TextEntry::make('reviewer.name')
                                                    ->label('REVIEWED BY')
                                                    ->visible(fn ($record) => $record->reviewed_by !== null),
                                                    
                                                TextEntry::make('rejected_reason')
                                                    ->label('ALASAN PENOLAKAN')
                                                    ->visible(fn ($record) => $record->status === 'rejected' && $record->rejected_reason !== null),
                                            ])
                                            ->columns(2)
                                            ->columnSpan(2),
                                            
                                        Section::make('Amount')
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
                                        Section::make('Loan Details')
                                            ->schema([
                                                TextEntry::make('margin_amount')
                                                    ->label('Margin')
                                                    ->suffix('%'),
                                                    
                                                TextEntry::make('disbursement_status')
                                                    ->label('Status Pencairan')
                                                    ->badge()
                                                    ->formatStateUsing(fn (string $state) => match ($state) {
                                                        'not_disbursed' => 'Not Disbursed',
                                                        'disbursed' => 'Disbursed',
                                                        default => $state,
                                                    })
                                                    ->color(fn (string $state): string => match ($state) {
                                                        'disbursed' => 'success',
                                                        'not_disbursed' => 'warning',
                                                        default => 'gray',
                                                    }),
                                                    
                                                TextEntry::make('disbursed_at')
                                                    ->label('Tanggal Pencairan')
                                                    ->date('d/m/Y'),
                                                    
                                                TextEntry::make('approved_at')
                                                    ->label('Tanggal Persetujuan')
                                                    ->date('d/m/Y'),
                                                    
                                                TextEntry::make('rejected_reason')
                                                    ->label('Alasan Penolakan')
                                                    ->visible(fn ($record) => $record->status === 'rejected'),
                                            ])
                                            ->columns(2)
                                            ->columnSpan(2),
                                            
                                        Section::make('Metadata')
                                            ->schema([
                                                TextEntry::make('created_at')
                                                    ->label('Created At')
                                                    ->dateTime('d/m/Y H:i:s'),
                                                    
                                                TextEntry::make('creator.name')
                                                    ->label('Created By')
                                                    ->placeholder('N/A'),
                                                    
                                                TextEntry::make('reviewer.name')
                                                    ->label('Reviewed By')
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
                                            
                                        // BPKB Collateral Details
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
                                            
                                        // SHM Collateral Details
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
                    ])
                    ->columnSpanFull(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            // Actions\EditAction::make(),
            
            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->status === 'pending' && auth()->user()->hasRole('kepala_cabang'))
                ->requiresConfirmation()
                ->modalHeading('Approve Loan')
                ->modalDescription('Are you sure you want to approve this loan? The status will be changed to approved.')
                ->action(function () {
                    $this->record->status = 'approved';
                    $this->record->reviewed_by = auth()->id();
                    $this->record->approved_at = now();
                    $this->record->save();
                    
                    Notification::make()
                        ->title('Loan approved successfully')
                        ->success()
                        ->send();
                        
                    $this->redirect(LoanResource::getUrl('view', ['record' => $this->record]));
                }),
                
            Actions\Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->status === 'pending' && auth()->user()->hasRole('kepala_cabang'))
                ->form([
                    Textarea::make('rejected_reason')
                        ->label('Reason for Rejection')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->record->status = 'rejected';
                    $this->record->reviewed_by = auth()->id();
                    $this->record->rejected_reason = $data['rejected_reason'];
                    $this->record->save();
                    
                    Notification::make()
                        ->title('Loan rejected')
                        ->success()
                        ->send();
                        
                    $this->redirect(LoanResource::getUrl('view', ['record' => $this->record]));
                }),
            Actions\Action::make('disburse')
                ->label('Disburse')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->visible(fn () => $this->record->status === 'approved' && $this->record->disbursement_status === 'not_disbursed')
                ->requiresConfirmation()
                ->modalHeading('Disburse Loan')
                ->modalDescription('Are you sure you want to disburse this loan?')
                ->action(function () {
                    try {
                        DB::beginTransaction();
                        
                        $this->record->disbursement_status = 'disbursed';
                        $this->record->disbursed_at = now();
                        $this->record->save();
                        
                        $loanProduct = $this->record->loanProduct;
                        if ($loanProduct && 
                            $loanProduct->journal_account_balance_debit_id && 
                            $loanProduct->journal_account_balance_credit_id) {
                            
                            $debitAccount = JournalAccount::find($loanProduct->journal_account_balance_debit_id);
                            if (!$debitAccount) {
                                throw new \Exception("Debit journal account not found");
                            }
                            
                            $creditAccount = JournalAccount::find($loanProduct->journal_account_balance_credit_id);
                            if (!$creditAccount) {
                                throw new \Exception("Credit journal account not found");
                            }
                            
                            // Tentukan jumlah yang akan dibukukan
                            $amount = $this->record->loan_amount;
                            if ($loanProduct->contract_type === 'Murabahah') {
                                $amount = $this->record->purchase_price;
                            }
                            
                            // 1. Piutang Pembiayaan (DEBIT) - bertambah
                            $debitAccount->balance += $amount;
                            $debitAccount->save();
                            
                            // 2. Kas (KREDIT) - berkurang
                            $creditAccount->balance -= $amount;
                            $creditAccount->save();
                        }
                        
                        // Commit transaksi jika semua berhasil
                        DB::commit();
                        
                        Notification::make()
                            ->title('Loan disbursed successfully')
                            ->success()
                            ->send();
                            
                        $this->redirect(LoanResource::getUrl('view', ['record' => $this->record]));
                        
                    } catch (\Exception $e) {
                        // Rollback transaksi jika terjadi kesalahan
                        DB::rollBack();
                        
                        Notification::make()
                            ->title('Error disbursing loan')
                            ->body('An error occurred: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
                
            Actions\Action::make('createPayment')
                ->label('Create Loan Payment')
                ->color('primary')
                ->visible(fn () => $this->record->status === 'approved' && $this->record->disbursement_status === 'disbursed')
                ->url(fn () => LoanPaymentResource::getUrl('create', ['loan_id' => $this->record->id]))
                ->openUrlInNewTab(),
        ];
    }

    public function getRelationManagers(): array
    {
        return [
            \App\Filament\Resources\LoanResource\RelationManagers\LoanPaymentsRelationManager::class,
        ];
    }
}