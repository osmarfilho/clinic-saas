<?php

namespace Database\Factories;

use App\Models\Clinic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Clinic>
 */
class ClinicFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'document' => fake()->numerify('##.###.###/####-##'),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'active' => true,
        ];
    }
}
