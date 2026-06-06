<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\ClinicNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $appointments = Appointment::query()
            ->with('patient:id,nome,cpf,telefone,email')
            ->when($request->filled('date'), fn ($query) => $query->whereDate('starts_at', $request->date('date')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($query) use ($search) {
                    $query
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('professional', 'like', "%{$search}%")
                        ->orWhereHas('patient', fn ($query) => $query->where('nome', 'like', "%{$search}%"));
                });
            })
            ->orderBy('starts_at')
            ->paginate($request->integer('per_page', 30));

        return response()->json($appointments);
    }

    public function store(Request $request): JsonResponse
    {
        $appointment = Appointment::create($this->validatedData($request));

        ClinicNotification::create([
            'user_id' => $request->user()?->id,
            'title' => 'Consulta agendada',
            'body' => $appointment->title.' em '.$appointment->starts_at->format('d/m/Y H:i'),
            'type' => 'success',
            'data' => ['appointment_id' => $appointment->id],
        ]);

        return response()->json($appointment->load('patient:id,nome,cpf,telefone,email'), 201);
    }

    public function show(Appointment $appointment): JsonResponse
    {
        return response()->json($appointment->load('patient:id,nome,cpf,telefone,email'));
    }

    public function update(Request $request, Appointment $appointment): JsonResponse
    {
        $appointment->update($this->validatedData($request, true));

        ClinicNotification::create([
            'user_id' => $request->user()?->id,
            'title' => 'Agenda atualizada',
            'body' => $appointment->title.' foi atualizada.',
            'type' => 'info',
            'data' => ['appointment_id' => $appointment->id],
        ]);

        return response()->json($appointment->refresh()->load('patient:id,nome,cpf,telefone,email'));
    }

    public function destroy(Appointment $appointment): JsonResponse
    {
        $appointment->delete();

        return response()->json([
            'message' => 'Agendamento removido com sucesso.',
        ]);
    }

    private function validatedData(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'patient_id' => ['nullable', 'exists:patients,id'],
            'title' => [$required, 'string', 'max:255'],
            'professional' => ['nullable', 'string', 'max:255'],
            'type' => [$required, 'string', 'max:80'],
            'starts_at' => [$required, 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'status' => [$required, Rule::in(['scheduled', 'confirmed', 'completed', 'canceled', 'no_show'])],
            'price' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
