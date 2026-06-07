<?php

namespace App\Policies;

use App\Models\Clinic;
use App\Models\User;

class ClinicPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, Clinic $clinic): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, Clinic $clinic): bool
    {
        return $user->isSuperAdmin();
    }

    public function activate(User $user, Clinic $clinic): bool
    {
        return $user->isSuperAdmin();
    }

    public function deactivate(User $user, Clinic $clinic): bool
    {
        return $user->isSuperAdmin();
    }
}
