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
    public function it_can_create_a_loan_payment()
    {
        // Create related models
        $member = Member::factory()->create([
            'nik' => '1234567890123456',
            'full_name' => 'Test Member',
        ]);
        
        $loanProduct = LoanProduct::create([
            'name' => 'Test Product',
            'code' => 'TP001',
            'contract_type' => 'Murabahah',
            'min_amount' => 1000,
            'max_amount' => 50000,
            'min_rate' => 5.0,
            'max_rate' => 15.0,
            'tenor_months' => '12',
            'usage_purposes' => 'Buka warung, Bengkel',
        ]);
        
        $user = User::factory()->create();
        
        $loan = Loan::create([
            'member_id' => $member->id,
            'loan_product_id' => $loanProduct->id,
            'created_by' => $user->id,
            'loan_amount' => 5000000,
            'purchase_price' => 5000000,
            'margin_amount' => 10.00,
            'status' => 'pending',
        ]);

        $paymentData = [
            'loan_id' => $loan->id,
            'due_date' => now()->addMonth(),
            'month' => now()->month,
            'year' => now()->year,
            'amount' => 1000.00,
            'payment_period' => 1,
            'status' => 'pending',
        ];

        $payment = LoanPayment::create($paymentData);

        $this->assertInstanceOf(LoanPayment::class, $payment);
        $this->assertEquals($loan->id, $payment->loan_id);
        $this->assertEquals(1000.00, $payment->amount);
        $this->assertEquals('pending', $payment->status);
    }

    #[Test]
    public function it_can_mark_payment_as_late()
    {
        // Create related models
        $member = Member::factory()->create([
            'nik' => '1234567890123456',
            'full_name' => 'Test Member',
        ]);
        
        $loanProduct = LoanProduct::create([
            'name' => 'Test Product',
            'code' => 'TP001',
            'contract_type' => 'Murabahah',
            'min_amount' => 1000,
            'max_amount' => 50000,
            'min_rate' => 5.0,
            'max_rate' => 15.0,
            'tenor_months' => '12',
            'usage_purposes' => 'Buka warung, Bengkel',
        ]);
        
        $user = User::factory()->create();
        
        $loan = Loan::create([
            'member_id' => $member->id,
            'loan_product_id' => $loanProduct->id,
            'created_by' => $user->id,
            'loan_amount' => 5000000,
            'purchase_price' => 5000000,
            'margin_amount' => 10.00,
            'status' => 'pending',
        ]);
        
        $payment = LoanPayment::create([
            'loan_id' => $loan->id,
            'due_date' => now()->subDays(5), // Past due date
            'month' => now()->month,
            'year' => now()->year,
            'amount' => 1000.00,
            'payment_period' => 1,
            'status' => 'pending',
            'is_late' => false,
        ]);

        $payment->is_late = true;
        $payment->fine = 50.00;
        $payment->save();

        $this->assertTrue($payment->is_late);
        $this->assertEquals(50.00, $payment->fine);
    }
}





