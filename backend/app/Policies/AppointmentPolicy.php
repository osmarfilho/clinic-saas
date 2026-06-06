<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isSuperAdmin() ? true : null;
    }

    public function view(User $user, Appointment $appointment): bool
    {
        return $user->can('manage appointments') && $appointment->belongsToSameClinicAs($user);
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $user->can('manage appointments') && $appointment->belongsToSameClinicAs($user);
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->can('manage appointments') && $appointment->belongsToSameClinicAs($user);
    }
}
