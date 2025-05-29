<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\Member;
use App\Models\LoanProduct;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Loan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $loanAmount = $this->faker->randomFloat(2, 5000, 50000);
        $marginPercentage = $this->faker->randomFloat(2, 5, 20);
        $marginAmount = $loanAmount * ($marginPercentage / 100);
        
        return [
            'account_number' => 'L' . $this->faker->unique()->numerify('######'),
            'member_id' => Member::factory(),
            'loan_product_id' => LoanProduct::factory(),
            'created_by' => User::factory(),
            'loan_amount' => $loanAmount,
            'purchase_price' => $loanAmount,
            'selling_price' => $loanAmount + $marginAmount,
            'margin_amount' => $marginPercentage,
            'tenor_months' => $this->faker->randomElement([6, 12, 24, 36]),
            'purpose' => $this->faker->sentence(),
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'disbursement_status' => 'pending',
            'payment_status' => 'not_paid',
            'disbursed_at' => null,
            'approved_at' => null,
        ];
    }
    
    /**
     * Indicate that the loan is approved.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function approved()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'approved',
                'approved_at' => now(),
            ];
        });
    }
    
    /**
     * Indicate that the loan is disbursed.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function disbursed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'approved',
                'approved_at' => now(),
                'disbursement_status' => 'disbursed',
                'disbursed_at' => now(),
            ];
        });
    }
}