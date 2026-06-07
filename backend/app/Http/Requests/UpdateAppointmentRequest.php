<?php

namespace App\Http\Requests;

use App\Enums\AppointmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage appointments') ?? false;
    }

    public function rules(): array
    {
        return [
            'patient_id' => [
                'sometimes',
                'nullable',
                Rule::exists('patients', 'id')->where('clinic_id', $this->user()?->clinic_id),
            ],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'professional' => ['nullable', 'string', 'max:255'],
            'type' => ['sometimes', 'required', 'string', 'max:80'],
            'starts_at' => ['sometimes', 'required', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date'],
            'status' => ['sometimes', 'required', Rule::enum(AppointmentStatus::class)],
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
            'status.required' => 'Informe o status do agendamento.',
            'status.enum' => 'O status do agendamento é inválido.',
        ];
    }
}
