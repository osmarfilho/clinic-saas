<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Models\ClinicNotification;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = $request->string('search')->toString();
        $numericSearch = preg_replace('/\D/', '', $search);

        $patients = Patient::query()
            ->when($request->filled('status'), fn ($query) => $query->where('ativo', $request->boolean('status')))
            ->when($request->filled('convenio'), fn ($query) => $query->where('convenio', $request->string('convenio')->toString()))
            ->when($search, function ($query) use ($search, $numericSearch) {
                $query->where(function ($query) use ($search, $numericSearch) {
                    $query
                        ->where('nome', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");

                    if ($numericSearch !== '') {
                        $query
                            ->orWhere('cpf', 'like', "%{$numericSearch}%")
                            ->orWhere('telefone', 'like', "%{$numericSearch}%");
                    }
                });
            })
            ->orderBy('nome')
            ->paginate($request->integer('per_page', 15));

        return response()->json($patients);
    }

    public function store(StorePatientRequest $request): JsonResponse
    {
        $patient = Patient::create($request->validated());

        ClinicNotification::create([
            'user_id' => $request->user()?->id,
            'title' => 'Novo paciente cadastrado',
            'body' => 'Novo paciente cadastrado: '.$patient->nome.'.',
            'type' => 'success',
            'data' => ['patient_id' => $patient->id],
        ]);

        return response()->json($patient, 201);
    }

    public function show(Patient $patient): JsonResponse
    {
        return response()->json($patient);
    }

    public function update(UpdatePatientRequest $request, Patient $patient): JsonResponse
    {
        $patient->fill($request->validated());
        $changedFields = array_keys($patient->getDirty());
        $patient->save();

        ClinicNotification::create([
            'user_id' => $request->user()?->id,
            'title' => 'Paciente atualizado',
            'body' => $this->patientUpdatedMessage($patient, $changedFields),
            'type' => 'info',
            'data' => ['patient_id' => $patient->id],
        ]);

        return response()->json($patient->refresh());
    }

    public function destroy(Request $request, Patient $patient): JsonResponse
    {
        ClinicNotification::create([
            'user_id' => $request->user()?->id,
            'title' => 'Paciente removido',
            'body' => 'Paciente '.$patient->nome.' foi removido do sistema.',
            'type' => 'warning',
            'data' => ['patient_id' => $patient->id],
        ]);

        $patient->delete();

        return response()->json([
            'message' => 'Paciente removido com sucesso.',
        ]);
    }

    public function restore(Request $request, int $patient): JsonResponse
    {
        $patientModel = Patient::withTrashed()->findOrFail($patient);
        $patientModel->restore();

        ClinicNotification::create([
            'user_id' => $request->user()?->id,
            'title' => 'Paciente restaurado',
            'body' => 'Paciente '.$patientModel->nome.' foi restaurado.',
            'type' => 'success',
            'data' => ['patient_id' => $patientModel->id],
        ]);

        return response()->json($patientModel->refresh());
    }

    private function patientUpdatedMessage(Patient $patient, array $changedFields): string
    {
        if ($changedFields === []) {
            return 'Paciente '.$patient->nome.' foi atualizado.';
        }

        $labels = [
            'nome' => 'nome',
            'cpf' => 'CPF',
            'telefone' => 'telefone',
            'email' => 'e-mail',
            'data_nascimento' => 'data de nascimento',
            'convenio' => 'convênio',
            'cep' => 'CEP',
            'endereco' => 'endereço',
            'numero' => 'número',
            'bairro' => 'bairro',
            'cidade' => 'cidade',
            'estado' => 'estado',
            'observacoes' => 'observações',
            'ativo' => 'status',
        ];

        $changedLabels = collect($changedFields)
            ->map(fn (string $field) => $labels[$field] ?? $field)
            ->join(', ', ' e ');

        return 'Paciente '.$patient->nome.' foi atualizado. Campos alterados: '.$changedLabels.'.';
    }
}
