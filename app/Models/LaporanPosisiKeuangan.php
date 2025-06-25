<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class LaporanPosisiKeuangan extends Model
{
    protected $table = 'laporan_posisi_keuangan';
    
    protected $fillable = [
        'bulan',
        'tahun',
        'akun_id',
        'kode_akun',
        'nama_akun',
        'jenis_akun',
        'klasifikasi',
        'saldo'
    ];

    protected $casts = [
        'saldo' => 'decimal:2',
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

    // Method untuk mendapatkan total aktiva lancar
    public static function getTotalAktivaLancar($bulan, $tahun)
    {
        return static::periode($bulan, $tahun)
                    ->klasifikasi('aktiva_lancar')
                    ->sum('saldo');
    }

    // Method untuk mendapatkan total aktiva tetap
    public static function getTotalAktivaTetap($bulan, $tahun)
    {
        return static::periode($bulan, $tahun)
                    ->klasifikasi('aktiva_tetap')
                    ->sum('saldo');
    }

    // Method untuk mendapatkan total kewajiban
    public static function getTotalKewajiban($bulan, $tahun)
    {
        return static::periode($bulan, $tahun)
                    ->klasifikasi('kewajiban')
                    ->sum('saldo');
    }

    // Method untuk mendapatkan total ekuitas
    public static function getTotalEkuitas($bulan, $tahun)
    {
        return static::periode($bulan, $tahun)
                    ->klasifikasi('ekuitas')
                    ->sum('saldo');
    }

    // Method untuk mendapatkan total aktiva
    public static function getTotalAktiva($bulan, $tahun)
    {
        return static::getTotalAktivaLancar($bulan, $tahun) + 
               static::getTotalAktivaTetap($bulan, $tahun);
    }

    // Method untuk mendapatkan total pasiva
    public static function getTotalPasiva($bulan, $tahun)
    {
        return static::getTotalKewajiban($bulan, $tahun) + 
               static::getTotalEkuitas($bulan, $tahun);
    }

    // Method untuk cek apakah neraca seimbang
    public static function isBalanced($bulan, $tahun)
    {
        $totalAktiva = static::getTotalAktiva($bulan, $tahun);
        $totalPasiva = static::getTotalPasiva($bulan, $tahun);
        
        return abs($totalAktiva - $totalPasiva) < 0.01;
    }
}
