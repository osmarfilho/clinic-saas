<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_route_is_not_available(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Clinic Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertNotFound();
    }

    public function test_user_can_login_with_email_and_password(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonStructure([
                'message',
                'user',
                'token',
            ]);
    }
}
