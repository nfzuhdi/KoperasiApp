<?php

namespace Tests\Unit;

use App\Services\IslamicFinanceCalculator;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class IslamicFinanceCalculatorTest extends TestCase
{
    #[Test]
    public function it_calculates_murabahah_selling_price()
    {
        $purchasePrice = 10000000;
        $marginPercentage = 10;
        
        $expectedSellingPrice = 11000000; // 10000000 + (10000000 * 10 / 100)
        $actualSellingPrice = IslamicFinanceCalculator::murabahahSellingPrice($purchasePrice, $marginPercentage);
        
        $this->assertEquals($expectedSellingPrice, $actualSellingPrice);
    }
    
    #[Test]
    public function it_calculates_murabahah_margin_amount()
    {
        $purchasePrice = 10000000;
        $marginPercentage = 10;
        
        $expectedMarginAmount = 1000000; // 10000000 * 10 / 100
        $actualMarginAmount = IslamicFinanceCalculator::murabahahMarginAmount($purchasePrice, $marginPercentage);
        
        $this->assertEquals($expectedMarginAmount, $actualMarginAmount);
    }
    
    #[Test]
    public function it_calculates_murabahah_monthly_installment()
    {
        $sellingPrice = 11000000;
        $tenorMonths = 12;
        
        $expectedMonthlyInstallment = 916666.6666666666; // 11000000 / 12
        $actualMonthlyInstallment = IslamicFinanceCalculator::murabahahMonthlyInstallment($sellingPrice, $tenorMonths);
        
        $this->assertEquals($expectedMonthlyInstallment, $actualMonthlyInstallment);
    }
    
    #[Test]
    public function it_calculates_mudharabah_profit()
    {
        $capitalAmount = 10000000;
        $profitAmount = 2000000;
        $sharingRatio = 60; // 60% untuk shahibul mal
        
        $expectedProfit = 1200000; // 2000000 * (60 / 100)
        $actualProfit = IslamicFinanceCalculator::mudharabahProfit($capitalAmount, $profitAmount, $sharingRatio);
        
        $this->assertEquals($expectedProfit, $actualProfit);
    }
    
    #[Test]
    public function it_calculates_mudharabah_expected_profit()
    {
        $capitalAmount = 10000000;
        $expectedProfitRate = 20; // 20% proyeksi keuntungan
        $sharingRatio = 60; // 60% untuk shahibul mal
        
        $expectedProfit = 1200000; // (10000000 * 20 / 100) * (60 / 100)
        $actualProfit = IslamicFinanceCalculator::mudharabahExpectedProfit($capitalAmount, $expectedProfitRate, $sharingRatio);
        
        $this->assertEquals($expectedProfit, $actualProfit);
    }
    
    #[Test]
    public function it_calculates_mudharabah_total_return()
    {
        $capitalAmount = 10000000;
        $profitShare = 1200000;
        
        $expectedTotalReturn = 11200000; // 10000000 + 1200000
        $actualTotalReturn = IslamicFinanceCalculator::mudharabahTotalReturn($capitalAmount, $profitShare);
        
        $this->assertEquals($expectedTotalReturn, $actualTotalReturn);
    }
    
    #[Test]
    public function it_calculates_musyarakah_profit_sharing()
    {
        $totalCapital = 20000000;
        $partnerCapital = 12000000;
        $koperasiCapital = 8000000;
        $totalProfit = 5000000;
        $koperasiRatio = 40; // 40% untuk koperasi
        
        $expectedResult = [
            'koperasi_profit' => 2000000, // 5000000 * (40 / 100)
            'partner_profit' => 3000000, // 5000000 - 2000000
            'koperasi_capital_ratio' => 40, // (8000000 / 20000000) * 100
            'partner_capital_ratio' => 60, // (12000000 / 20000000) * 100
        ];
        
        $actualResult = IslamicFinanceCalculator::musyarakahProfitSharing(
            $totalCapital,
            $partnerCapital,
            $koperasiCapital,
            $totalProfit,
            $koperasiRatio
        );
        
        $this->assertEquals($expectedResult, $actualResult);
    }
    
    #[Test]
    public function it_calculates_musyarakah_expected_profit()
    {
        $totalCapital = 20000000;
        $koperasiCapital = 8000000;
        $expectedProfitRate = 25; // 25% proyeksi keuntungan
        $koperasiRatio = 40; // 40% untuk koperasi
        
        $expectedProfit = 2000000; // (20000000 * 25 / 100) * (40 / 100)
        $actualProfit = IslamicFinanceCalculator::musyarakahExpectedProfit(
            $totalCapital,
            $koperasiCapital,
            $expectedProfitRate,
            $koperasiRatio
        );
        
        $this->assertEquals($expectedProfit, $actualProfit);
    }
    
    #[Test]
    public function it_calculates_late_penalty()
    {
        $installmentAmount = 1000000;
        $penaltyRate = 0.5; // 0.5% per hari
        $daysLate = 5;
        
        $expectedPenalty = 25000; // 1000000 * (0.5 / 100) * 5
        $actualPenalty = IslamicFinanceCalculator::calculateLatePenalty($installmentAmount, $penaltyRate, $daysLate);
        
        $this->assertEquals($expectedPenalty, $actualPenalty);
    }
    
    #[Test]
    public function it_calculates_remaining_principal()
    {
        $loanAmount = 10000000;
        $totalPaid = 6000000;
        $marginAmount = 1000000;
        
        $expectedRemainingPrincipal = 5000000; // 10000000 - (6000000 - 1000000)
        $actualRemainingPrincipal = IslamicFinanceCalculator::calculateRemainingPrincipal($loanAmount, $totalPaid, $marginAmount);
        
        $this->assertEquals($expectedRemainingPrincipal, $actualRemainingPrincipal);
    }
}
