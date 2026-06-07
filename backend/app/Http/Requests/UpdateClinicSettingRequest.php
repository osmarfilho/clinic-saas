<?php

namespace App\Http\Requests;

use App\Rules\PhoneNumber as PhoneNumberRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateClinicSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage clinic settings') ?? false;
    }

    public function rules(): array
    {
        return [
            'clinic_name' => ['required', 'string', 'max:255'],
            'document' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', new PhoneNumberRule()],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'opening_time' => ['required', 'date_format:H:i'],
            'closing_time' => ['required', 'date_format:H:i'],
            'appointment_duration' => ['required', 'integer', 'min:10', 'max:240'],
            'daily_capacity' => ['required', 'integer', 'min:1', 'max:200'],
            'average_wait_minutes' => ['required', 'integer', 'min:0', 'max:240'],
            'satisfaction_rate' => ['required', 'integer', 'min:0', 'max:100'],
        ];
    }
}
