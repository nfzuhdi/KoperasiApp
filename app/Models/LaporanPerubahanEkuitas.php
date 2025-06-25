<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class LaporanPerubahanEkuitas extends Model
{
    protected $table = 'laporan_perubahan_ekuitas';
    
    protected $fillable = [
        'bulan',
        'tahun',
        'akun_id',
        'kode_akun',
        'nama_akun',
        'jenis_akun',
        'saldo_awal',
        'perubahan',
        'saldo_akhir'
    ];

    protected $casts = [
        'saldo_awal' => 'decimal:2',
        'perubahan' => 'decimal:2',
        'saldo_akhir' => 'decimal:2',
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

    // Method untuk mendapatkan total saldo awal
    public static function getTotalSaldoAwal($bulan, $tahun)
    {
        return static::periode($bulan, $tahun)->sum('saldo_awal');
    }

    // Method untuk mendapatkan total perubahan
    public static function getTotalPerubahan($bulan, $tahun)
    {
        return static::periode($bulan, $tahun)->sum('perubahan');
    }

    // Method untuk mendapatkan total saldo akhir
    public static function getTotalSaldoAkhir($bulan, $tahun)
    {
        return static::periode($bulan, $tahun)->sum('saldo_akhir');
    }
}
