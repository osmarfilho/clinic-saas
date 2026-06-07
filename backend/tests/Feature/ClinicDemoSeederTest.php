<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\ClinicDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_user_is_created_with_defaults_in_local_environment(): void
    {
        $originalEnvironment = app()->environment();

        unset($_ENV['DEMO_ADMIN_EMAIL'], $_SERVER['DEMO_ADMIN_EMAIL']);
        unset($_ENV['DEMO_ADMIN_PASSWORD'], $_SERVER['DEMO_ADMIN_PASSWORD']);

        app()->detectEnvironment(fn () => 'local');

        try {
            app(ClinicDemoSeeder::class)->run();
        } finally {
            app()->detectEnvironment(fn () => $originalEnvironment);
        }

        $user = User::query()->where('email', 'admin@clinic.test')->first();

        $this->assertNotNull($user);
        $this->assertSame('Admin', $user->name);
        $this->assertTrue($user->hasRole('Super Admin'));
    }

    public function test_demo_user_is_not_created_outside_local_environment(): void
    {
        $originalEnvironment = app()->environment();

        unset($_ENV['DEMO_ADMIN_EMAIL'], $_SERVER['DEMO_ADMIN_EMAIL']);
        unset($_ENV['DEMO_ADMIN_PASSWORD'], $_SERVER['DEMO_ADMIN_PASSWORD']);

        app()->detectEnvironment(fn () => 'production');

        try {
            app(ClinicDemoSeeder::class)->run();
        } finally {
            app()->detectEnvironment(fn () => $originalEnvironment);
        }

        $this->assertDatabaseMissing('users', [
            'email' => 'admin@clinic.test',
        ]);
    }
}
