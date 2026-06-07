<?php

namespace Tests\Feature;

use App\Models\Clinic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminClinicApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_manage_clinics_and_generate_audit_logs(): void
    {
        $adminClinic = Clinic::factory()->create([
            'name' => 'Clínica Zeta',
            'document' => '99999999000199',
            'email' => 'zeta@example.com',
            'phone' => '8530000099',
            'active' => true,
        ]);

        $user = $this->actingAsClinicRole('Super Admin', $adminClinic);

        Clinic::factory()->create([
            'name' => 'Clínica Alfa',
            'document' => '11111111000111',
            'email' => 'alfa@example.com',
            'phone' => '8530000001',
            'active' => true,
        ]);

        $listResponse = $this->getJson('/api/super-admin/clinics')
            ->assertOk()
            ->assertJsonPath('meta.total', 3)
            ->assertJsonCount(3, 'data')
            ->assertJsonFragment(['name' => 'Clínica Alfa']);

        $created = $this->postJson('/api/super-admin/clinics', [
            'name' => 'Clínica Beta',
            'document' => '22222222000122',
            'email' => 'beta@example.com',
            'phone' => '8530000002',
        ])
            ->assertCreated()
            ->assertJsonPath('name', 'Clínica Beta');

        $clinicId = $created->json('id');

        $this->putJson("/api/super-admin/clinics/{$clinicId}", [
            'name' => 'Clínica Beta Atualizada',
            'email' => 'contato@beta.example',
        ])
            ->assertOk()
            ->assertJsonPath('name', 'Clínica Beta Atualizada')
            ->assertJsonPath('email', 'contato@beta.example');

        $this->patchJson("/api/super-admin/clinics/{$clinicId}/deactivate")
            ->assertOk()
            ->assertJsonPath('active', false);

        $this->patchJson("/api/super-admin/clinics/{$clinicId}/activate")
            ->assertOk()
            ->assertJsonPath('active', true);

        $this->postJson('/api/super-admin/clinics', [
            'name' => 'Telefone inválido',
            'document' => '33333333000133',
            'email' => 'invalid@example.com',
            'phone' => '(85) 3000-0003',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone'])
            ->assertJsonPath('errors.phone.0', 'Telefone deve conter entre 10 e 11 dígitos.');

        $this->assertDatabaseHas('audit_logs', [
            'clinic_id' => $clinicId,
            'user_id' => $user->id,
            'event' => 'clinic.created',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'clinic_id' => $clinicId,
            'user_id' => $user->id,
            'event' => 'clinic.updated',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'clinic_id' => $clinicId,
            'user_id' => $user->id,
            'event' => 'clinic.deactivated',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'clinic_id' => $clinicId,
            'user_id' => $user->id,
            'event' => 'clinic.activated',
        ]);
    }

    public function test_clinic_management_is_blocked_for_clinic_roles(): void
    {
        $clinic = Clinic::factory()->create();

        foreach (['Admin da Clínica', 'Médico', 'Recepcionista', 'Financeiro'] as $role) {
            $this->actingAsClinicRole($role);

            $this->getJson('/api/super-admin/clinics')->assertForbidden();

            $this->postJson('/api/super-admin/clinics', [
                'name' => 'Clínica Indevida',
                'document' => '33333333000133',
                'email' => 'indevida@example.com',
                'phone' => '8530000003',
            ])->assertForbidden();

            $this->putJson("/api/super-admin/clinics/{$clinic->id}", [
                'name' => 'Tentativa indevida',
            ])->assertForbidden();

            $this->patchJson("/api/super-admin/clinics/{$clinic->id}/activate")->assertForbidden();
            $this->patchJson("/api/super-admin/clinics/{$clinic->id}/deactivate")->assertForbidden();
        }
    }
}
