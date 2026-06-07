<?php

namespace App\Http\Requests;

use App\Enums\AppointmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage appointments') ?? false;
    }

    public function rules(): array
    {
        return [
            'patient_id' => [
                'nullable',
                Rule::exists('patients', 'id')->where('clinic_id', $this->user()?->clinic_id),
            ],
            'title' => ['required', 'string', 'max:255'],
            'professional' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:80'],
            'starts_at' => ['required', 'date', 'after_or_equal:now'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'status' => ['required', Rule::enum(AppointmentStatus::class)],
            'price' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'patient_id.exists' => 'O paciente informado não pertence à clínica atual.',
            'title.required' => 'Informe o título do agendamento.',
            'type.required' => 'Informe o tipo do atendimento.',
            'starts_at.required' => 'Informe a data e hora de início.',
            'starts_at.after_or_equal' => 'O agendamento não pode ser criado em uma data ou horário passado.',
            'ends_at.after' => 'O horário de término deve ser posterior ao início.',
            'status.required' => 'Informe o status do agendamento.',
            'status.enum' => 'O status do agendamento é inválido.',
        ];
    }
}
