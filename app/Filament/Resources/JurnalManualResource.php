<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JurnalManualResource\Pages;
use App\Models\JurnalManual;
use App\Models\JournalAccount;
use App\Models\JurnalUmum;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


class JurnalManualResource extends Resource
{
    protected static ?string $model = JurnalManual::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Laporan Keuangan';
    protected static ?string $navigationLabel = 'Jurnal Manual';
    protected static string $resourceLabel = 'Jurnal Manual';

    public static function getPluralLabel(): ?string
        {
            return 'Jurnal Manual'; // label plural (list & breadcrumb)
        }

        public static function getLabel(): ?string
        {
            return 'Jurnal Manual'; // label tunggal (form header)
        }



    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Transaksi')
                    ->schema([
Forms\Components\Select::make('nama_transaksi')
    ->live()
    ->label('Nama Transaksi')
    ->options([
        'bayar_listrik' => 'Bayar Listrik',
        'bayar_gaji' => 'Bayar Gaji',
        'bayar_internet' => 'Bayar Internet',
        'beli_atk' => 'Beli ATK',
        'beli_peralatan_kantor' => 'Beli Peralatan Kantor',
        'bayar_sewa_kantor' => 'Bayar Sewa Kantor',
        'bayar_air' => 'Bayar Air',
        'pengeluaran_transportasi' => 'Pengeluaran Transportasi',
        'pengeluaran_representasi' => 'Pengeluaran Representasi',
        'biaya_pemasaran' => 'Biaya Pemasaran',
        'biaya_pajak' => 'Biaya Pajak',
        'bayar_asuransi' => 'Bayar Asuransi',
        'pendapatan_lain' => 'Pendapatan Lain',
        'penyesuaian_persediaan' => 'Penyesuaian Persediaan',
        'lainnya' => 'Lainnya',
    ])
    ->searchable()
    ->required()
    ->afterStateUpdated(function ($state, Forms\Set $set) {
        // Reset field other ketika bukan other
        if ($state !== 'lainnya') {
            $set('nama_transaksi_lainnya', null);
        }
    }),

Forms\Components\TextInput::make('nama_transaksi_lainnya')
    ->label('Nama Transaksi Lainnya')
    ->visible(fn (Forms\Get $get) => $get('nama_transaksi') === 'lainnya')
    ->required(fn (Forms\Get $get) => $get('nama_transaksi') === 'lainnya')
    ->maxLength(255)
    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
        // Set nilai nama_transaksi dengan nilai custom jika other dipilih
        if ($get('nama_transaksi') === 'lainnya' && $state) {
            $set('nama_transaksi', $state);
        }
    }),

                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->placeholder('DD/MM/YYYY'),

                        Forms\Components\TextInput::make('nominal')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->minValue(0)
                            ->step(0.01)
                            ->label('Nominal'),

                        Forms\Components\Textarea::make('catatan')
                            ->maxLength(65535)
                            ->label('Catatan'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Akun Jurnal')
                    ->schema([
                        Forms\Components\Select::make('journal_account_transaction_debit_id')
                            ->label('Akun Debit')
                            ->relationship('debitAccount', 'account_name', fn ($query) => $query->where('is_active', true))
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->account_number} - {$record->account_name}")
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('journal_account_transaction_credit_id')
                            ->label('Akun Kredit')
                            ->relationship('creditAccount', 'account_name', fn ($query) => $query->where('is_active', true))
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->account_number} - {$record->account_name}")
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->columns(2),

                // Hidden field untuk status
                Forms\Components\Hidden::make('status')
                    ->default('pending'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_transaksi')
                    ->label('Nama Transaksi')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('nominal')
                    ->label('Nominal')
                    ->money('IDR', true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'primary' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('debitAccount.account_name')
                    ->label('Akun Debit')
                    ->searchable(),

                Tables\Columns\TextColumn::make('creditAccount.account_name')
                    ->label('Akun Kredit')
                    ->searchable(),

                Tables\Columns\TextColumn::make('reviewedBy.name')
                    ->label('Disetujui Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
                Tables\Filters\Filter::make('tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->status === 'pending'),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => $record->status === 'pending'),

                Action::make('approve')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->iconButton()
                    ->visible(fn ($record) => $record->status === 'pending' && auth()->user()?->hasRole('kepala_cabang'))
                    ->requiresConfirmation()
                    ->modalHeading('Setujui Transaksi')
                    ->modalDescription('Apakah Anda yakin ingin menyetujui transaksi ini? Sistem akan otomatis memproses jurnal akuntansi.')
                    ->action(function ($record) {
                        try {
                            // Mulai transaksi database
                            DB::beginTransaction();
                            
                            // Debug log untuk melihat data awal
                            Log::info('Starting manual journal approval process', [
                                'journal_id' => $record->id,
                                'nama_transaksi' => $record->nama_transaksi,
                                'nominal' => $record->nominal
                            ]);
                            
                            // 1. Update status transaksi
                            $record->status = 'approved';
                            $record->reviewed_by = auth()->id();
                            $record->save();
                            
                            // 2. Proses jurnal akuntansi
                            if (!$record->journal_account_transaction_debit_id || 
                                !$record->journal_account_transaction_credit_id) {
                                throw new \Exception("Journal accounts not configured");
                            }
                            
                            // 2.1 Akun debit
                            $debitAccount = JournalAccount::find($record->journal_account_transaction_debit_id);
                            if (!$debitAccount) {
                                throw new \Exception("Debit journal account not found with ID: {$record->journal_account_transaction_debit_id}");
                            }
                            
                            Log::info('Found debit account', [
                                'account_id' => $debitAccount->id,
                                'account_name' => $debitAccount->account_name,
                                'account_position' => $debitAccount->account_position,
                                'current_balance' => $debitAccount->balance
                            ]);
                            
                            // Update saldo akun debit
                            $oldDebitBalance = $debitAccount->balance;
                            if ($debitAccount->account_position === 'debit') {
                                // Akun dengan posisi normal debit: debit menambah saldo
                                $debitAccount->balance += $record->nominal;
                            } else {
                                // Akun dengan posisi normal kredit: debit mengurangi saldo
                                $debitAccount->balance -= $record->nominal;
                            }
                            $debitAccount->save();
                            
                            Log::info('Updated debit account', [
                                'account_id' => $debitAccount->id,
                                'old_balance' => $oldDebitBalance,
                                'new_balance' => $debitAccount->balance
                            ]);
                            
                            // 2.2 Akun kredit
                            $creditAccount = JournalAccount::find($record->journal_account_transaction_credit_id);
                            if (!$creditAccount) {
                                throw new \Exception("Credit journal account not found with ID: {$record->journal_account_transaction_credit_id}");
                            }
                            
                            Log::info('Found credit account', [
                                'account_id' => $creditAccount->id,
                                'account_name' => $creditAccount->account_name,
                                'account_position' => $creditAccount->account_position,
                                'current_balance' => $creditAccount->balance
                            ]);
                            
                            // Update saldo akun kredit
                            $oldCreditBalance = $creditAccount->balance;
                            if ($creditAccount->account_position === 'credit') {
                                // Akun dengan posisi normal kredit: kredit menambah saldo
                                $creditAccount->balance += $record->nominal;
                            } else {
                                // Akun dengan posisi normal debit: kredit mengurangi saldo
                                $creditAccount->balance -= $record->nominal;
                            }
                            $creditAccount->save();
                            
                            Log::info('Updated credit account', [
                                'account_id' => $creditAccount->id,
                                'old_balance' => $oldCreditBalance,
                                'new_balance' => $creditAccount->balance
                            ]);
                            
                            // 3. Generate transaction number
                            $transactionNumber = 'TRX-MAN-' . $record->id . '-' . now()->format('Ymd-His');
                            
                            // 4. Tambah entri jurnal umum untuk debit
                            JurnalUmum::create([
                                'tanggal_bayar' => $record->tanggal,
                                'no_ref' => 'MAN-' . $record->id,
                                'no_transaksi' => $transactionNumber,
                                'akun_id' => $debitAccount->id,
                                'keterangan' => $record->nama_transaksi . ($record->catatan ? ' - ' . $record->catatan : ''),
                                'debet' => $record->nominal,
                                'kredit' => 0,
                                'jurnal_manual_id' => $record->id,
                            ]);
                            
                            // 5. Tambah entri jurnal umum untuk kredit
                            JurnalUmum::create([
                                'tanggal_bayar' => $record->tanggal,
                                'no_ref' => 'MAN-' . $record->id,
                                'no_transaksi' => $transactionNumber,
                                'akun_id' => $creditAccount->id,
                                'keterangan' => $record->nama_transaksi . ($record->catatan ? ' - ' . $record->catatan : ''),
                                'debet' => 0,
                                'kredit' => $record->nominal,
                                'jurnal_manual_id' => $record->id,
                            ]);
                            
                            Log::info('Created journal entries', [
                                'journal_id' => $record->id,
                                'transaction_number' => $transactionNumber,
                                'debit_account' => $debitAccount->account_name,
                                'credit_account' => $creditAccount->account_name,
                                'amount' => $record->nominal
                            ]);
                            
                            // Commit transaksi jika semua berhasil
                            DB::commit();

                            Log::info('Manual journal approval completed successfully', [
                                'journal_id' => $record->id
                            ]);

                            Notification::make()
                                ->title('Transaksi berhasil disetujui')
                                ->body('Jurnal akuntansi telah diproses dan saldo akun telah diperbarui.')
                                ->success()
                                ->send();
                                
                        } catch (\Exception $e) {
                            // Rollback transaksi jika terjadi kesalahan
                            DB::rollBack();
                            
                            Log::error('Error approving manual journal: ' . $e->getMessage(), [
                                'journal_id' => $record->id,
                                'exception' => $e,
                                'trace' => $e->getTraceAsString()
                            ]);
                            
                            Notification::make()
                                ->title('Error menyetujui transaksi')
                                ->body('Terjadi kesalahan: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('reject')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->iconButton()
                    ->visible(fn ($record) => $record->status === 'pending' && auth()->user()?->hasRole('kepala_cabang'))
                    ->requiresConfirmation()
                    ->modalHeading('Tolak Transaksi')
                    ->modalDescription('Apakah Anda yakin ingin menolak transaksi ini?')
                    ->form([
                        Textarea::make('rejected_reason')
                            ->label('Alasan Penolakan')
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->status = 'rejected';
                        $record->reviewed_by = auth()->id();
                        $record->catatan = $record->catatan
                            ? $record->catatan . "\n\n[Ditolak]: " . $data['rejected_reason']
                            : "[Ditolak]: " . $data['rejected_reason'];
                        $record->save();

                        Notification::make()
                            ->title('Transaksi ditolak')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->hasRole('kepala_cabang'))
                        ->before(function ($records) {
                            // Cek apakah ada record dengan status approved
                            $hasApproved = $records->contains(fn ($record) => $record->status === 'approved');
                            
                            if ($hasApproved) {
                                Notification::make()
                                    ->title('Tidak dapat menghapus jurnal yang sudah disetujui')
                                    ->danger()
                                    ->send();
                                
                                $this->halt();
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJurnalManuals::route('/'),
            'create' => Pages\CreateJurnalManual::route('/create'),
            'edit' => Pages\EditJurnalManual::route('/{record}/edit'),
        ];
    }
}
