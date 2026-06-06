<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isSuperAdmin() ? true : null;
    }

    public function view(User $user, Patient $patient): bool
    {
        return $user->can('view patients') && $patient->belongsToSameClinicAs($user);
    }

    public function update(User $user, Patient $patient): bool
    {
        return $user->can('manage patients') && $patient->belongsToSameClinicAs($user);
    }

    public function delete(User $user, Patient $patient): bool
    {
        return $user->can('manage patients') && $patient->belongsToSameClinicAs($user);
    }

    public function restore(User $user, Patient $patient): bool
    {
        return $user->can('manage patients') && $patient->belongsToSameClinicAs($user);
    }
}
