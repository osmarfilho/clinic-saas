<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFinancialTransactionRequest extends FormRequest
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
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['nullable', 'required_if:type,income', 'exists:patients,id'],
            'appointment_id' => ['nullable', 'exists:appointments,id'],
            'description' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['income', 'expense'])],
            'category' => ['nullable', 'string', 'max:120'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'due_date' => ['required', 'date'],
            'paid_at' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['pending', 'paid', 'canceled'])],
            'payment_method' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'patient_id.required_if' => 'Receitas devem estar vinculadas a um paciente.',
            'description.required' => 'Informe a descrição do lançamento.',
            'type.required' => 'Informe se o lançamento é receita ou despesa.',
            'amount.required' => 'Informe o valor do lançamento.',
            'amount.numeric' => 'Informe um valor monetário válido.',
            'amount.min' => 'O valor deve ser maior que zero.',
            'due_date.required' => 'Informe a data de vencimento.',
            'status.required' => 'Informe o status do lançamento.',
        ];
    }
}
