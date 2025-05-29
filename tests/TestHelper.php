<?php

namespace Tests;

use App\Models\JournalAccount;
use App\Models\LoanProduct;
use App\Models\Loan;
use App\Models\Member;
use App\Models\User;
use Mockery;

class TestHelper
{
    public static function mockJournalAccount($attributes = [])
    {
        $defaultAttributes = [
            'id' => 1,
            'account_number' => '1234-5678-9012',
            'account_position' => 'debit',
            'balance' => 10000,
        ];
        
        return Mockery::mock(JournalAccount::class, array_merge($defaultAttributes, $attributes));
    }
    
    public static function mockMember($attributes = [])
    {
        $defaultAttributes = [
            'id' => 1,
            'member_id' => 'MMR00001',
            'nik' => '1234567890123456',
            'full_name' => 'Test Member',
        ];
        
        return Mockery::mock(Member::class, array_merge($defaultAttributes, $attributes));
    }
    
    public static function mockLoanProduct($attributes = [])
    {
        $defaultAttributes = [
            'id' => 1,
            'name' => 'Test Loan Product',
            'contract_type' => 'Murabahah',
            'tenor_months' => 12,
            'min_rate' => 5.0,
            'max_rate' => 15.0,
        ];
        
        return Mockery::mock(LoanProduct::class, array_merge($defaultAttributes, $attributes));
    }
    
    public static function mockLoan($attributes = [])
    {
        $defaultAttributes = [
            'id' => 1,
            'loan_amount' => 10000,
            'purchase_price' => 10000,
            'selling_price' => 12000,
            'status' => 'approved',
            'disbursement_status' => 'disbursed',
        ];
        
        return Mockery::mock(Loan::class, array_merge($defaultAttributes, $attributes));
    }
}

