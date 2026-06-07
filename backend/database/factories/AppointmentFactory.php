<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Clinic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('+1 day', '+2 weeks');

        return [
            'clinic_id' => Clinic::factory(),
            'patient_id' => null,
            'title' => fake()->sentence(4),
            'professional' => fake()->name(),
            'type' => 'Consulta',
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->modify('+30 minutes'),
            'status' => AppointmentStatus::Scheduled->value,
            'price' => fake()->randomFloat(2, 80, 350),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
