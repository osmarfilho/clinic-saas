<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ClinicNotification;
use App\Models\FinancialTransaction;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicModulesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_authentication(): void
    {
        $this->getJson('/api/dashboard')
            ->assertUnauthorized();
    }

    public function test_authenticated_user_can_create_and_list_scheduled_appointment(): void
    {
        $this->actingAsClinicRole();
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

        $this->getJson('/api/appointments')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Consulta inicial');
    }

    public function test_authenticated_user_can_conclude_past_appointment_and_register_audit(): void
    {
        $user = $this->actingAsClinicRole();
        $patient = Patient::create($this->patientPayload());
        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'title' => 'Consulta inicial',
            'professional' => 'Dra. Paula',
            'type' => 'Consulta',
            'starts_at' => now()->subDay()->setTime(9, 0),
            'ends_at' => now()->subDay()->setTime(9, 30),
            'status' => 'scheduled',
            'price' => 180,
        ]);

        $this->putJson("/api/appointments/{$appointment->id}", [
            'status' => 'completed',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'completed');

        $this->assertDatabaseHas('audit_logs', [
            'clinic_id' => $user->clinic_id,
            'user_id' => $user->id,
            'event' => 'appointment.status_changed',
        ]);
    }

    public function test_authenticated_user_can_mark_past_appointment_as_no_show(): void
    {
        $this->actingAsClinicRole();
        $patient = Patient::create($this->patientPayload());
        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'title' => 'Consulta faltante',
            'professional' => 'Dra. Paula',
            'type' => 'Consulta',
            'starts_at' => now()->subDay()->setTime(10, 0),
            'ends_at' => now()->subDay()->setTime(10, 30),
            'status' => 'scheduled',
            'price' => 180,
        ]);

        $this->putJson("/api/appointments/{$appointment->id}", [
            'status' => 'no_show',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'no_show');
    }

    public function test_authenticated_user_can_cancel_past_appointment(): void
    {
        $this->actingAsClinicRole();
        $patient = Patient::create($this->patientPayload());
        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'title' => 'Consulta para cancelar',
            'professional' => 'Dra. Paula',
            'type' => 'Consulta',
            'starts_at' => now()->subDay()->setTime(11, 0),
            'ends_at' => now()->subDay()->setTime(11, 30),
            'status' => 'scheduled',
            'price' => 180,
        ]);

        $this->putJson("/api/appointments/{$appointment->id}", [
            'status' => 'cancelled',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'cancelled');
    }

    public function test_appointment_creation_blocks_past_dates_and_schedule_conflicts(): void
    {
        $this->actingAsClinicRole();
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

    public function test_updating_appointment_date_to_past_is_rejected(): void
    {
        $this->actingAsClinicRole();
        $patient = Patient::create($this->patientPayload());
        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'title' => 'Consulta futura',
            'professional' => 'Dra. Paula',
            'type' => 'Consulta',
            'starts_at' => now()->addDay()->setTime(11, 0),
            'ends_at' => now()->addDay()->setTime(11, 30),
            'status' => 'scheduled',
            'price' => 180,
        ]);

        $this->putJson("/api/appointments/{$appointment->id}", [
            'starts_at' => now()->subDay()->setTime(11, 0)->format('Y-m-d H:i:s'),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['starts_at']);
    }

    public function test_updating_past_appointment_status_to_completed_is_allowed(): void
    {
        $this->actingAsClinicRole();
        $patient = Patient::create($this->patientPayload());
        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'title' => 'Consulta antiga',
            'professional' => 'Dra. Paula',
            'type' => 'Consulta',
            'starts_at' => now()->subDay()->setTime(8, 0),
            'ends_at' => now()->subDay()->setTime(8, 30),
            'status' => 'scheduled',
            'price' => 180,
        ]);

        $this->putJson("/api/appointments/{$appointment->id}", [
            'status' => 'completed',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'completed');
    }

    public function test_updating_past_appointment_status_to_no_show_is_allowed(): void
    {
        $this->actingAsClinicRole();
        $patient = Patient::create($this->patientPayload());
        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'title' => 'Consulta faltou',
            'professional' => 'Dra. Paula',
            'type' => 'Consulta',
            'starts_at' => now()->subDay()->setTime(9, 0),
            'ends_at' => now()->subDay()->setTime(9, 30),
            'status' => 'scheduled',
            'price' => 180,
        ]);

        $this->putJson("/api/appointments/{$appointment->id}", [
            'status' => 'no_show',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'no_show');
    }

    public function test_updating_past_appointment_status_to_cancelled_is_allowed(): void
    {
        $this->actingAsClinicRole();
        $patient = Patient::create($this->patientPayload());
        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'title' => 'Consulta cancelada',
            'professional' => 'Dra. Paula',
            'type' => 'Consulta',
            'starts_at' => now()->subDay()->setTime(10, 0),
            'ends_at' => now()->subDay()->setTime(10, 30),
            'status' => 'scheduled',
            'price' => 180,
        ]);

        $this->putJson("/api/appointments/{$appointment->id}", [
            'status' => 'cancelled',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'cancelled');
    }

    public function test_invalid_appointment_status_is_rejected(): void
    {
        $this->actingAsClinicRole();
        $patient = Patient::create($this->patientPayload());

        $this->postJson('/api/appointments', [
            'patient_id' => $patient->id,
            'title' => 'Consulta inválida',
            'professional' => 'Dra. Paula',
            'type' => 'Consulta',
            'starts_at' => now()->addDay()->setTime(11, 0),
            'ends_at' => now()->addDay()->setTime(11, 30),
            'status' => 'confirmed',
            'price' => 180,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    public function test_authenticated_user_can_manage_financial_transactions_and_dashboard(): void
    {
        $this->actingAsClinicRole();
        $patient = Patient::create($this->patientPayload());
        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'title' => 'Consulta inicial',
            'type' => 'Consulta',
            'starts_at' => now()->subDay()->setTime(9, 0),
            'status' => 'completed',
            'price' => 180,
        ]);

        Appointment::create([
            'patient_id' => $patient->id,
            'title' => 'Consulta agendada',
            'type' => 'Consulta',
            'starts_at' => now()->setTime(8, 0),
            'status' => 'scheduled',
            'price' => 180,
        ]);
        Appointment::create([
            'patient_id' => $patient->id,
            'title' => 'Consulta concluída',
            'type' => 'Consulta',
            'starts_at' => now()->setTime(9, 0),
            'status' => 'completed',
            'price' => 180,
        ]);
        Appointment::create([
            'patient_id' => $patient->id,
            'title' => 'Consulta faltou',
            'type' => 'Consulta',
            'starts_at' => now()->setTime(10, 0),
            'status' => 'no_show',
            'price' => 180,
        ]);
        Appointment::create([
            'patient_id' => $patient->id,
            'title' => 'Consulta cancelada',
            'type' => 'Consulta',
            'starts_at' => now()->setTime(11, 0),
            'status' => 'cancelled',
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
            ->assertJsonPath('metrics.monthly_revenue', 180)
            ->assertJsonPath('metrics.scheduled_today', 1)
            ->assertJsonPath('metrics.completed_today', 1)
            ->assertJsonPath('metrics.no_show_month', 1)
            ->assertJsonPath('metrics.cancelled_month', 1)
            ->assertJsonPath('indicators.scheduled_today', 1)
            ->assertJsonPath('indicators.completed_today', 1)
            ->assertJsonPath('indicators.no_show_month', 1)
            ->assertJsonPath('indicators.cancelled_month', 1);
    }

    public function test_financial_summary_separates_paid_and_pending_totals(): void
    {
        $this->actingAsClinicRole();
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
        $user = $this->actingAsClinicRole();
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
        $user = $this->actingAsClinicRole();

        $this->putJson('/api/settings', [
            'clinic_name' => 'Clínica Teste',
            'document' => '00000000000100',
            'phone' => '8533330000',
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

        $invalidPhones = [
            '839999999',
            '839999999999999',
            'telefone',
            '(83)99999-9999',
            '83 99999 9999',
        ];

        foreach ($invalidPhones as $phone) {
            $this->putJson('/api/settings', [
                'clinic_name' => 'Clínica Teste',
                'document' => '00000000000100',
                'phone' => $phone,
                'email' => 'contato@clinic.test',
                'address' => 'Rua Teste, 123',
                'opening_time' => '08:00',
                'closing_time' => '18:00',
                'appointment_duration' => 30,
                'daily_capacity' => 20,
                'average_wait_minutes' => 12,
                'satisfaction_rate' => 95,
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['phone'])
                ->assertJsonPath('errors.phone.0', 'Telefone deve conter entre 10 e 11 dígitos.');
        }

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
