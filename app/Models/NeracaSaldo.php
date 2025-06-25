<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class NeracaSaldo extends Model
{
    protected $table = 'neraca_saldo';
    
    protected $fillable = [
        'bulan',
        'tahun',
        'akun_id',
        'kode_akun',
        'nama_akun',
        'jenis_akun',
        'saldo_debet',
        'saldo_kredit'
    ];

    protected $casts = [
        'saldo_debet' => 'decimal:2',
        'saldo_kredit' => 'decimal:2',
        'bulan' => 'integer',
        'tahun' => 'integer',
    ];

    // Relasi ke akun jurnal
    public function akun(): BelongsTo
    {
        return $this->belongsTo(JournalAccount::class, 'akun_id');
    }

    // Helper method untuk mendapatkan nama bulan
    public function getNamaBulanAttribute(): string
    {
        return Carbon::createFromDate($this->tahun, $this->bulan, 1)
                    ->locale('id')
                    ->monthName;
    }

    // Helper method untuk mendapatkan periode
    public function getPeriodeAttribute(): string
    {
        return $this->nama_bulan . ' ' . $this->tahun;
    }

    // Scope untuk filter berdasarkan periode
    public function scopePeriode($query, $bulan, $tahun)
    {
        return $query->where('bulan', $bulan)->where('tahun', $tahun);
    }

    // Scope untuk filter berdasarkan jenis akun
    public function scopeJenisAkun($query, $jenisAkun)
    {
        return $query->where('jenis_akun', $jenisAkun);
    }

    // Method untuk mendapatkan total debet
    public static function getTotalDebet($bulan, $tahun)
    {
        return static::periode($bulan, $tahun)->sum('saldo_debet');
    }

    // Method untuk mendapatkan total kredit
    public static function getTotalKredit($bulan, $tahun)
    {
        return static::periode($bulan, $tahun)->sum('saldo_kredit');
    }

    // Method untuk cek apakah neraca seimbang
    public static function isBalanced($bulan, $tahun)
    {
        $totalDebet = static::getTotalDebet($bulan, $tahun);
        $totalKredit = static::getTotalKredit($bulan, $tahun);
        
        return abs($totalDebet - $totalKredit) < 0.01;
    }
}
