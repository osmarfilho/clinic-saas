<?php

namespace App\Services;

use App\Models\Clinic;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogger
{
    public function log(Request $request, string $event, ?Model $auditable = null, array $metadata = []): void
    {
        $user = $request->user();
        $clinicId = data_get($auditable, 'clinic_id');

        if ($clinicId === null && $auditable instanceof Clinic) {
            $clinicId = $auditable->getKey();
        }

        AuditLog::create([
            'clinic_id' => $clinicId ?? $user?->clinic_id,
            'user_id' => $user?->id,
            'event' => $event,
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id' => $auditable?->getKey(),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            'metadata' => $metadata,
        ]);
    }
}
