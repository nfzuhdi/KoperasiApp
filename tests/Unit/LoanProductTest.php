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
    public function it_can_create_a_murabahah_loan_product()
    {
        $murabahah = LoanProduct::create([
            'contract_type' => 'Murabahah',
            'name' => 'Murabahah Product',
            'code' => 'MUR001',
            'min_amount' => 10000,
            'max_amount' => 5000000,
            // Marginnya nominal, bukan rate
            'min_rate' => null,
            'max_rate' => null,
            'tenor_months' => '12',
            'usage_purposes' => 'Modal usaha kecil',
        ]);

        $this->assertEquals('Murabahah', $murabahah->contract_type);
        $this->assertNull($murabahah->min_rate);
        $this->assertNull($murabahah->max_rate);
    }

    #[Test]
    public function it_can_create_a_mudharabah_loan_product()
    {
        $mudharabah = LoanProduct::create([
            'contract_type' => 'Mudharabah',
            'name' => 'Mudharabah Product',
            'code' => 'MUD001',
            'min_amount' => 10000,
            'max_amount' => 5000000,
            'min_rate' => 10.0,
            'max_rate' => 20.0,
            'tenor_months' => '12',
            'usage_purposes' => 'Usaha dagang harian',
        ]);

        $this->assertEquals('Mudharabah', $mudharabah->contract_type);
        $this->assertEquals(10.0, $mudharabah->min_rate);
        $this->assertEquals(20.0, $mudharabah->max_rate);
    }

    #[Test]
    public function it_can_create_a_musyarakah_loan_product()
    {
        $musyarakah = LoanProduct::create([
            'contract_type' => 'Musyarakah',
            'name' => 'Musyarakah Product',
            'code' => 'MUS001',
            'min_amount' => 20000,
            'max_amount' => 10000000,
            'min_rate' => 8.5,
            'max_rate' => 18.0,
            'tenor_months' => '24',
            'usage_purposes' => 'Proyek kemitraan',
        ]);

        $this->assertEquals('Musyarakah', $musyarakah->contract_type);
        $this->assertEquals(8.5, $musyarakah->min_rate);
        $this->assertEquals(18.0, $musyarakah->max_rate);
    }
}