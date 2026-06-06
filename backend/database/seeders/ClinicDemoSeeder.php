<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\ClinicNotification;
use App\Models\ClinicSetting;
use App\Models\FinancialTransaction;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class ClinicDemoSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $admin = User::updateOrCreate(
            ['email' => 'admin@clinic.test'],
            [
                'name' => 'Admin',
                'password' => Hash::make('123456'),
            ],
        );

        $admin->assignRole($adminRole);

        $patients = collect([
            [
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
                'ativo' => true,
            ],
            [
                'nome' => 'João Martins',
                'cpf' => '98765432100',
                'telefone' => '85999990000',
                'email' => 'joao@example.com',
                'data_nascimento' => '1984-10-03',
                'convenio' => 'Particular',
                'cidade' => 'Fortaleza',
                'estado' => 'CE',
                'observacoes' => 'Retorno trimestral.',
                'ativo' => true,
            ],
            [
                'nome' => 'Aline Rocha',
                'cpf' => '45678912300',
                'telefone' => '81988776655',
                'email' => 'aline@example.com',
                'data_nascimento' => '1995-02-18',
                'convenio' => 'Vida Plena',
                'cidade' => 'Recife',
                'estado' => 'PE',
                'observacoes' => 'Solicitou exames.',
                'ativo' => true,
            ],
            [
                'nome' => 'Carlos Lima',
                'cpf' => '32165498700',
                'telefone' => '71987654321',
                'email' => 'carlos@example.com',
                'data_nascimento' => '1978-07-22',
                'convenio' => 'Saúde Mais',
                'cidade' => 'Salvador',
                'estado' => 'BA',
                'observacoes' => 'Atendimento por teleconsulta.',
                'ativo' => true,
            ],
        ])->map(fn (array $data) => Patient::updateOrCreate(['cpf' => $data['cpf']], $data));

        $today = Carbon::today();

        $appointments = [
            [
                'patient_id' => $patients[0]->id,
                'title' => 'Retorno de Maria Souza',
                'professional' => 'Dra. Paula Fernandes',
                'type' => 'Retorno',
                'starts_at' => $today->copy()->setTime(8, 30),
                'ends_at' => $today->copy()->setTime(9, 0),
                'status' => 'confirmed',
                'price' => 180,
                'notes' => 'Revisar exames anteriores.',
            ],
            [
                'patient_id' => $patients[1]->id,
                'title' => 'Consulta de João Martins',
                'professional' => 'Dr. Paulo Nogueira',
                'type' => 'Consulta',
                'starts_at' => $today->copy()->setTime(9, 15),
                'ends_at' => $today->copy()->setTime(9, 45),
                'status' => 'scheduled',
                'price' => 220,
                'notes' => 'Primeira avaliação do mês.',
            ],
            [
                'patient_id' => $patients[2]->id,
                'title' => 'Exame de Aline Rocha',
                'professional' => 'Dra. Renata Alves',
                'type' => 'Exame',
                'starts_at' => $today->copy()->setTime(10, 40),
                'ends_at' => $today->copy()->setTime(11, 10),
                'status' => 'scheduled',
                'price' => 140,
                'notes' => 'Confirmar documentação.',
            ],
            [
                'patient_id' => $patients[3]->id,
                'title' => 'Teleconsulta de Carlos Lima',
                'professional' => 'Dr. Paulo Nogueira',
                'type' => 'Teleconsulta',
                'starts_at' => $today->copy()->setTime(11, 20),
                'ends_at' => $today->copy()->setTime(11, 50),
                'status' => 'confirmed',
                'price' => 160,
                'notes' => 'Enviar link antes do atendimento.',
            ],
            [
                'patient_id' => $patients[0]->id,
                'title' => 'Consulta de revisão',
                'professional' => 'Dra. Paula Fernandes',
                'type' => 'Consulta',
                'starts_at' => $today->copy()->addDay()->setTime(14, 0),
                'ends_at' => $today->copy()->addDay()->setTime(14, 30),
                'status' => 'scheduled',
                'price' => 180,
                'notes' => 'Agenda futura.',
            ],
        ];

        $createdAppointments = collect($appointments)->map(function (array $data) {
            return Appointment::updateOrCreate(
                [
                    'patient_id' => $data['patient_id'],
                    'starts_at' => $data['starts_at'],
                    'title' => $data['title'],
                ],
                $data,
            );
        });

        $transactions = [
            [
                'patient_id' => $patients[0]->id,
                'appointment_id' => $createdAppointments[0]->id,
                'description' => 'Consulta Maria Souza',
                'type' => 'income',
                'category' => 'Consulta',
                'amount' => 180,
                'due_date' => $today,
                'paid_at' => $today,
                'status' => 'paid',
                'payment_method' => 'Pix',
                'notes' => 'Pagamento confirmado.',
            ],
            [
                'patient_id' => $patients[1]->id,
                'appointment_id' => $createdAppointments[1]->id,
                'description' => 'Consulta João Martins',
                'type' => 'income',
                'category' => 'Consulta',
                'amount' => 220,
                'due_date' => $today,
                'paid_at' => null,
                'status' => 'pending',
                'payment_method' => 'Cartão',
                'notes' => 'Cobrança pendente.',
            ],
            [
                'patient_id' => null,
                'appointment_id' => null,
                'description' => 'Aluguel da clínica',
                'type' => 'expense',
                'category' => 'Estrutura',
                'amount' => 3200,
                'due_date' => $today->copy()->startOfMonth()->addDays(4),
                'paid_at' => $today->copy()->startOfMonth()->addDays(4),
                'status' => 'paid',
                'payment_method' => 'Transferência',
                'notes' => 'Despesa fixa mensal.',
            ],
            [
                'patient_id' => null,
                'appointment_id' => null,
                'description' => 'Materiais médicos',
                'type' => 'expense',
                'category' => 'Insumos',
                'amount' => 780,
                'due_date' => $today->copy()->addDays(5),
                'paid_at' => null,
                'status' => 'pending',
                'payment_method' => 'Boleto',
                'notes' => 'Compra recorrente.',
            ],
        ];

        foreach ($transactions as $data) {
            FinancialTransaction::updateOrCreate(
                [
                    'description' => $data['description'],
                    'due_date' => $data['due_date'],
                    'amount' => $data['amount'],
                ],
                $data,
            );
        }

        $settings = [
            'clinic_name' => 'Clinic SaaS',
            'document' => '12.345.678/0001-90',
            'phone' => '(85) 3333-0000',
            'email' => 'contato@clinic.test',
            'address' => 'Av. Santos Dumont, 1000 - Fortaleza/CE',
            'opening_time' => '08:00',
            'closing_time' => '18:00',
            'appointment_duration' => '30',
            'daily_capacity' => '32',
            'average_wait_minutes' => '14',
            'satisfaction_rate' => '94',
        ];

        foreach ($settings as $key => $value) {
            ClinicSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        foreach ([
            ['title' => 'Paciente Maria Souza cadastrado', 'body' => 'Cadastro inicial criado no sistema.', 'type' => 'success'],
            ['title' => 'Pagamento confirmado', 'body' => 'Consulta Maria Souza paga via Pix.', 'type' => 'success'],
            ['title' => 'Agenda do Dr. Paulo atualizada', 'body' => 'Novos horários foram carregados para hoje.', 'type' => 'info'],
        ] as $notification) {
            ClinicNotification::firstOrCreate(
                ['title' => $notification['title'], 'body' => $notification['body']],
                [...$notification, 'user_id' => $admin->id],
            );
        }
    }
}
