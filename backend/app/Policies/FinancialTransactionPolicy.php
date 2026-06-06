<?php

namespace App\Policies;

use App\Models\FinancialTransaction;
use App\Models\User;

class FinancialTransactionPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isSuperAdmin() ? true : null;
    }

    public function view(User $user, FinancialTransaction $financialTransaction): bool
    {
        return $user->can('access finance') && $financialTransaction->belongsToSameClinicAs($user);
    }

    public function update(User $user, FinancialTransaction $financialTransaction): bool
    {
        return $user->can('access finance') && $financialTransaction->belongsToSameClinicAs($user);
    }

    public function delete(User $user, FinancialTransaction $financialTransaction): bool
    {
        return $user->can('access finance') && $financialTransaction->belongsToSameClinicAs($user);
    }
}
