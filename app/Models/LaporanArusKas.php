<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class LaporanArusKas extends Model
{
    protected $table = 'laporan_arus_kas';
    
    protected $fillable = [
        'bulan',
        'tahun',
        'tanggal',
        'keterangan',
        'akun_id',
        'nama_akun',
        'kategori',
        'jenis',
        'jumlah'
    ];

    protected $casts = [
        'tanggal' => 'date',
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

    // Helper method untuk format tanggal
    public function getFormattedTanggalAttribute(): string
    {
        return $this->tanggal ? $this->tanggal->format('d/m/Y') : '-';
    }

    // Scope untuk filter berdasarkan periode
    public function scopePeriode($query, $bulan, $tahun)
    {
        return $query->where('bulan', $bulan)->where('tahun', $tahun);
    }

    // Scope untuk filter berdasarkan kategori
    public function scopeKategori($query, $kategori)
    {
        return $query->where('kategori', $kategori);
    }

    // Scope untuk filter berdasarkan jenis
    public function scopeJenis($query, $jenis)
    {
        return $query->where('jenis', $jenis);
    }

    // Method untuk mendapatkan total arus operasi
    public static function getTotalArusOperasi($bulan, $tahun)
    {
        $masuk = static::periode($bulan, $tahun)
                      ->kategori('operasi')
                      ->jenis('masuk')
                      ->sum('jumlah');
        
        $keluar = static::periode($bulan, $tahun)
                       ->kategori('operasi')
                       ->jenis('keluar')
                       ->sum('jumlah');
        
        return $masuk - $keluar;
    }

    // Method untuk mendapatkan total arus investasi
    public static function getTotalArusInvestasi($bulan, $tahun)
    {
        $masuk = static::periode($bulan, $tahun)
                      ->kategori('investasi')
                      ->jenis('masuk')
                      ->sum('jumlah');
        
        $keluar = static::periode($bulan, $tahun)
                       ->kategori('investasi')
                       ->jenis('keluar')
                       ->sum('jumlah');
        
        return $masuk - $keluar;
    }

    // Method untuk mendapatkan total arus pendanaan
    public static function getTotalArusPendanaan($bulan, $tahun)
    {
        $masuk = static::periode($bulan, $tahun)
                      ->kategori('pendanaan')
                      ->jenis('masuk')
                      ->sum('jumlah');
        
        $keluar = static::periode($bulan, $tahun)
                       ->kategori('pendanaan')
                       ->jenis('keluar')
                       ->sum('jumlah');
        
        return $masuk - $keluar;
    }

    // Method untuk mendapatkan arus kas bersih
    public static function getArusKasBersih($bulan, $tahun)
    {
        return static::getTotalArusOperasi($bulan, $tahun) +
               static::getTotalArusInvestasi($bulan, $tahun) +
               static::getTotalArusPendanaan($bulan, $tahun);
    }
}
