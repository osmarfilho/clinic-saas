<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFinancialTransactionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('amount')) {
            $this->merge([
                'amount' => is_string($this->input('amount')) && str_contains($this->input('amount'), ',')
                    ? str_replace(',', '.', str_replace('.', '', $this->input('amount')))
                    : $this->input('amount'),
            ]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->can('access finance') ?? false;
    }

    public function rules(): array
    {
        return [
            'patient_id' => [
                'nullable',
                'required_if:type,income',
                Rule::exists('patients', 'id')->where('clinic_id', $this->user()?->clinic_id),
            ],
            'appointment_id' => [
                'nullable',
                Rule::exists('appointments', 'id')->where('clinic_id', $this->user()?->clinic_id),
            ],
            'description' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', 'required', Rule::in(['income', 'expense'])],
            'category' => ['nullable', 'string', 'max:120'],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0.01', 'max:999999.99'],
            'due_date' => ['sometimes', 'required', 'date'],
            'paid_at' => ['nullable', 'date'],
            'status' => ['sometimes', 'required', Rule::in(['pending', 'paid', 'canceled'])],
            'payment_method' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'patient_id.required_if' => 'Receitas devem estar vinculadas a um paciente.',
            'description.required' => 'Informe a descrição do lançamento.',
            'amount.numeric' => 'Informe um valor monetário válido.',
            'amount.min' => 'O valor deve ser maior que zero.',
            'due_date.required' => 'Informe a data de vencimento.',
        ];
    }
}
