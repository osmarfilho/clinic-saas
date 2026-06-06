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

        $patients = Patient::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('nome', 'like', "%{$search}%")
                        ->orWhere('cpf', 'like', "%{$search}%")
                        ->orWhere('telefone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json($patients);
    }

    public function store(StorePatientRequest $request): JsonResponse
    {
        $patient = Patient::create($request->validated());

        ClinicNotification::create([
            'user_id' => $request->user()?->id,
            'title' => 'Novo paciente cadastrado',
            'body' => 'Novo paciente cadastrado: '.$patient->nome,
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
        $patient->update($request->validated());

        ClinicNotification::create([
            'user_id' => $request->user()?->id,
            'title' => 'Paciente atualizado',
            'body' => 'Paciente atualizado: '.$patient->nome,
            'type' => 'info',
            'data' => ['patient_id' => $patient->id],
        ]);

        return response()->json($patient->refresh());
    }

    public function destroy(Patient $patient): JsonResponse
    {
        $patient->delete();

        return response()->json([
            'message' => 'Paciente removido com sucesso.',
        ]);
    }
}
