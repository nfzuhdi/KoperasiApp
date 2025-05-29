<?php

namespace Database\Factories;

use App\Models\JournalAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class JournalAccountFactory extends Factory
{
    protected $model = JournalAccount::class;

    public function definition(): array
    {
        return [
            'account_number' => $this->faker->numerify('####-####-####'),
            'account_position' => $this->faker->randomElement(['debit', 'credit']),
            'balance' => $this->faker->randomFloat(2, 0, 10000),
        ];
    }
}

