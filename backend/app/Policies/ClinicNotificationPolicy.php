<?php

namespace App\Policies;

use App\Models\ClinicNotification;
use App\Models\User;

class ClinicNotificationPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isSuperAdmin() ? true : null;
    }

    public function view(User $user, ClinicNotification $notification): bool
    {
        return $user->can('view notifications')
            && $notification->belongsToSameClinicAs($user)
            && ($notification->user_id === null || $notification->user_id === $user->id);
    }
}
