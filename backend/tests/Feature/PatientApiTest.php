<?php

namespace Tests\Feature;

use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_routes_require_authentication(): void
    {
        $this->getJson('/api/patients')
            ->assertStatus(401);
    }

    public function test_authenticated_user_can_create_and_list_patients(): void
    {
        $this->actingAsClinicRole();

        $payload = $this->patientPayload();

        $this->postJson('/api/patients', $payload)
            ->assertCreated()
            ->assertJsonPath('nome', 'Maria Souza')
            ->assertJsonPath('cpf', '12345678901')
            ->assertJsonPath('estado', 'SP');

        $this->assertDatabaseHas('patients', [
            'nome' => 'Maria Souza',
            'cpf' => '12345678901',
            'estado' => 'SP',
        ]);

        $this->assertDatabaseHas('clinic_notifications', [
            'title' => 'Novo paciente cadastrado',
            'body' => 'Novo paciente cadastrado: Maria Souza.',
            'type' => 'success',
        ]);

        $this->getJson('/api/patients?search=Maria')
            ->assertOk()
            ->assertJsonPath('data.0.nome', 'Maria Souza');
    }

    public function test_authenticated_user_can_update_patient(): void
    {
        $this->actingAsClinicRole();

        $patient = Patient::create($this->patientPayload());

        $this->putJson("/api/patients/{$patient->id}", [
            'nome' => 'Maria Souza Atualizada',
            'cpf' => '12345678901',
            'telefone' => '11999999999',
            'estado' => 'RJ',
        ])
            ->assertOk()
            ->assertJsonPath('nome', 'Maria Souza Atualizada')
            ->assertJsonPath('estado', 'RJ');

        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
            'nome' => 'Maria Souza Atualizada',
            'estado' => 'RJ',
        ]);

        $this->assertDatabaseHas('clinic_notifications', [
            'title' => 'Paciente atualizado',
            'body' => 'Paciente Maria Souza Atualizada foi atualizado. Campos alterados: nome, telefone e estado.',
        ]);
    }

    public function test_patient_validation_requires_name_and_unique_cpf(): void
    {
        $this->actingAsClinicRole();

        Patient::create($this->patientPayload());

        $this->postJson('/api/patients', [
            'nome' => '',
            'cpf' => '12345678901',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nome', 'cpf']);
    }

    public function test_patient_cpf_and_phone_must_be_numeric_with_expected_lengths(): void
    {
        $this->actingAsClinicRole();

        $this->postJson('/api/patients', [
            ...$this->patientPayload(),
            'cpf' => '12345678901',
            'telefone' => '8399999999',
        ])
            ->assertCreated()
            ->assertJsonPath('telefone', '8399999999');

        $this->postJson('/api/patients', [
            ...$this->patientPayload(),
            'cpf' => '123ABC',
            'telefone' => '83999999999',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cpf']);

        $invalidPhones = [
            '839999999',
            '839999999999999',
            'telefone',
            '(83)99999-9999',
            '83 99999 9999',
        ];

        foreach ($invalidPhones as $phone) {
            $this->postJson('/api/patients', [
                ...$this->patientPayload(),
                'cpf' => fake()->unique()->numerify('###########'),
                'telefone' => $phone,
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['telefone'])
                ->assertJsonPath('errors.telefone.0', 'Telefone deve conter entre 10 e 11 dígitos.');
        }

        $this->postJson('/api/patients', [
            ...$this->patientPayload(),
            'cpf' => '22222222222',
            'telefone' => '83999999999',
        ])
            ->assertCreated()
            ->assertJsonPath('cpf', '22222222222')
            ->assertJsonPath('telefone', '83999999999');
    }

    public function test_patient_address_number_must_be_positive_integer(): void
    {
        $this->actingAsClinicRole();

        $this->postJson('/api/patients', [
            ...$this->patientPayload(),
            'numero' => '12A',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['numero']);

        $this->postJson('/api/patients', [
            ...$this->patientPayload(),
            'cpf' => '98765432100',
            'numero' => 0,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['numero']);
    }

    public function test_authenticated_user_can_soft_delete_patient(): void
    {
        $this->actingAsClinicRole();

        $patient = Patient::create($this->patientPayload());

        $this->deleteJson("/api/patients/{$patient->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Paciente removido com sucesso.');

        $this->assertSoftDeleted('patients', [
            'id' => $patient->id,
        ]);

        $this->assertDatabaseHas('clinic_notifications', [
            'title' => 'Paciente removido',
            'body' => 'Paciente Maria Souza foi removido do sistema.',
        ]);
    }

    public function test_authenticated_user_can_restore_patient(): void
    {
        $this->actingAsClinicRole();

        $patient = Patient::create($this->patientPayload());
        $patient->delete();

        $this->postJson("/api/patients/restore/{$patient->id}")
            ->assertOk()
            ->assertJsonPath('id', $patient->id);

        $this->assertDatabaseHas('clinic_notifications', [
            'title' => 'Paciente restaurado',
            'body' => 'Paciente Maria Souza foi restaurado.',
        ]);
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
            'cep' => '01001000',
            'endereco' => 'Praça da Sé',
            'numero' => '100',
            'bairro' => 'Sé',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'observacoes' => 'Paciente em acompanhamento.',
        ];
    }
}
