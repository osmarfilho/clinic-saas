<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\ClinicNotification;
use App\Services\AuditLogger;
use App\Services\AppointmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AppointmentController extends Controller
{
    public function index(Request $request, AppointmentService $service): AnonymousResourceCollection
    {
        return AppointmentResource::collection(
            $service->paginate($request->only(['date', 'status', 'search']), $request->integer('per_page', 30))
        );
    }

    public function store(StoreAppointmentRequest $request, AppointmentService $service, AuditLogger $audit): JsonResponse
    {
        $this->authorize('create', Appointment::class);

        $appointment = $service->create($request->validated());
        $audit->log($request, 'appointment.created', $appointment);

        ClinicNotification::create([
            'user_id' => $request->user()?->id,
            'title' => 'Agendamento criado',
            'body' => 'Novo agendamento criado para '.$this->appointmentPatientName($appointment).' em '.$appointment->starts_at->format('d/m/Y').' às '.$appointment->starts_at->format('H:i').'.',
            'type' => 'success',
            'data' => ['appointment_id' => $appointment->id],
        ]);

        return response()->json((new AppointmentResource($appointment->load('patient:id,nome,cpf,telefone,email')))->resolve(), 201);
    }

    public function show(Appointment $appointment): JsonResponse
    {
        $this->authorize('view', $appointment);

        return response()->json((new AppointmentResource($appointment->load('patient:id,nome,cpf,telefone,email')))->resolve());
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment, AppointmentService $service, AuditLogger $audit): JsonResponse
    {
        $this->authorize('update', $appointment);

        $original = $appointment->replicate();
        $service->update($appointment, $request->validated());
        $changedFields = array_keys($appointment->getChanges());
        $statusChanged = $original->status !== $appointment->status;
        $audit->log($request, $statusChanged ? 'appointment.status_changed' : 'appointment.updated', $appointment, [
            'changed_fields' => $changedFields,
            'previous_status' => $original->status,
            'current_status' => $appointment->status,
        ]);

        ClinicNotification::create([
            'user_id' => $request->user()?->id,
            'title' => $statusChanged ? 'Status do agendamento atualizado' : 'Agenda atualizada',
            'body' => $statusChanged
                ? 'Agendamento de '.$this->appointmentPatientName($appointment).' alterado de '.$this->statusLabel($original->status).' para '.$this->statusLabel($appointment->status).'.'
                : $this->appointmentUpdatedMessage($appointment, $original, $changedFields),
            'type' => $statusChanged ? 'info' : 'info',
            'data' => ['appointment_id' => $appointment->id],
        ]);

        return response()->json((new AppointmentResource($appointment->refresh()->load('patient:id,nome,cpf,telefone,email')))->resolve());
    }

    public function destroy(Request $request, Appointment $appointment, AuditLogger $audit): JsonResponse
    {
        $this->authorize('delete', $appointment);

        $audit->log($request, 'appointment.deleted', $appointment);

        ClinicNotification::create([
            'user_id' => $request->user()?->id,
            'title' => 'Agendamento removido',
            'body' => 'Agendamento de '.$this->appointmentPatientName($appointment).' foi removido.',
            'type' => 'warning',
            'data' => ['appointment_id' => $appointment->id],
        ]);

        $appointment->delete();

        return response()->json([
            'message' => 'Agendamento removido com sucesso.',
        ]);
    }

    private function appointmentPatientName(Appointment $appointment): string
    {
        return $appointment->patient?->nome ?? 'Paciente avulso';
    }

    private function statusLabel(string $status): string
    {
        return [
            'scheduled' => 'Agendado',
            'completed' => 'Concluído',
            'no_show' => 'Faltou',
            'cancelled' => 'Cancelado',
        ][$status] ?? $status;
    }

    private function appointmentUpdatedMessage(Appointment $appointment, Appointment $original, array $changedFields): string
    {
        $message = 'Agendamento de '.$this->appointmentPatientName($appointment).' foi alterado.';

        if (in_array('starts_at', $changedFields, true)) {
            return $message.' Data alterada de '.$original->starts_at->format('d/m/Y H:i').' para '.$appointment->starts_at->format('d/m/Y H:i').'.';
        }

        if ($changedFields === []) {
            return $message;
        }

        $labels = [
            'patient_id' => 'paciente',
            'title' => 'título',
            'professional' => 'médico',
            'type' => 'tipo',
            'ends_at' => 'horário de término',
            'status' => 'status',
            'price' => 'valor',
            'notes' => 'observação',
        ];

        $changedLabels = collect($changedFields)
            ->reject(fn (string $field) => $field === 'updated_at')
            ->map(fn (string $field) => $labels[$field] ?? $field)
            ->join(', ', ' e ');

        return $changedLabels ? $message.' Campos alterados: '.$changedLabels.'.' : $message;
    }
}
