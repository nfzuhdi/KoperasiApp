<?php

namespace App\Filament\Resources\SavingPaymentResource\Pages;

use App\Filament\Resources\SavingPaymentResource;
use App\Models\Saving;
use App\Models\JournalAccount;
use App\Models\JurnalUmum;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class ViewSavingPayment extends ViewRecord
{
    protected static string $resource = SavingPaymentResource::class;

    public function getTitle(): string 
    {
        return 'Detail Transaksi ' . $this->record->reference_number;
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
                ->modalHeading('Approve Payment')
                ->modalDescription('Are you sure you want to approve this payment?')
                ->action(function () {
                    try {
                        // Mulai transaksi database
                        DB::beginTransaction();
                        
                        // Debug log untuk melihat data awal
                        Log::info('Starting payment approval process', [
                            'payment_id' => $this->record->id,
                            'saving_id' => $this->record->saving_id,
                            'amount' => $this->record->amount
                        ]);
                        
                        // 1. Update status pembayaran
                        $this->record->status = 'approved';
                        $this->record->reviewed_by = auth()->id();
                        $this->record->save();
                        
                        // 2. Update saldo rekening simpanan
                        $saving = Saving::find($this->record->saving_id);
                        if (!$saving) {
                            throw new \Exception("Saving account not found with ID: {$this->record->saving_id}");
                        }
                        
                        Log::info('Found saving account', [
                            'saving_id' => $saving->id,
                            'current_balance' => $saving->balance
                        ]);
                        
                        $saving->balance += $this->record->payment_type === 'withdrawal' ? -$this->record->amount : $this->record->amount;
                        $saving->save();
                        
                        Log::info('Updated saving balance', [
                            'saving_id' => $saving->id,
                            'new_balance' => $saving->balance
                        ]);
                        
                        // 3. Proses jurnal akuntansi
                        $savingProduct = $saving->savingProduct;
                        if (!$savingProduct) {
                            throw new \Exception("Saving product not found for saving ID: {$saving->id}");
                        }
                        
                        Log::info('Found saving product', [
                            'product_id' => $savingProduct->id,
                            'product_name' => $savingProduct->savings_product_name
                        ]);
                        
                        // 3.1 Proses jurnal untuk setoran/penarikan
                        if ($this->record->payment_type === 'withdrawal') {
                            // Untuk penarikan:

                            // Akun debit (Simpanan - posisi normal kredit)
                            $debitAccount = JournalAccount::find($savingProduct->journal_account_withdrawal_debit_id);
                            if (!$debitAccount) {
                                throw new \Exception("Withdrawal debit journal account not found");
                            }
                            
                            // Akun kredit (Kas - posisi normal debit)
                            $creditAccount = JournalAccount::find($savingProduct->journal_account_withdrawal_credit_id);
                            if (!$creditAccount) {
                                throw new \Exception("Withdrawal credit journal account not found");
                            }

                            // Update saldo akun debit (Simpanan - posisi normal kredit)
                            $oldBalance = $debitAccount->balance;
                            if ($debitAccount->account_position === 'debit') {
                                $debitAccount->balance += $this->record->amount; // Berkurang di debit
                            } else {
                                $debitAccount->balance -= $this->record->amount; // Bertambah di kredit
                            }
                            $debitAccount->save();

                            // Update saldo akun kredit (Kas - posisi normal debit)
                            $oldBalance = $creditAccount->balance;
                            if ($creditAccount->account_position === 'credit') {
                                $creditAccount->balance += $this->record->amount; // Bertambah di kredit
                            } else {
                                $creditAccount->balance -= $this->record->amount; // Berkurang di debit
                            }
                            $creditAccount->save();

                            // Generate transaction number
                            $transactionNumber = 'TRX-' . $saving->id . '-' . now()->format('Ymd-His');

                            // Tambah jurnal umum untuk penarikan
                            JurnalUmum::create([
                                'tanggal_bayar' => $this->record->created_at,
                                'no_ref' => $this->record->reference_number,
                                'no_transaksi' => $transactionNumber, // Use generated number
                                'akun_id' => $debitAccount->id,
                                'keterangan' => "Penarikan simpanan {$saving->account_number}",
                                'debet' => $this->record->amount,
                                'kredit' => 0,
                                'saving_payment_id' => $this->record->id,
                             ]);

                            JurnalUmum::create([
                                'tanggal_bayar' => $this->record->created_at,
                                'no_ref' => $this->record->reference_number,
                                'no_transaksi' => $transactionNumber, // Use same number for pair entry
                                'akun_id' => $creditAccount->id,
                                'keterangan' => "Penarikan simpanan {$saving->account_number}",
                                'debet' => 0,
                                'kredit' => $this->record->amount,
                                'saving_payment_id' => $this->record->id,
                            ]);

                        } elseif ($this->record->payment_type === 'profit_sharing') {
                            // For profit sharing payments:
                            
                            // Get profit sharing journal accounts from saving product
                            $debitAccount = JournalAccount::find($savingProduct->journal_account_profitsharing_debit_id);
                            if (!$debitAccount) {
                                throw new \Exception("Profit sharing debit account not found");
                            }
                            
                            $creditAccount = JournalAccount::find($savingProduct->journal_account_profitsharing_credit_id);
                            if (!$creditAccount) {
                                throw new \Exception("Profit sharing credit account not found");
                            }

                            Log::info('Processing profit sharing journal entries', [
                                'debit_account' => $debitAccount->account_name,
                                'credit_account' => $creditAccount->account_name,
                                'amount' => $this->record->amount
                            ]);

                            // Update debit account balance (Beban Bagi Hasil)
                            $oldBalance = $debitAccount->balance;
                            if ($debitAccount->account_position === 'debit') {
                                $debitAccount->balance += $this->record->amount;
                            } else {
                                $debitAccount->balance -= $this->record->amount;
                            }
                            $debitAccount->save();

                            // Update credit account balance (Hutang Bagi Hasil)
                            $oldBalance = $creditAccount->balance;
                            if ($creditAccount->account_position === 'credit') {
                                $creditAccount->balance += $this->record->amount;
                            } else {
                                $creditAccount->balance -= $this->record->amount;
                            }
                            $creditAccount->save();

                            // Generate transaction number for profit sharing
                            $transactionNumber = 'TRX-PROFIT-' . $saving->id . '-' . now()->format('Ymd-His');

                            // Create journal entries for profit sharing
                            JurnalUmum::create([
                                'tanggal_bayar' => $this->record->created_at,
                                'no_ref' => $this->record->reference_number,
                                'no_transaksi' => $transactionNumber,
                                'akun_id' => $debitAccount->id,
                                'keterangan' => "Bagi hasil simpanan mudharabah {$saving->account_number} periode " . 
                                    Carbon::create()->month($this->record->month)->format('F Y'),
                                'debet' => $this->record->amount,
                                'kredit' => 0,
                                'saving_payment_id' => $this->record->id,
                            ]);

                            JurnalUmum::create([
                                'tanggal_bayar' => $this->record->created_at,
                                'no_ref' => $this->record->reference_number,
                                'no_transaksi' => $transactionNumber,
                                'akun_id' => $creditAccount->id,
                                'keterangan' => "Bagi hasil simpanan mudharabah {$saving->account_number} periode " . 
                                    Carbon::create()->month($this->record->month)->format('F Y'),
                                'debet' => 0,
                                'kredit' => $this->record->amount,
                                'saving_payment_id' => $this->record->id,
                            ]);

                            // Update saving balance
                            $saving->balance += $this->record->amount;
                            $saving->save();

                            Log::info('Completed profit sharing journal entries', [
                                'payment_id' => $this->record->id,
                                'saving_id' => $saving->id,
                                'amount' => $this->record->amount
                            ]);
                        } else {
                            // Proses jurnal untuk setoran (kode yang sudah ada)
                            if (!$savingProduct->journal_account_deposit_debit_id || 
                                !$savingProduct->journal_account_deposit_credit_id) {
                                throw new \Exception("Deposit journal accounts not configured");
                            }
                            
                            // Akun debit (biasanya Kas)
                            $debitAccount = JournalAccount::find($savingProduct->journal_account_deposit_debit_id);
                            if (!$debitAccount) {
                                throw new \Exception("Debit journal account not found with ID: {$savingProduct->journal_account_deposit_debit_id}");
                            }
                            
                            Log::info('Found debit account', [
                                'account_id' => $debitAccount->id,
                                'account_name' => $debitAccount->account_name,
                                'current_balance' => $debitAccount->balance
                            ]);
                            
                            // Debit kas (bertambah jika posisi normal debit, berkurang jika kredit)
                            $oldBalance = $debitAccount->balance;
                            if ($debitAccount->account_position === 'debit') {
                                $debitAccount->balance += $this->record->amount;
                            } else {
                                $debitAccount->balance -= $this->record->amount;
                            }
                            $debitAccount->save();
                            
                            Log::info('Updated debit account', [
                                'account_id' => $debitAccount->id,
                                'old_balance' => $oldBalance,
                                'new_balance' => $debitAccount->balance
                            ]);
                            
                            // Akun kredit (biasanya Simpanan)
                            $creditAccount = JournalAccount::find($savingProduct->journal_account_deposit_credit_id);
                            if (!$creditAccount) {
                                throw new \Exception("Credit journal account not found with ID: {$savingProduct->journal_account_deposit_credit_id}");
                            }
                            
                            Log::info('Found credit account', [
                                'account_id' => $creditAccount->id,
                                'account_name' => $creditAccount->account_name,
                                'current_balance' => $creditAccount->balance
                            ]);
                            
                            // Kredit simpanan (bertambah jika posisi normal kredit, berkurang jika debit)
                            $oldBalance = $creditAccount->balance;
                            if ($creditAccount->account_position === 'credit') {
                                $creditAccount->balance += $this->record->amount;
                            } else {
                                $creditAccount->balance -= $this->record->amount;
                            }
                            $creditAccount->save();
                            
                            Log::info('Updated credit account', [
                                'account_id' => $creditAccount->id,
                                'old_balance' => $oldBalance,
                                'new_balance' => $creditAccount->balance
                            ]);
                            
                            // Generate transaction number
                            $transactionNumber = 'TRX-' . $saving->id . '-' . now()->format('Ymd-His');

                            // Tambah jurnal umum untuk setoran
                            JurnalUmum::create([
                                'tanggal_bayar' => $this->record->created_at,
                                'no_ref' => $this->record->reference_number, 
                                'no_transaksi' => $transactionNumber,
                                'akun_id' => $debitAccount->id,
                                'keterangan' => "Setoran simpanan {$saving->account_number}",
                                'debet' => $this->record->amount,
                                'kredit' => 0,
                                'saving_payment_id' => $this->record->id,
                            ]);

                            JurnalUmum::create([
                                'tanggal_bayar' => $this->record->created_at,
                                'no_ref' => $this->record->reference_number,
                                'no_transaksi' => $transactionNumber,
                                'akun_id' => $creditAccount->id,
                                'keterangan' => "Setoran simpanan {$saving->account_number}",
                                'debet' => 0,
                                'kredit' => $this->record->amount,
                                'saving_payment_id' => $this->record->id,
                            ]);

                        }
                        
                        // 3.2 Proses jurnal untuk denda keterlambatan (jika ada)
                        if (isset($this->record->fine) && $this->record->fine > 0) {
                            
                            // Process late payment fine journal entries
                            Log::info('Processing late payment fine journal entries', [
                                'fine_amount' => $this->record->fine
                            ]);
                            
                            // Get the debit account for penalty
                            $penaltyDebitAccount = JournalAccount::find($savingProduct->journal_account_penalty_debit_id);
                            if (!$penaltyDebitAccount) {
                                throw new \Exception("Penalty debit journal account not found with ID: {$savingProduct->journal_account_penalty_debit_id}");
                            }
                            
                            // Get the credit account for penalty
                            $penaltyCreditAccount = JournalAccount::find($savingProduct->journal_account_penalty_credit_id);
                            if (!$penaltyCreditAccount) {
                                throw new \Exception("Penalty credit journal account not found with ID: {$savingProduct->journal_account_penalty_credit_id}");
                            }
                            
                            // Update debit account balance
                            $oldBalance = $penaltyDebitAccount->balance;
                            if ($penaltyDebitAccount->account_position === 'debit') {
                                $penaltyDebitAccount->balance += $this->record->fine;
                            } else {
                                $penaltyDebitAccount->balance -= $this->record->fine;
                            }
                            $penaltyDebitAccount->save();
                            
                            Log::info('Updated penalty debit account', [
                                'account_id' => $penaltyDebitAccount->id,
                                'old_balance' => $oldBalance,
                                'new_balance' => $penaltyDebitAccount->balance
                            ]);
                            
                            // Update credit account balance
                            $oldBalance = $penaltyCreditAccount->balance;
                            if ($penaltyCreditAccount->account_position === 'credit') {
                                $penaltyCreditAccount->balance += $this->record->fine;
                            } else {
                                $penaltyCreditAccount->balance -= $this->record->fine;
                            }
                            $penaltyCreditAccount->save();
                            
                            Log::info('Updated penalty credit account', [
                                'account_id' => $penaltyCreditAccount->id,
                                'old_balance' => $oldBalance,
                                'new_balance' => $penaltyCreditAccount->balance
                            ]);
                            
                            // Tambah jurnal umum untuk denda
                            $penaltyTransactionNumber = 'TRX-PEN-' . $saving->id . '-' . now()->format('Ymd-His');

                            JurnalUmum::create([
                                'tanggal_bayar' => $this->record->created_at,
                                'no_ref' => $this->record->reference_number,
                                'no_transaksi' => $penaltyTransactionNumber,
                                'akun_id' => $penaltyDebitAccount->id,
                                'keterangan' => "Denda keterlambatan simpanan {$saving->account_number}",
                                'debet' => $this->record->fine,
                                'kredit' => 0,
                                'saving_payment_id' => $this->record->id,
                            ]);

                            JurnalUmum::create([
                                'tanggal_bayar' => $this->record->created_at,
                                'no_ref' => $this->record->reference_number,
                                'no_transaksi' => $penaltyTransactionNumber,
                                'akun_id' => $penaltyCreditAccount->id,
                                'keterangan' => "Denda keterlambatan simpanan {$saving->account_number}",
                                'debet' => 0,
                                'kredit' => $this->record->fine,
                                'saving_payment_id' => $this->record->id,
                            ]);

                            Log::info('Created journal entries for penalty', [
                                'payment_id' => $this->record->id,
                                'fine_amount' => $this->record->fine
                            ]);
                        }
                        
                        // Commit transaksi jika semua berhasil
                        DB::commit();

                        Log::info('Payment approval completed successfully', [
                            'payment_id' => $this->record->id
                        ]);

                        Notification::make()
                            ->title('Payment approved successfully')
                            ->success()
                            ->send();
                            
                        $this->redirect(SavingPaymentResource::getUrl('view', ['record' => $this->record]));
                            
                    } catch (\Exception $e) {
                        // Rollback transaksi jika terjadi kesalahan
                        DB::rollBack();
                        
                        Log::error('Error approving payment: ' . $e->getMessage(), [
                            'payment_id' => $this->record->id,
                            'exception' => $e,
                            'trace' => $e->getTraceAsString()
                        ]);
                        
                        Notification::make()
                            ->title('Error approving payment')
                            ->body('An error occurred: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
                
            Actions\Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->status === 'pending' && auth()->user()->hasRole('kepala_cabang'))
                ->requiresConfirmation()
                ->form([
                    Textarea::make('rejection_notes')
                        ->label('Reason for Rejection')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->record->status = 'rejected';
                    $this->record->reviewed_by = auth()->id();
                    $this->record->notes = ($this->record->notes ? $this->record->notes . "\n\n" : '') . "Rejected: " . $data['rejection_notes'];
                    $this->record->save();
                    
                    Notification::make()
                        ->title('Payment rejected')
                        ->success()
                        ->send();
                        
                    $this->redirect(SavingPaymentResource::getUrl('view', ['record' => $this->record]));
                }),
        ];
    }
    
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Baris pertama - Account Info & Amount
                Grid::make(3)
                    ->schema([
                        Section::make('Informasi Akun')
                            ->schema([
                                TextEntry::make('savingAccount.account_number')
                                    ->label('NOMOR REKENING')
                                    ->weight(FontWeight::Bold),
                                TextEntry::make('savingAccount.member.full_name')
                                    ->label('NAMA ANGGOTA')
                                    ->color(Color::Blue),
                                TextEntry::make('payment_type')
                                    ->label('TIPE TRANSAKSI')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'deposit' => 'Setoran',
                                        'withdrawal' => 'Penarikan',
                                        default => $state,
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        'deposit' => 'success',
                                        'withdrawal' => 'warning',
                                        default => 'gray',
                                    }),
                            ])
                            ->columns(1)
                            ->columnSpan(2),

                        Section::make('Jumlah Transaksi')
                            ->schema([
                                TextEntry::make('amount')
                                    ->label('JUMLAH TRANSAKSI')
                                    ->money('IDR')
                                    ->weight(FontWeight::Bold)
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->color(Color::Emerald),
                                TextEntry::make('fine')
                                    ->label('DENDA')
                                    ->money('IDR')
                                    ->color(Color::Red)
                                    ->weight(FontWeight::Medium)
                                    ->visible(fn ($record) => $record->fine > 0),
                            ])
                            ->columnSpan(1),
                    ]),

                // Baris kedua - Payment Details & Status
                Grid::make(3)
                    ->schema([
                        Section::make('Detail Pembayaran')
                            ->schema([
                                TextEntry::make('payment_method')
                                    ->label('METODE PEMBAYARAN')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'cash' => 'Tunai',
                                        'transfer' => 'Transfer Bank',
                                        'debit_card' => 'Kartu Debit',
                                        'credit_card' => 'Kartu Kredit',
                                        'e_wallet' => 'Dompet Digital',
                                        'other' => 'Lainnya',
                                        default => ucfirst($state),
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        'cash' => 'success',
                                        'transfer' => 'info',
                                        'debit_card' => 'warning',
                                        'credit_card' => 'danger',
                                        'e_wallet' => 'purple',
                                        default => 'gray',
                                    }),
                                TextEntry::make('reference_number')
                                    ->label('NOMOR REFERENSI')
                                    ->placeholder('N/A')
                                    ->color(Color::Gray),
                                TextEntry::make('created_at')
                                    ->label('TANGGAL TRANSAKSI')
                                    ->dateTime('d/m/Y H:i:s')
                                    ->color(Color::Slate),
                            ])
                            ->columns(1)
                            ->columnSpan(2),

                        Section::make('Status')
                            ->schema([
                                TextEntry::make('status')
                                    ->label('STATUS TRANSAKSI')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
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
                                    })
                                    ->size(TextEntry\TextEntrySize::Medium)
                                    ->weight(FontWeight::Bold),
                            ])
                            ->columnSpan(1),
                    ]),

                // Baris ketiga - Notes (jika ada)
                Grid::make(1)
                    ->schema([
                        Section::make('Additional Information')
                            ->schema([
                                TextEntry::make('notes')
                                    ->label('CATATAN')
                                    ->placeholder('Tidak ada catatan')
                                    ->color(Color::Slate),
                            ])
                            ->visible(fn ($record) => !empty($record->notes)),
                    ]),

                // Baris keempat - Metadata
                Grid::make(1)
                    ->schema([
                        Section::make('Metadata')
                            ->schema([
                                TextEntry::make('creator.name')
                                    ->label('DIBUAT OLEH')
                                    ->color(Color::Blue)
                                    ->weight(FontWeight::Medium),
                                TextEntry::make('reviewer.name')
                                    ->label('DISETUJUI/DITOLAK OLEH')
                                    ->color(Color::Blue)
                                    ->weight(FontWeight::Medium)
                                    ->visible(fn ($record) => $record->reviewed_by !== null)
                                    ->placeholder('Belum direview'),
                                TextEntry::make('updated_at')
                                    ->label('TERAKHIR DIUPDATE')
                                    ->dateTime('d/m/Y H:i:s')
                                    ->color(Color::Gray),
                            ])
                            ->columns(1)
                            ->columnSpan(1),
                    ]),
            ]);
    }
}