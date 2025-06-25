<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class LaporanLabaRugi extends Model
{
    protected $table = 'laporan_laba_rugi';
    
    protected $fillable = [
        'bulan',
        'tahun',
        'akun_id',
        'kode_akun',
        'nama_akun',
        'jenis_akun',
        'klasifikasi',
        'jumlah'
    ];

    protected $casts = [
        'jumlah' => 'decimal:2',
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

    // Scope untuk filter berdasarkan klasifikasi
    public function scopeKlasifikasi($query, $klasifikasi)
    {
        return $query->where('klasifikasi', $klasifikasi);
    }

    // Method untuk mendapatkan total pendapatan
    public static function getTotalPendapatan($bulan, $tahun)
    {
        return static::periode($bulan, $tahun)
                    ->klasifikasi('pendapatan')
                    ->sum('jumlah');
    }

    // Method untuk mendapatkan total beban
    public static function getTotalBeban($bulan, $tahun)
    {
        return static::periode($bulan, $tahun)
                    ->klasifikasi('beban')
                    ->sum('jumlah');
    }

    // Method untuk mendapatkan laba/rugi
    public static function getLabaRugi($bulan, $tahun)
    {
        $totalPendapatan = static::getTotalPendapatan($bulan, $tahun);
        $totalBeban = static::getTotalBeban($bulan, $tahun);
        
        return $totalPendapatan - $totalBeban;
    }

    // Method untuk cek apakah laba atau rugi
    public static function isProfit($bulan, $tahun)
    {
        return static::getLabaRugi($bulan, $tahun) >= 0;
    }
}
