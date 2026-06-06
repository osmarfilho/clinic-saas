<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use BelongsToClinic, HasFactory, SoftDeletes;

    protected $fillable = [
        'clinic_id',
        'patient_id',
        'title',
        'professional',
        'type',
        'starts_at',
        'ends_at',
        'status',
        'price',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime:Y-m-d H:i:s',
            'ends_at' => 'datetime:Y-m-d H:i:s',
            'price' => 'decimal:2',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
