<?php

namespace Tests;

use App\Models\Clinic;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Sanctum\Sanctum;

abstract class TestCase extends BaseTestCase
{
    protected function actingAsClinicRole(string $role = 'Admin da Clínica', ?Clinic $clinic = null): User
    {
        app(RoleSeeder::class)->run();

        $user = User::factory()->create([
            'clinic_id' => $clinic?->id ?? Clinic::factory()->create()->id,
        ]);

        $user->assignRole($role);
        Sanctum::actingAs($user);

        return $user;
    }
}
