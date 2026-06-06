<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use BelongsToClinic, HasFactory, SoftDeletes;

    protected $fillable = [
        'clinic_id',
        'nome',
        'cpf',
        'telefone',
        'email',
        'data_nascimento',
        'convenio',
        'cep',
        'endereco',
        'numero',
        'bairro',
        'cidade',
        'estado',
        'observacoes',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'data_nascimento' => 'date:Y-m-d',
            'ativo' => 'boolean',
        ];
    }
}
