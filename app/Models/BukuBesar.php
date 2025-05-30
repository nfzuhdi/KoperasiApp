<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class BukuBesar extends Model
{
    protected $table = 'buku_besar';
    
    protected $fillable = [
        'akun_id',
        'tanggal',
        'keterangan',
        'debet',
        'kredit',
        'saldo',
        'bulan',
        'tahun'
    ];

    protected $casts = [
        'tanggal' => 'date',
        'debet' => 'decimal:2',
        'kredit' => 'decimal:2',
        'saldo' => 'decimal:2',
    ];

    // Add accessor for formatted date
    public function getFormattedTanggalAttribute()
    {
        return $this->tanggal ? $this->tanggal->format('d/m/Y') : '-';
    }

    public function akun(): BelongsTo
    {
        return $this->belongsTo(JournalAccount::class, 'akun_id');
    }
}