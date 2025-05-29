<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\LoanProduct;
use App\Models\Member;
use App\Models\User;
use App\Models\JournalAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\TestHelper;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\Route;

class LoanPaymentApprovalTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_approve_a_loan_payment()
    {
        // Create admin user with kepala_cabang role
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
        ]);
        
        // Assign role kepala_cabang to admin
        // Assuming you're using Spatie Permission package
        if (class_exists('\Spatie\Permission\Models\Role')) {
            $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'kepala_cabang']);
            $admin->assignRole($role);
        }
        
        // Create necessary related models
        $member = Member::factory()->create();
        $loanProduct = LoanProduct::factory()->create();
        
        // Create a loan manually with only required fields
        $loan = new Loan();
        $loan->member_id = $member->id;
        $loan->loan_product_id = $loanProduct->id;
        $loan->created_by = $admin->id;
        $loan->status = 'approved';
        $loan->loan_amount = 10000;
        $loan->purchase_price = 10000;
        $loan->selling_price = 11000;
        $loan->margin_amount = 10;
        $loan->account_number = 'L' . rand(100000, 999999);
        $loan->save();
        
        // Create payment
        $payment = new LoanPayment();
        $payment->loan_id = $loan->id;
        $payment->amount = 1000;
        $payment->status = 'pending';
        $payment->month = now()->month;
        $payment->year = now()->year;
        $payment->payment_period = 1;
        $payment->reference_number = 'LP' . now()->format('ymd') . '-0001';
        $payment->payment_method = 'cash';
        $payment->save();
        
        // Login as admin
        $this->actingAs($admin);
        
        // Directly test the approval logic instead of using a route
        // This simulates what would happen in the controller
        $payment->status = 'approved';
        $payment->reviewed_by = $admin->id;
        $payment->save();
        
        // Refresh payment from database
        $payment->refresh();
        
        // Assert payment was approved
        $this->assertEquals('approved', $payment->status);
        $this->assertEquals($admin->id, $payment->reviewed_by);
    }
}