<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ClinicNotification;
use App\Models\FinancialTransaction;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClinicModulesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_authentication(): void
    {
        $this->getJson('/api/dashboard')
            ->assertUnauthorized();
    }

    public function test_authenticated_user_can_manage_appointments(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $patient = Patient::create($this->patientPayload());

        $response = $this->postJson('/api/appointments', [
            'patient_id' => $patient->id,
            'title' => 'Consulta inicial',
            'professional' => 'Dra. Paula',
            'type' => 'Consulta',
            'starts_at' => now()->addDay()->setTime(9, 0)->format('Y-m-d H:i:s'),
            'ends_at' => now()->addDay()->setTime(9, 30)->format('Y-m-d H:i:s'),
            'status' => 'scheduled',
            'price' => 180,
            'notes' => 'Chegar 10 minutos antes.',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('title', 'Consulta inicial')
            ->assertJsonPath('patient.nome', 'Maria Souza');

        $appointmentId = $response->json('id');

        $this->putJson("/api/appointments/{$appointmentId}", [
            'status' => 'confirmed',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'confirmed');

        $this->getJson('/api/appointments')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Consulta inicial');
    }

    public function test_appointment_validation_blocks_past_dates_and_schedule_conflicts(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $patient = Patient::create($this->patientPayload());

        $this->postJson('/api/appointments', [
            'patient_id' => $patient->id,
            'title' => 'Consulta antiga',
            'professional' => 'Dra. Paula',
            'type' => 'Consulta',
            'starts_at' => now()->subDay()->setTime(9, 0)->format('Y-m-d H:i:s'),
            'ends_at' => now()->subDay()->setTime(9, 30)->format('Y-m-d H:i:s'),
            'status' => 'scheduled',
            'price' => 180,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['starts_at']);

        Appointment::create([
            'patient_id' => $patient->id,
            'title' => 'Consulta existente',
            'professional' => 'Dra. Paula',
            'type' => 'Consulta',
            'starts_at' => now()->addDay()->setTime(10, 0),
            'ends_at' => now()->addDay()->setTime(10, 30),
            'status' => 'scheduled',
            'price' => 180,
        ]);

        $this->postJson('/api/appointments', [
            'patient_id' => $patient->id,
            'title' => 'Consulta conflitante',
            'professional' => 'Dra. Paula',
            'type' => 'Consulta',
            'starts_at' => now()->addDay()->setTime(10, 15)->format('Y-m-d H:i:s'),
            'ends_at' => now()->addDay()->setTime(10, 45)->format('Y-m-d H:i:s'),
            'status' => 'scheduled',
            'price' => 180,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['starts_at']);
    }

    public function test_canceling_appointment_creates_notification(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $patient = Patient::create($this->patientPayload());
        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'title' => 'Consulta para cancelar',
            'professional' => 'Dra. Paula',
            'type' => 'Consulta',
            'starts_at' => now()->addDay()->setTime(11, 0),
            'ends_at' => now()->addDay()->setTime(11, 30),
            'status' => 'scheduled',
            'price' => 180,
        ]);

        $this->putJson("/api/appointments/{$appointment->id}", [
            'status' => 'canceled',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'canceled');

        $this->assertDatabaseHas('clinic_notifications', [
            'user_id' => $user->id,
            'title' => 'Consulta cancelada',
            'body' => 'Agendamento de Maria Souza foi cancelado.',
            'type' => 'warning',
        ]);
    }

    public function test_authenticated_user_can_manage_financial_transactions_and_dashboard(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $patient = Patient::create($this->patientPayload());
        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'title' => 'Consulta inicial',
            'type' => 'Consulta',
            'starts_at' => now()->setTime(9, 0),
            'status' => 'completed',
            'price' => 180,
        ]);

        $this->postJson('/api/financial-transactions', [
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'description' => 'Consulta Maria Souza',
            'type' => 'income',
            'category' => 'Consulta',
            'amount' => 180,
            'due_date' => now()->toDateString(),
            'paid_at' => now()->toDateString(),
            'status' => 'paid',
            'payment_method' => 'Pix',
        ])
            ->assertCreated()
            ->assertJsonPath('description', 'Consulta Maria Souza');

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('metrics.active_patients', 1)
            ->assertJsonPath('metrics.monthly_revenue', 180);
    }

    public function test_financial_summary_separates_paid_and_pending_totals(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $patient = Patient::create($this->patientPayload());

        FinancialTransaction::create([
            'patient_id' => $patient->id,
            'description' => 'Receita paga',
            'type' => 'income',
            'amount' => 180,
            'due_date' => now()->toDateString(),
            'paid_at' => now()->toDateString(),
            'status' => 'paid',
        ]);
        FinancialTransaction::create([
            'patient_id' => $patient->id,
            'description' => 'Receita pendente',
            'type' => 'income',
            'amount' => 220,
            'due_date' => now()->toDateString(),
            'status' => 'pending',
        ]);
        FinancialTransaction::create([
            'description' => 'Despesa paga',
            'type' => 'expense',
            'amount' => 3200,
            'due_date' => now()->toDateString(),
            'paid_at' => now()->toDateString(),
            'status' => 'paid',
        ]);
        FinancialTransaction::create([
            'description' => 'Despesa pendente',
            'type' => 'expense',
            'amount' => 780,
            'due_date' => now()->toDateString(),
            'status' => 'pending',
        ]);

        $this->getJson('/api/financial-transactions?type=income&status=paid')
            ->assertOk()
            ->assertJsonPath('summary.paid_income', 180)
            ->assertJsonPath('summary.pending_income', 220)
            ->assertJsonPath('summary.paid_expenses', 3200)
            ->assertJsonPath('summary.pending_expenses', 780)
            ->assertJsonPath('summary.current_balance', -3020)
            ->assertJsonPath('summary.forecast_balance', -3580);
    }

    public function test_financial_validation_and_notifications(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $patient = Patient::create($this->patientPayload());

        $this->postJson('/api/financial-transactions', [
            'description' => '',
            'type' => 'income',
            'amount' => 0,
            'due_date' => null,
            'status' => 'pending',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['patient_id', 'description', 'amount', 'due_date']);

        $response = $this->postJson('/api/financial-transactions', [
            'patient_id' => $patient->id,
            'description' => 'Consulta Maria Souza',
            'type' => 'income',
            'amount' => 180,
            'due_date' => now()->toDateString(),
            'status' => 'pending',
        ])
            ->assertCreated()
            ->assertJsonPath('description', 'Consulta Maria Souza');

        $transactionId = $response->json('id');

        $this->putJson("/api/financial-transactions/{$transactionId}", [
            'status' => 'paid',
            'paid_at' => now()->toDateString(),
        ])
            ->assertOk()
            ->assertJsonPath('status', 'paid');

        $this->assertDatabaseHas('clinic_notifications', [
            'user_id' => $user->id,
            'title' => 'Pagamento confirmado',
            'body' => 'Pagamento confirmado: Consulta Maria Souza - R$ 180,00.',
        ]);

        $this->deleteJson("/api/financial-transactions/{$transactionId}")
            ->assertOk();

        $this->assertDatabaseHas('clinic_notifications', [
            'user_id' => $user->id,
            'title' => 'Lançamento financeiro removido',
        ]);
    }

    public function test_authenticated_user_can_update_settings_and_read_notifications(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson('/api/settings', [
            'clinic_name' => 'Clínica Teste',
            'document' => '00.000.000/0001-00',
            'phone' => '(85) 3333-0000',
            'email' => 'contato@clinic.test',
            'address' => 'Rua Teste, 123',
            'opening_time' => '08:00',
            'closing_time' => '18:00',
            'appointment_duration' => 30,
            'daily_capacity' => 20,
            'average_wait_minutes' => 12,
            'satisfaction_rate' => 95,
        ])
            ->assertOk()
            ->assertJsonPath('clinic_name', 'Clínica Teste')
            ->assertJsonPath('daily_capacity', '20');

        $notification = ClinicNotification::create([
            'user_id' => $user->id,
            'title' => 'Agenda atualizada',
            'body' => 'Teste',
            'type' => 'info',
        ]);

        $this->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('notifications.data.0.title', 'Agenda atualizada');

        $this->postJson("/api/notifications/{$notification->id}/read")
            ->assertOk()
            ->assertJsonPath('read_at', fn ($value) => filled($value));
    }

    private function patientPayload(): array
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
        ];
    }
}
