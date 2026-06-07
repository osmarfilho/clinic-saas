<?php

namespace Database\Factories;

use App\Models\Clinic;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'clinic_id' => Clinic::factory(),
            'nome' => fake()->name(),
            'cpf' => fake()->unique()->numerify('###########'),
            'telefone' => fake()->numerify('###########'),
            'email' => fake()->safeEmail(),
            'data_nascimento' => fake()->date(),
            'convenio' => fake()->company(),
            'cep' => fake()->numerify('########'),
            'endereco' => fake()->streetAddress(),
            'numero' => fake()->numberBetween(1, 9999),
            'bairro' => fake()->citySuffix(),
            'cidade' => fake()->city(),
            'estado' => fake()->stateAbbr(),
            'observacoes' => fake()->sentence(),
            'ativo' => true,
        ];
    }
}
