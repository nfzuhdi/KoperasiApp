<?php

namespace Tests\Unit;

use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\LoanProduct;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class LoanPaymentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_loan_payment_for_murabahah()
    {
        $member = Member::factory()->create();
        $user = User::factory()->create();

        $loanProduct = LoanProduct::create([
            'name' => 'Murabahah Product',
            'code' => 'MUR001',
            'contract_type' => 'Murabahah',
            'tenor_months' => '12',
            'usage_purposes' => 'Usaha Toko',
        ]);

        $loan = Loan::create([
            'member_id' => $member->id,
            'loan_product_id' => $loanProduct->id,
            'created_by' => $user->id,
            'purchase_price' => 1000,
            'margin_amount' => 200, // nominal
            'status' => 'pending',
        ]);

        $payment = LoanPayment::create([
            'loan_id' => $loan->id,
            'due_date' => now()->addMonth(),
            'month' => now()->month,
            'year' => now()->year,
            'amount' => 100,
            'payment_period' => 1,
            'status' => 'pending',
        ]);

        $this->assertInstanceOf(LoanPayment::class, $payment);
        $this->assertEquals(100, $payment->amount);
    }

    #[Test]
    public function it_can_create_loan_payment_for_mudharabah()
    {
        $member = Member::factory()->create();
        $user = User::factory()->create();

        $loanProduct = LoanProduct::create([
            'name' => 'Mudharabah Product',
            'code' => 'MUD001',
            'contract_type' => 'Mudharabah',
            'min_rate' => 5.0,
            'max_rate' => 15.0,
            'tenor_months' => '12',
            'usage_purposes' => 'Modal Usaha',
        ]);

        $loan = Loan::create([
            'member_id' => $member->id,
            'loan_product_id' => $loanProduct->id,
            'created_by' => $user->id,
            'loan_amount' => 5000000,
            'margin_amount' => 10.0, // persen
            'status' => 'pending',
        ]);

        $payment = LoanPayment::create([
            'loan_id' => $loan->id,
            'due_date' => now()->addMonth(),
            'month' => now()->month,
            'year' => now()->year,
            'amount' => 500000,
            'payment_period' => 1,
            'status' => 'pending',
        ]);

        $this->assertInstanceOf(LoanPayment::class, $payment);
        $this->assertEquals(500000, $payment->amount);
    }

    #[Test]
    public function it_can_create_loan_payment_for_musyarakah()
    {
        $member = Member::factory()->create();
        $user = User::factory()->create();

        $loanProduct = LoanProduct::create([
            'name' => 'Musyarakah Product',
            'code' => 'MUS001',
            'contract_type' => 'Musyarakah',
            'min_rate' => 6.0,
            'max_rate' => 12.0,
            'tenor_months' => '24',
            'usage_purposes' => 'Kemitraan',
        ]);

        $loan = Loan::create([
            'member_id' => $member->id,
            'loan_product_id' => $loanProduct->id,
            'created_by' => $user->id,
            'loan_amount' => 10000000,
            'margin_amount' => 8.0, // persen
            'status' => 'pending',
        ]);

        $payment = LoanPayment::create([
            'loan_id' => $loan->id,
            'due_date' => now()->addMonth(),
            'month' => now()->month,
            'year' => now()->year,
            'amount' => 800000,
            'payment_period' => 1,
            'status' => 'pending',
        ]);

        $this->assertInstanceOf(LoanPayment::class, $payment);
        $this->assertEquals(800000, $payment->amount);
    }
}