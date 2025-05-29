<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LoanProduct>
 */
class LoanProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true) . ' Loan',
            'code' => 'LP' . $this->faker->unique()->numerify('####'),
            'contract_type' => $this->faker->randomElement(['Murabahah', 'Mudharabah', 'Musyarakah']),
            'min_amount' => 1000,
            'max_amount' => 50000,
            'min_rate' => $this->faker->randomFloat(2, 5, 10),
            'max_rate' => $this->faker->randomFloat(2, 10, 20),
            'usage_purposes' => 'Buka warung, Bengkel, Pendidikan',
            'tenor_months' => $this->faker->randomElement(['6', '12', '24']),
        ];
    }
}

