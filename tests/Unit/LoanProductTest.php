<?php

namespace Tests\Unit;

use App\Models\LoanProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class LoanProductTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_a_loan_product()
    {
        $loanProductData = [
            'name' => 'Test Loan Product',
            'code' => 'TLP001',
            'contract_type' => 'Murabahah',
            'min_amount' => 1000,
            'max_amount' => 50000,
            'min_rate' => 5.0,
            'max_rate' => 15.0,
            'tenor_months' => '12',
            'usage_purposes' => 'Buka warung, Bengkel',
        ];

        $loanProduct = LoanProduct::create($loanProductData);

        $this->assertInstanceOf(LoanProduct::class, $loanProduct);
        $this->assertEquals('Test Loan Product', $loanProduct->name);
        $this->assertEquals('Murabahah', $loanProduct->contract_type);
        $this->assertEquals('12', $loanProduct->tenor_months);
    }

    #[Test]
    public function it_can_have_different_contract_types()
    {
        // Sesuaikan factory untuk kolom yang ada di migrasi
        $murabahah = LoanProduct::create([
            'contract_type' => 'Murabahah',
            'name' => 'Murabahah Product',
            'code' => 'MUR001',
            'min_amount' => 1000,
            'max_amount' => 50000,
            'min_rate' => 5.0,
            'max_rate' => 15.0,
            'tenor_months' => '12',
            'usage_purposes' => 'Buka warung, Bengkel',
        ]);
        
        $mudharabah = LoanProduct::create([
            'contract_type' => 'Mudharabah',
            'name' => 'Mudharabah Product',
            'code' => 'MUD001',
            'min_amount' => 1000,
            'max_amount' => 50000,
            'min_rate' => 5.0,
            'max_rate' => 15.0,
            'tenor_months' => '12',
            'usage_purposes' => 'Buka warung, Bengkel',
        ]);
        
        $musyarakah = LoanProduct::create([
            'contract_type' => 'Musyarakah',
            'name' => 'Musyarakah Product',
            'code' => 'MUS001',
            'min_amount' => 1000,
            'max_amount' => 50000,
            'min_rate' => 5.0,
            'max_rate' => 15.0,
            'tenor_months' => '12',
            'usage_purposes' => 'Buka warung, Bengkel',
        ]);

        $this->assertEquals('Murabahah', $murabahah->contract_type);
        $this->assertEquals('Mudharabah', $mudharabah->contract_type);
        $this->assertEquals('Musyarakah', $musyarakah->contract_type);
    }
}