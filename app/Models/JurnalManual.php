<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JurnalManual extends Model
{
    protected $table = 'jurnal_manual';

    protected $fillable = [
        'nama_transaksi',
        'tanggal',
        'nominal',
        'catatan',
        'status',
        'reviewed_by',
        'created_by',
        'journal_account_transaction_debit_id',
        'journal_account_transaction_credit_id',
    ];

    // Relasi ke user yang mereview
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // Relasi ke user yang membuat
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relasi ke akun debit
    public function debitAccount(): BelongsTo
    {
        return $this->belongsTo(JournalAccount::class, 'journal_account_transaction_debit_id');
    }

    // Relasi ke akun kredit
    public function creditAccount(): BelongsTo
    {
        return $this->belongsTo(JournalAccount::class, 'journal_account_transaction_credit_id');
    }
}
