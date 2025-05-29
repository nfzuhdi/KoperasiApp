<?php

namespace Tests\Unit;

use App\Models\Loan;
use App\Models\LoanProduct;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class LoanTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_a_loan()
    {
        // Create related models
        $member = new Member();
        $member->member_id = 'MMR00001';
        $member->nik = '1234567890123456';
        $member->full_name = 'Test Member';
        $member->save();
        
        $loanProduct = LoanProduct::create([
            'contract_type' => 'Murabahah',
            'name' => 'Test Product',
            'code' => 'TP001',
            'min_amount' => 1000,
            'max_amount' => 50000,
            'min_rate' => 5.0,
            'max_rate' => 15.0,
            'tenor_months' => '12',
            'usage_purposes' => 'Buka warung, Bengkel',
        ]);
        
        $user = User::factory()->create();

        $loanData = [
            'member_id' => $member->id,
            'loan_product_id' => $loanProduct->id,
            'created_by' => $user->id,
            'loan_amount' => 5000000,
            'margin_amount' => 10.00,
            'purchase_price' => 5000000,
            'status' => 'pending',
        ];

        $loan = Loan::create($loanData);

        $this->assertInstanceOf(Loan::class, $loan);
        $this->assertEquals($member->id, $loan->member_id);
        $this->assertEquals(5000000, $loan->loan_amount);
        $this->assertEquals('pending', $loan->status);
    }

    #[Test]
    public function it_calculates_selling_price_for_murabahah_loans()
    {
        // Create related models
        $member = new Member();
        $member->member_id = 'MMR00002';
        $member->nik = '1234567890123456';
        $member->full_name = 'Test Member';
        $member->save();
        
        $loanProduct = LoanProduct::create([
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
        
        $user = User::factory()->create();

        // Create Murabahah loan
        $loan = Loan::create([
            'member_id' => $member->id,
            'loan_product_id' => $loanProduct->id,
            'created_by' => $user->id,
            'purchase_price' => 5000000,
            'margin_amount' => 10.00, // 10%
            'loan_amount' => 5000000,
            'status' => 'pending',
        ]);

        // Expected selling price: purchase_price + (purchase_price * margin_amount / 100)
        $expectedSellingPrice = 5000000 + (5000000 * 10 / 100);
        
        $this->assertEquals($expectedSellingPrice, $loan->selling_price);
    }
}





