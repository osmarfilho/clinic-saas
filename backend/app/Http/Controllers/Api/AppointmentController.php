<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\ClinicNotification;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
        $data = $this->validatedData($request);
        $this->ensureScheduleIsAvailable($data);

        $appointment = Appointment::create($data);

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
        $data = $this->validatedData($request, true);
        $this->ensureScheduleIsAvailable($data, $appointment);

        $previousStatus = $appointment->status;
        $appointment->update($data);

        ClinicNotification::create([
            'user_id' => $request->user()?->id,
            'title' => $appointment->status === 'canceled' && $previousStatus !== 'canceled' ? 'Consulta cancelada' : 'Agenda atualizada',
            'body' => $appointment->status === 'canceled' && $previousStatus !== 'canceled'
                ? $appointment->title.' foi cancelada.'
                : $appointment->title.' foi atualizada.',
            'type' => $appointment->status === 'canceled' && $previousStatus !== 'canceled' ? 'warning' : 'info',
            'data' => ['appointment_id' => $appointment->id],
        ]);

        return response()->json($appointment->refresh()->load('patient:id,nome,cpf,telefone,email'));
    }

    public function destroy(Request $request, Appointment $appointment): JsonResponse
    {
        ClinicNotification::create([
            'user_id' => $request->user()?->id,
            'title' => 'Consulta cancelada',
            'body' => $appointment->title.' foi removida da agenda.',
            'type' => 'warning',
            'data' => ['appointment_id' => $appointment->id],
        ]);

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
            'starts_at' => [$required, 'date', 'after_or_equal:now'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'status' => [$required, Rule::in(['scheduled', 'confirmed', 'completed', 'canceled', 'no_show'])],
            'price' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'notes' => ['nullable', 'string'],
        ], [
            'starts_at.after_or_equal' => 'O agendamento não pode ser criado em uma data ou horário passado.',
            'ends_at.after' => 'O horário de término deve ser posterior ao início.',
        ]);
    }

    private function ensureScheduleIsAvailable(array $data, ?Appointment $appointment = null): void
    {
        if (! array_key_exists('starts_at', $data) && ! array_key_exists('ends_at', $data) && ! array_key_exists('professional', $data)) {
            return;
        }

        $startsAt = Carbon::parse($data['starts_at'] ?? $appointment?->starts_at);
        $endsAt = isset($data['ends_at'])
            ? Carbon::parse($data['ends_at'])
            : Carbon::parse($appointment?->ends_at ?? $startsAt->copy()->addMinutes(30));
        $professional = trim((string) ($data['professional'] ?? $appointment?->professional ?? ''));

        if ($professional === '') {
            return;
        }

        $hasConflict = Appointment::query()
            ->where('professional', $professional)
            ->whereNotIn('status', ['canceled', 'no_show'])
            ->when($appointment, fn ($query) => $query->whereKeyNot($appointment->id))
            ->whereDate('starts_at', $startsAt->toDateString())
            ->get(['id', 'starts_at', 'ends_at'])
            ->contains(function (Appointment $item) use ($startsAt, $endsAt) {
                $itemStartsAt = Carbon::parse($item->starts_at);
                $itemEndsAt = $item->ends_at ? Carbon::parse($item->ends_at) : $itemStartsAt->copy()->addMinutes(30);

                return $itemStartsAt->lt($endsAt) && $itemEndsAt->gt($startsAt);
            });

        if ($hasConflict) {
            throw ValidationException::withMessages([
                'starts_at' => ['Já existe um agendamento para este profissional nesse horário.'],
            ]);
        }
    }
}
