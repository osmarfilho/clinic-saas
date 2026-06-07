<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PhoneNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) && ! is_numeric($value)) {
            $fail('Telefone deve conter entre 10 e 11 dígitos.');

            return;
        }

        if (! preg_match('/^\d{10,11}$/', (string) $value)) {
            $fail('Telefone deve conter entre 10 e 11 dígitos.');
        }
    }
}
