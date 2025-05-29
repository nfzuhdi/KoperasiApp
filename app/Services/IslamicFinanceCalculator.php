<?php

namespace App\Services;

class IslamicFinanceCalculator
{
    /**
     * MURABAHAH (Jual Beli)
     * -----------------------
     */
    
    /**
     * Menghitung harga jual (selling price) untuk akad Murabahah
     *
     * @param float $purchasePrice Harga beli/pokok pinjaman
     * @param float $marginPercentage Persentase margin
     * @return float Harga jual
     */
    public static function murabahahSellingPrice(float $purchasePrice, float $marginPercentage): float
    {
        return $purchasePrice + ($purchasePrice * $marginPercentage / 100);
    }

    /**
     * Menghitung nilai margin dalam nominal untuk akad Murabahah
     *
     * @param float $purchasePrice Harga beli/pokok pinjaman
     * @param float $marginPercentage Persentase margin
     * @return float Nilai margin
     */
    public static function murabahahMarginAmount(float $purchasePrice, float $marginPercentage): float
    {
        return $purchasePrice * $marginPercentage / 100;
    }

    /**
     * Menghitung angsuran bulanan untuk akad Murabahah
     *
     * @param float $sellingPrice Harga jual
     * @param int $tenorMonths Jangka waktu dalam bulan
     * @return float Angsuran per bulan
     */
    public static function murabahahMonthlyInstallment(float $sellingPrice, int $tenorMonths): float
    {
        return $sellingPrice / $tenorMonths;
    }

    /**
     * MUDHARABAH (Bagi Hasil)
     * -----------------------
     */
    
    /**
     * Menghitung bagi hasil untuk akad Mudharabah
     *
     * @param float $capitalAmount Modal yang diberikan
     * @param float $profitAmount Total keuntungan usaha
     * @param float $sharingRatio Nisbah bagi hasil untuk shahibul mal (dalam persen)
     * @return float Bagi hasil untuk pemberi modal (shahibul mal)
     */
    public static function mudharabahProfit(float $capitalAmount, float $profitAmount, float $sharingRatio): float
    {
        return $profitAmount * ($sharingRatio / 100);
    }

    /**
     * Menghitung proyeksi bagi hasil Mudharabah berdasarkan proyeksi keuntungan
     *
     * @param float $capitalAmount Modal yang diberikan
     * @param float $expectedProfitRate Proyeksi tingkat keuntungan (dalam persen)
     * @param float $sharingRatio Nisbah bagi hasil untuk shahibul mal (dalam persen)
     * @return float Proyeksi bagi hasil
     */
    public static function mudharabahExpectedProfit(float $capitalAmount, float $expectedProfitRate, float $sharingRatio): float
    {
        $expectedProfit = $capitalAmount * ($expectedProfitRate / 100);
        return $expectedProfit * ($sharingRatio / 100);
    }

    /**
     * Menghitung pengembalian modal + bagi hasil untuk Mudharabah
     *
     * @param float $capitalAmount Modal awal
     * @param float $profitShare Bagi hasil
     * @return float Total pengembalian
     */
    public static function mudharabahTotalReturn(float $capitalAmount, float $profitShare): float
    {
        return $capitalAmount + $profitShare;
    }

    /**
     * MUSYARAKAH (Kemitraan)
     * -----------------------
     */
    
    /**
     * Menghitung bagi hasil untuk akad Musyarakah
     *
     * @param float $totalCapital Total modal usaha
     * @param float $partnerCapital Modal dari mitra (anggota)
     * @param float $koperasiCapital Modal dari koperasi
     * @param float $totalProfit Total keuntungan usaha
     * @param float $koperasiRatio Nisbah bagi hasil untuk koperasi (dalam persen)
     * @return array Bagi hasil untuk masing-masing pihak
     */
    public static function musyarakahProfitSharing(
        float $totalCapital, 
        float $partnerCapital, 
        float $koperasiCapital, 
        float $totalProfit, 
        float $koperasiRatio
    ): array {
        // Hitung rasio kontribusi modal
        $partnerCapitalRatio = $partnerCapital / $totalCapital;
        $koperasiCapitalRatio = $koperasiCapital / $totalCapital;
        
        // Hitung bagi hasil berdasarkan nisbah yang disepakati
        $koperasiProfit = $totalProfit * ($koperasiRatio / 100);
        $partnerProfit = $totalProfit - $koperasiProfit;
        
        return [
            'koperasi_profit' => $koperasiProfit,
            'partner_profit' => $partnerProfit,
            'koperasi_capital_ratio' => $koperasiCapitalRatio * 100, // dalam persen
            'partner_capital_ratio' => $partnerCapitalRatio * 100, // dalam persen
        ];
    }

    /**
     * Menghitung proyeksi bagi hasil Musyarakah berdasarkan proyeksi keuntungan
     *
     * @param float $totalCapital Total modal usaha
     * @param float $koperasiCapital Modal dari koperasi
     * @param float $expectedProfitRate Proyeksi tingkat keuntungan (dalam persen)
     * @param float $koperasiRatio Nisbah bagi hasil untuk koperasi (dalam persen)
     * @return float Proyeksi bagi hasil untuk koperasi
     */
    public static function musyarakahExpectedProfit(
        float $totalCapital, 
        float $koperasiCapital, 
        float $expectedProfitRate, 
        float $koperasiRatio
    ): float {
        $expectedProfit = $totalCapital * ($expectedProfitRate / 100);
        return $expectedProfit * ($koperasiRatio / 100);
    }

    /**
     * UMUM
     * -----------------------
     */
    
    /**
     * Menghitung denda keterlambatan
     *
     * @param float $installmentAmount Jumlah angsuran
     * @param float $penaltyRate Persentase denda
     * @param int $daysLate Jumlah hari keterlambatan
     * @return float Jumlah denda
     */
    public static function calculateLatePenalty(float $installmentAmount, float $penaltyRate, int $daysLate): float
    {
        return $installmentAmount * ($penaltyRate / 100) * $daysLate;
    }

    /**
     * Menghitung sisa pokok pinjaman
     *
     * @param float $loanAmount Jumlah pinjaman awal
     * @param float $totalPaid Total yang sudah dibayarkan
     * @param float $marginAmount Total margin
     * @return float Sisa pokok pinjaman
     */
    public static function calculateRemainingPrincipal(float $loanAmount, float $totalPaid, float $marginAmount): float
    {
        $paidPrincipal = max(0, $totalPaid - $marginAmount);
        return max(0, $loanAmount - $paidPrincipal);
    }
}