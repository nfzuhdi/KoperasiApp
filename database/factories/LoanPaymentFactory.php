<?php

namespace Database\Factories;

use App\Models\LoanPayment;
use App\Models\Loan;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoanPaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = LoanPayment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'loan_id' => Loan::factory(),
            'due_date' => now()->addDays($this->faker->numberBetween(1, 30)),
            'month' => now()->month,
            'year' => now()->year,
            'amount' => $this->faker->randomFloat(2, 500, 5000),
            'payment_period' => $this->faker->numberBetween(1, 12),
            'is_principal_return' => false,
            'fine' => 0,
            'is_late' => false,
            'payment_method' => $this->faker->randomElement(['cash', 'transfer', 'debit']),
            'reference_number' => 'LP' . now()->format('ymd') . '-' . $this->faker->unique()->numerify('####'),
            'status' => 'pending',
            'reviewed_by' => null,
            'notes' => $this->faker->optional()->sentence(),
            'member_profit' => 0,
            'koperasi_profit' => 0,
        ];
    }
    
    /**
     * Indicate that the payment is approved.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function approved()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'approved',
                'reviewed_by' => \App\Models\User::factory(),
            ];
        });
    }
    
    /**
     * Indicate that the payment is late.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function late()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_late' => true,
                'fine' => $this->faker->randomFloat(2, 10, 100),
                'due_date' => now()->subDays($this->faker->numberBetween(1, 30)),
            ];
        });
    }
}