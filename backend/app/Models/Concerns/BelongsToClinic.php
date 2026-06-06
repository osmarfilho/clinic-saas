<?php

namespace App\Models\Concerns;

use App\Models\Clinic;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToClinic
{
    protected static function bootBelongsToClinic(): void
    {
        static::addGlobalScope('clinic', function (Builder $builder) {
            $user = Auth::user();

            if (! $user instanceof User) {
                return;
            }

            if ($user->hasRole('Super Admin')) {
                return;
            }

            if (! $user->clinic_id) {
                $builder->whereRaw('1 = 0');

                return;
            }

            $builder->where($builder->getModel()->getTable().'.clinic_id', $user->clinic_id);
        });

        static::creating(function ($model) {
            $user = Auth::user();

            if ($user instanceof User && $user->clinic_id && empty($model->clinic_id)) {
                $model->clinic_id = $user->clinic_id;
            }
        });
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function belongsToSameClinicAs(User $user): bool
    {
        return $user->hasRole('Super Admin') || ($user->clinic_id !== null && $this->clinic_id === $user->clinic_id);
    }
}
