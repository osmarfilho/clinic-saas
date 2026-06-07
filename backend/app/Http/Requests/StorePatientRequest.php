<?php

namespace App\Http\Requests;

use App\Rules\PhoneNumber as PhoneNumberRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'cpf' => $this->normalizeNumericInput('cpf'),
            'telefone' => $this->filled('telefone') ? trim((string) $this->input('telefone')) : null,
            'cep' => $this->filled('cep') ? preg_replace('/\D/', '', (string) $this->input('cep')) : null,
            'numero' => $this->filled('numero') ? $this->normalizeNumericInput('numero') : null,
            'estado' => $this->filled('estado') ? strtoupper((string) $this->input('estado')) : null,
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('manage patients') ?? false;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'cpf' => [
                'required',
                'string',
                'digits:11',
                Rule::unique('patients', 'cpf')->where('clinic_id', $this->user()?->clinic_id),
            ],
            'telefone' => ['nullable', 'string', new PhoneNumberRule()],
            'email' => ['nullable', 'email', 'max:255'],
            'data_nascimento' => ['nullable', 'date'],
            'convenio' => ['nullable', 'string', 'max:255'],
            'cep' => ['nullable', 'string', 'max:9'],
            'endereco' => ['nullable', 'string', 'max:255'],
            'numero' => ['nullable', 'integer', 'min:1'],
            'bairro' => ['nullable', 'string', 'max:255'],
            'cidade' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', 'string', 'size:2'],
            'observacoes' => ['nullable', 'string'],
            'ativo' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'numero.integer' => 'O número do endereço deve conter apenas números.',
            'numero.min' => 'O número do endereço deve ser maior que zero.',
            'cpf.digits' => 'O CPF deve conter exatamente 11 números.',
            'email.email' => 'Informe um e-mail válido.',
        ];
    }

    private function normalizeNumericInput(string $field): string
    {
        $value = (string) $this->input($field);

        return preg_match('/\pL/u', $value) ? $value : preg_replace('/\D/', '', $value);
    }
}
