<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JurnalUmum extends Model
{
    protected $table = 'jurnal_umum';
    
    protected $fillable = [
        'tanggal_bayar',
        'no_ref',
        'no_transaksi',
        'akun_id',
        'keterangan',
        'debet',
        'kredit',
        'saving_payment_id',
        'loan_payment_id'
    ];

    protected $casts = [
        'tanggal_bayar' => 'date',
        'debet' => 'decimal:2',
        'kredit' => 'decimal:2'
    ];

    // Relasi ke akun jurnal
    public function akun(): BelongsTo
    {
        return $this->belongsTo(JournalAccount::class, 'akun_id');
    }

    // Relasi ke pembayaran simpanan
    public function savingPayment(): BelongsTo
    {
        return $this->belongsTo(SavingPayment::class);
    }

    // Relasi ke pembayaran pinjaman
    public function loanPayment(): BelongsTo
    {
        return $this->belongsTo(LoanPayment::class);
    }

    // Scope untuk filter berdasarkan rentang tanggal
    public function scopeDateBetween($query, $from, $to)
    {
        return $query->whereBetween('tanggal_bayar', [$from, $to]);
    }

    // Scope untuk mencari berdasarkan nomor referensi
    public function scopeReferenceNumber($query, $ref)
    {
        return $query->where('no_ref', 'like', "%{$ref}%");
    }

    // Method untuk mengecek apakah jurnal seimbang
    public function isBalanced(): bool
    {
        return $this->debet === $this->kredit;
    }

    protected static function boot()
    {
        parent::boot();

        // After jurnal umum is saved, update buku besar
        static::saved(function ($jurnalUmum) {
            // Get all entries for this account on this date
            $entries = static::where('akun_id', $jurnalUmum->akun_id)
                ->whereYear('tanggal_bayar', $jurnalUmum->tanggal_bayar->year)
                ->whereMonth('tanggal_bayar', $jurnalUmum->tanggal_bayar->month)
                ->orderBy('tanggal_bayar')
                ->orderBy('id')
                ->get();

            // Calculate running balance
            $saldo = 0;
            foreach ($entries as $entry) {
                $saldo += $entry->debet - $entry->kredit;

                // Create or update buku besar entry
                BukuBesar::updateOrCreate(
                    [
                        'akun_id' => $entry->akun_id,
                        'tanggal' => $entry->tanggal_bayar,
                        'keterangan' => $entry->keterangan,
                    ],
                    [
                        'debet' => $entry->debet,
                        'kredit' => $entry->kredit,
                        'saldo' => $saldo,
                        'bulan' => $entry->tanggal_bayar->month,
                        'tahun' => $entry->tanggal_bayar->year,
                    ]
                );
            }
        });
    }
}