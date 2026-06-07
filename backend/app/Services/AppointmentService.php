<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    public function paginate(array $filters = [], int $perPage = 30): LengthAwarePaginator
    {
        return Appointment::query()
            ->with('patient:id,nome,cpf,telefone,email')
            ->when(! empty($filters['date']), fn ($query) => $query->whereDate('starts_at', $filters['date']))
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(! empty($filters['search']), function ($query) use ($filters) {
                $search = (string) $filters['search'];

                $query->where(function ($query) use ($search) {
                    $query
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('professional', 'like', "%{$search}%")
                        ->orWhereHas('patient', fn ($query) => $query->where('nome', 'like', "%{$search}%"));
                });
            })
            ->orderBy('starts_at')
            ->paginate($perPage);
    }

    public function create(array $data): Appointment
    {
        $this->ensureScheduleWindowIsValid($data);
        $this->ensureScheduleIsAvailable($data);

        return Appointment::create($data);
    }

    public function update(Appointment $appointment, array $data): Appointment
    {
        $this->ensureScheduleWindowIsValid($data, $appointment);
        $this->ensureScheduleIsAvailable($data, $appointment);

        $appointment->fill($data);
        $appointment->save();

        return $appointment;
    }

    public function delete(Appointment $appointment): void
    {
        $appointment->delete();
    }

    public function ensureScheduleIsAvailable(array $data, ?Appointment $appointment = null): void
    {
        if (! array_key_exists('starts_at', $data) && ! array_key_exists('ends_at', $data) && ! array_key_exists('professional', $data)) {
            return;
        }

        $status = $data['status'] ?? $appointment?->status ?? AppointmentStatus::Scheduled->value;

        if ($status !== AppointmentStatus::Scheduled->value) {
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
            ->where('status', AppointmentStatus::Scheduled->value)
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

    public function ensureScheduleWindowIsValid(array $data, ?Appointment $appointment = null): void
    {
        $hasStartsAt = array_key_exists('starts_at', $data);
        $hasEndsAt = array_key_exists('ends_at', $data);

        if ($appointment && ! $hasStartsAt && ! $hasEndsAt) {
            return;
        }

        $newStartsAt = $hasStartsAt ? Carbon::parse($data['starts_at']) : $appointment?->starts_at;
        $newEndsAt = $hasEndsAt
            ? ($data['ends_at'] !== null ? Carbon::parse($data['ends_at']) : null)
            : $appointment?->ends_at;

        if ($newStartsAt && $this->hasDateChanged($appointment?->starts_at, $newStartsAt) && $newStartsAt->lt(now())) {
            throw ValidationException::withMessages([
                'starts_at' => ['O agendamento não pode ser criado em uma data ou horário passado.'],
            ]);
        }

        if ($hasEndsAt && $newEndsAt !== null && $this->hasDateChanged($appointment?->ends_at, $newEndsAt) && $newEndsAt->lt(now())) {
            throw ValidationException::withMessages([
                'ends_at' => ['O horário informado não pode estar no passado.'],
            ]);
        }

        if ($newStartsAt && $newEndsAt && $newEndsAt->lte($newStartsAt)) {
            throw ValidationException::withMessages([
                'ends_at' => ['O horário de término deve ser posterior ao início.'],
            ]);
        }
    }

    private function hasDateChanged(null|Carbon|string $original, Carbon $candidate): bool
    {
        if ($original === null) {
            return true;
        }

        return ! Carbon::parse($original)->equalTo($candidate);
    }
}
