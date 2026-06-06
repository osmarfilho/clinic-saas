<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePatientRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = [];

        foreach (['cpf', 'telefone', 'cep', 'numero'] as $field) {
            if ($this->has($field)) {
                $data[$field] = $this->filled($field)
                    ? ($field === 'cep' ? preg_replace('/\D/', '', (string) $this->input($field)) : $this->normalizeNumericInput($field))
                    : null;
            }
        }

        if ($this->has('estado')) {
            $data['estado'] = $this->filled('estado') ? strtoupper((string) $this->input('estado')) : null;
        }

        $this->merge($data);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('manage patients') ?? false;
    }

    public function rules(): array
    {
        $patientId = $this->route('patient')?->id;

        return [
            'nome' => ['sometimes', 'required', 'string', 'max:255'],
            'cpf' => [
                'sometimes',
                'required',
                'string',
                'digits:11',
                Rule::unique('patients', 'cpf')
                    ->where('clinic_id', $this->user()?->clinic_id)
                    ->ignore($patientId),
            ],
            'telefone' => ['nullable', 'string', 'digits_between:10,11'],
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
            'telefone.digits_between' => 'O telefone deve conter 10 ou 11 números, incluindo DDD.',
            'email.email' => 'Informe um e-mail válido.',
        ];
    }

    private function normalizeNumericInput(string $field): string
    {
        $value = (string) $this->input($field);

        return preg_match('/\pL/u', $value) ? $value : preg_replace('/\D/', '', $value);
    }
}
