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
        $purchasePrice = 1000;
        $marginPercentage = 20;

        $expectedSellingPrice = 1200; // 1000 + 20%
        $actualSellingPrice = IslamicFinanceCalculator::murabahahSellingPrice($purchasePrice, $marginPercentage);

        $this->assertEquals($expectedSellingPrice, $actualSellingPrice);
    }

    #[Test]
    public function it_calculates_murabahah_monthly_installment()
    {
        $sellingPrice = 1200;
        $tenorMonths = 12;

        $expectedMonthlyInstallment = 100.0;
        $actualMonthlyInstallment = IslamicFinanceCalculator::murabahahMonthlyInstallment($sellingPrice, $tenorMonths);

        $this->assertEquals($expectedMonthlyInstallment, $actualMonthlyInstallment);
    }

    #[Test]
    public function it_calculates_mudharabah_expected_profit()
    {
        $capitalAmount = 10000;
        $expectedProfitRate = 20;
        $sharingRatio = 60;

        $expectedProfit = 1200;
        $actualProfit = IslamicFinanceCalculator::mudharabahExpectedProfit($capitalAmount, $expectedProfitRate, $sharingRatio);

        $this->assertEquals($expectedProfit, $actualProfit);
    }

    #[Test]
    public function it_calculates_mudharabah_total_return()
    {
        $capitalAmount = 10000;
        $profitShare = 1200;

        $expectedTotalReturn = 11200;
        $actualTotalReturn = IslamicFinanceCalculator::mudharabahTotalReturn($capitalAmount, $profitShare);

        $this->assertEquals($expectedTotalReturn, $actualTotalReturn);
    }

    #[Test]
    public function it_calculates_musyarakah_expected_profit()
    {
        $totalCapital = 20000;
        $koperasiCapital = 8000;
        $expectedProfitRate = 25;
        $koperasiRatio = 40;

        $expectedProfit = 2000;
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
        $penaltyRate = 0.5;
        $daysLate = 5;

        $expectedPenalty = 25000;
        $actualPenalty = IslamicFinanceCalculator::calculateLatePenalty($installmentAmount, $penaltyRate, $daysLate);

        $this->assertEquals($expectedPenalty, $actualPenalty);
    }

    #[Test]
    public function it_calculates_remaining_principal()
    {
        $loanAmount = 10000000;
        $totalPaid = 6000000;
        $marginAmount = 1000000;

        $expectedRemainingPrincipal = 5000000;
        $actualRemaining = IslamicFinanceCalculator::calculateRemainingPrincipal($loanAmount, $totalPaid, $marginAmount);

        $this->assertEquals($expectedRemainingPrincipal, $actualRemaining);
    }
}