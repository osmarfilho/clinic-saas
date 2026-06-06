<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Clinic;
use App\Models\ClinicNotification;
use App\Models\FinancialTransaction;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiTenantSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_only_list_patients_from_their_own_clinic(): void
    {
        $clinicA = Clinic::factory()->create();
        $clinicB = Clinic::factory()->create();

        $this->actingAsClinicRole('Admin da Clínica', $clinicA);

        Patient::create($this->patientPayload(['cpf' => '11111111111', 'nome' => 'Paciente Clínica A']));
        Patient::withoutGlobalScope('clinic')->create($this->patientPayload([
            'clinic_id' => $clinicB->id,
            'cpf' => '22222222222',
            'nome' => 'Paciente Clínica B',
        ]));

        $this->getJson('/api/patients')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nome', 'Paciente Clínica A');
    }

    public function test_user_cannot_access_patient_from_another_clinic(): void
    {
        $clinicA = Clinic::factory()->create();
        $clinicB = Clinic::factory()->create();
        $this->actingAsClinicRole('Admin da Clínica', $clinicA);

        $foreignPatient = Patient::withoutGlobalScope('clinic')->create($this->patientPayload([
            'clinic_id' => $clinicB->id,
            'cpf' => '33333333333',
            'nome' => 'Paciente de outra clínica',
        ]));

        $this->getJson("/api/patients/{$foreignPatient->id}")
            ->assertNotFound();

        $this->putJson("/api/patients/{$foreignPatient->id}", ['nome' => 'Tentativa'])
            ->assertNotFound();
    }

    public function test_user_cannot_link_appointment_to_patient_from_another_clinic(): void
    {
        $clinicA = Clinic::factory()->create();
        $clinicB = Clinic::factory()->create();
        $this->actingAsClinicRole('Admin da Clínica', $clinicA);

        $foreignPatient = Patient::withoutGlobalScope('clinic')->create($this->patientPayload([
            'clinic_id' => $clinicB->id,
            'cpf' => '44444444444',
        ]));

        $this->postJson('/api/appointments', [
            'patient_id' => $foreignPatient->id,
            'title' => 'Consulta indevida',
            'type' => 'Consulta',
            'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'status' => 'scheduled',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['patient_id']);
    }

    public function test_role_without_permission_cannot_manage_patients(): void
    {
        $this->actingAsClinicRole('Financeiro');

        $this->postJson('/api/patients', $this->patientPayload())
            ->assertForbidden();
    }

    public function test_financial_data_is_isolated_by_clinic(): void
    {
        $clinicA = Clinic::factory()->create();
        $clinicB = Clinic::factory()->create();
        $this->actingAsClinicRole('Admin da Clínica', $clinicA);

        $patient = Patient::create($this->patientPayload(['cpf' => '55555555555']));
        FinancialTransaction::create([
            'patient_id' => $patient->id,
            'description' => 'Receita Clínica A',
            'type' => 'income',
            'amount' => 100,
            'due_date' => now()->toDateString(),
            'paid_at' => now()->toDateString(),
            'status' => 'paid',
        ]);

        FinancialTransaction::withoutGlobalScope('clinic')->create([
            'clinic_id' => $clinicB->id,
            'description' => 'Receita Clínica B',
            'type' => 'income',
            'amount' => 999,
            'due_date' => now()->toDateString(),
            'paid_at' => now()->toDateString(),
            'status' => 'paid',
        ]);

        $this->getJson('/api/financial-transactions')
            ->assertOk()
            ->assertJsonPath('summary.paid_income', 100)
            ->assertJsonCount(1, 'data');
    }

    public function test_notifications_are_isolated_by_clinic(): void
    {
        $clinicA = Clinic::factory()->create();
        $clinicB = Clinic::factory()->create();
        $this->actingAsClinicRole('Admin da Clínica', $clinicA);

        ClinicNotification::create([
            'title' => 'Notificação Clínica A',
            'type' => 'info',
        ]);

        ClinicNotification::withoutGlobalScope('clinic')->create([
            'clinic_id' => $clinicB->id,
            'title' => 'Notificação Clínica B',
            'type' => 'info',
        ]);

        $this->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('notifications.data.0.title', 'Notificação Clínica A');
    }

    public function test_audit_log_is_created_for_patient_changes(): void
    {
        $user = $this->actingAsClinicRole('Admin da Clínica');

        $this->postJson('/api/patients', $this->patientPayload())
            ->assertCreated();

        $this->assertDatabaseHas('audit_logs', [
            'clinic_id' => $user->clinic_id,
            'user_id' => $user->id,
            'event' => 'patient.created',
        ]);
    }

    public function test_schedule_conflicts_are_scoped_to_the_current_clinic(): void
    {
        $clinicA = Clinic::factory()->create();
        $clinicB = Clinic::factory()->create();
        $this->actingAsClinicRole('Admin da Clínica', $clinicA);

        $startsAt = now()->addDay()->setTime(10, 0);

        Appointment::withoutGlobalScope('clinic')->create([
            'clinic_id' => $clinicB->id,
            'title' => 'Consulta de outra clínica',
            'professional' => 'Dra. Paula',
            'type' => 'Consulta',
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes(30),
            'status' => 'scheduled',
        ]);

        $this->postJson('/api/appointments', [
            'title' => 'Consulta permitida',
            'professional' => 'Dra. Paula',
            'type' => 'Consulta',
            'starts_at' => $startsAt->copy()->addMinutes(10)->format('Y-m-d H:i:s'),
            'ends_at' => $startsAt->copy()->addMinutes(40)->format('Y-m-d H:i:s'),
            'status' => 'scheduled',
        ])
            ->assertCreated();
    }

    private function patientPayload(array $overrides = []): array
    {
        return [
            'nome' => 'Maria Souza',
            'cpf' => '12345678901',
            'telefone' => '11988887777',
            'email' => 'maria@example.com',
            'data_nascimento' => '1990-05-12',
            'convenio' => 'Saúde Mais',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'observacoes' => 'Paciente em acompanhamento.',
            ...$overrides,
        ];
    }
}
