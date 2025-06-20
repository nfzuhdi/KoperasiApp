<?php

namespace App\Filament\Resources\LoanPaymentResource\Pages;

use App\Filament\Resources\LoanPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\DB;
use App\Models\Loan;

class ViewLoanPayment extends ViewRecord
{
    protected static string $resource = LoanPaymentResource::class;

    protected static ?string $title = 'Detail Pembayaran Pinjaman';

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Grid::make(3)
                    ->schema([
                        Section::make('Informasi Pembayaran')
                            ->schema([
                                TextEntry::make('reference_number')
                                    ->label('NOMOR REFERENSI'),
                                
                                TextEntry::make('loan.account_number')
                                    ->label('AKUN PINJAMAN'),
                                
                                TextEntry::make('loan.member.full_name')
                                    ->label('NAMA ANGGOTA'),
                                
                                TextEntry::make('payment_period')
                                    ->label('PERIODE PEMBAYARAN'),
                                
                                TextEntry::make('created_at')
                                    ->label('TANGGAL PEMBAYARAN')
                                    ->dateTime('d/m/Y H:i:s')
                                    ->timezone('Asia/Jakarta'),
                                
                                TextEntry::make('status')
                                    ->label('STATUS')
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
                            ])
                            ->columns(2)
                            ->columnSpan(2),
                            
                        Section::make('Detail')
                            ->schema([
                                TextEntry::make('amount')
                                    ->label('JUMLAH PEMBAYARAN')
                                    ->money('IDR')
                                    ->color(Color::Emerald)
                                    ->weight('bold')
                                    ->size(TextEntry\TextEntrySize::Large),
                                
                                TextEntry::make('fine')
                                    ->label('DENDA KETERLAMBATAN')
                                    ->money('IDR')
                                    ->visible(fn ($record) => $record->fine > 0),
                                
                                TextEntry::make('payment_method')
                                    ->label('METODE PEMBAYARAN')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'cash' => 'success',
                                        'transfer' => 'info',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'cash' => 'Tunai',
                                        'transfer' => 'Transfer Bank',
                                        default => ucfirst($state),
                                    }),

                            ])
                            ->columnSpan(1),
                    ]),
                    
                Section::make('Informasi Tambahan')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('CATATAN')
                            ->columnSpanFull(),
                            
                        TextEntry::make('reviewedBy.name')
                            ->label('DITINJAU OLEH')
                            ->visible(fn ($record) => $record->reviewed_by !== null),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record->notes || $record->reviewed_by),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [            
            Actions\Action::make('approve')
                ->label('Setuju')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->status === 'pending' && auth()->user()->hasRole('kepala_cabang'))
                ->requiresConfirmation()
                ->modalHeading('Setujui Pembayaran Pinjaman')
                ->modalDescription('Apakah anda yakin ingin menyetujui pembayaran pinjaman ini?')
                ->modalSubmitActionLabel('Setujui')
                ->modalCancelActionLabel('Batal')
                ->action(function () {
                    try {
                        DB::beginTransaction();

                        $this->record->status = 'approved';
                        $this->record->reviewed_by = auth()->id();
                        $this->record->save();

                        $loan = Loan::find($this->record->loan_id);
                        if (!$loan) {
                            throw new \Exception("Loan not found with ID: {$this->record->loan_id}");
                        }
                        
                        if ($loan->loanProduct->contract_type === 'Mudharabah') {
                            $this->record->processJournalMudharabah($loan);
                        } else if ($loan->loanProduct->contract_type === 'Murabahah') {
                            $this->record->processJournalMurabahah($loan);
                        } else if ($loan->loanProduct->contract_type === 'Musyarakah') {
                            $this->record->processJournalMusyarakah($loan);
                        } else {
                            LoanPaymentResource::processJournalEntries($this->record);
                        }
                        
                        if ($this->record->fine > 0){
                            $this->record->processFineJournalFixed($loan);
                        }

                        LoanPaymentResource::updateLoanPaymentStatus($loan);

                        DB::commit();

                        Notification::make()
                            ->title('Pembayaran Pinjaman berhasil disetujui')
                            ->success()
                            ->send();
                            
                        $this->redirect(LoanPaymentResource::getUrl('view', ['record' => $this->record]));
                    } catch (\Exception $e) {
                        DB::rollBack();

                        Notification::make()
                            ->title('Error approving payment')
                            ->body('An error occurred: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
                
            Actions\Action::make('reject')
                ->label('Tolak')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->status === 'pending' && auth()->user()->hasRole('kepala_cabang'))
                ->form([
                    Textarea::make('rejection_notes')
                        ->label('Alasan Penolakan')
                        ->required(),
                ])
                ->requiresConfirmation()
                ->modalHeading('Tolak Pembayaran Pinjaman')
                ->modalDescription('Apakah anda yakin ingin menolak pembayaran pinjaman ini?')
                ->modalSubmitActionLabel('Tolak')
                ->modalCancelActionLabel('Batal')
                ->action(function (array $data) {
                    $this->record->status = 'rejected';
                    $this->record->reviewed_by = auth()->id();
                    $this->record->notes = ($this->record->notes ? $this->record->notes . "\n\n" : '') . "Rejected: " . $data['rejection_notes'];
                    $this->record->save();
                    
                    Notification::make()
                        ->title('Pembayaran Pinjaman Ditolak')
                        ->success()
                        ->send();
                        
                    $this->redirect(LoanPaymentResource::getUrl('view', ['record' => $this->record]));
                }),
            Actions\Action::make('printInvoice')
                ->label('Cetak Invoice')
                ->icon('heroicon-o-printer')
                ->color('info')
                ->url(fn () => route('loan-payment.invoice', ['record' => $this->record->id]))
                ->openUrlInNewTab()
                ->visible(fn () => $this->record->status === 'approved'),
        ];
    }
}