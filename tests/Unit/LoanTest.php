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

    private function createMember(): Member
    {
        return Member::create([
            'member_id' => 'MMR00001',
            'nik' => '1234567890123456',
            'full_name' => 'Test Member',
        ]);
    }

    private function createLoanProduct(string $contractType): LoanProduct
    {
        return LoanProduct::create([
            'contract_type' => $contractType,
            'name' => $contractType . ' Product',
            'code' => strtoupper(substr($contractType, 0, 3)) . '001',
            'min_amount' => 1000,
            'max_amount' => 50000,
            'min_rate' => 5.0,
            'max_rate' => 15.0,
            'tenor_months' => '12',
            'usage_purposes' => 'Usaha Mikro',
        ]);
    }

    #[Test]
    public function it_can_create_a_loan_for_murabahah()
    {
        $member = $this->createMember();
        $product = $this->createLoanProduct('Murabahah');
        $user = User::factory()->create();

        $loan = Loan::create([
            'member_id' => $member->id,
            'loan_product_id' => $product->id,
            'created_by' => $user->id,
            'purchase_price' => 1000,
            'margin_amount' => 200, // nominal
            'status' => 'pending',
        ]);

        $this->assertInstanceOf(Loan::class, $loan);
        $this->assertEquals(1200, $loan->selling_price); // 1000 + 200
    }

    #[Test]
    public function it_can_create_a_loan_for_mudharabah()
    {
        $member = $this->createMember();
        $product = $this->createLoanProduct('Mudharabah');
        $user = User::factory()->create();

        $loan = Loan::create([
            'member_id' => $member->id,
            'loan_product_id' => $product->id,
            'created_by' => $user->id,
            'loan_amount' => 5000000,
            'margin_amount' => 10, // persen
            'status' => 'pending',
        ]);

        $this->assertInstanceOf(Loan::class, $loan);
        $this->assertNull($loan->selling_price);
        $this->assertEquals(10, $loan->margin_amount); // margin % tetap tersimpan
    }

    #[Test]
    public function it_can_create_a_loan_for_musyarakah()
    {
        $member = $this->createMember();
        $product = $this->createLoanProduct('Musyarakah');
        $user = User::factory()->create();

        $loan = Loan::create([
            'member_id' => $member->id,
            'loan_product_id' => $product->id,
            'created_by' => $user->id,
            'loan_amount' => 10000000,
            'margin_amount' => 12.5, // persen
            'status' => 'pending',
        ]);

        $this->assertInstanceOf(Loan::class, $loan);
        $this->assertNull($loan->selling_price);
        $this->assertEquals(12.5, $loan->margin_amount);
    }

    #[Test]
    public function it_generates_an_account_number_on_creation()
    {
        $member = $this->createMember();
        $product = $this->createLoanProduct('Murabahah');
        $user = User::factory()->create();

        $loan = Loan::create([
            'member_id' => $member->id,
            'loan_product_id' => $product->id,
            'created_by' => $user->id,
            'purchase_price' => 1000,
            'margin_amount' => 200,
            'status' => 'pending',
        ]);

        $this->assertNotNull($loan->account_number);
        $this->assertStringStartsWith('LN', $loan->account_number);
    }
}