<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClinicSetting;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClinicSettingController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        abort_unless($request->user()?->clinic_id, 403);

        return response()->json($this->settings($request->user()->clinic_id));
    }

    public function update(Request $request, AuditLogger $audit): JsonResponse
    {
        abort_unless($request->user()?->clinic_id, 403);

        $data = $request->validate([
            'clinic_name' => ['required', 'string', 'max:255'],
            'document' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'opening_time' => ['required', 'date_format:H:i'],
            'closing_time' => ['required', 'date_format:H:i'],
            'appointment_duration' => ['required', 'integer', 'min:10', 'max:240'],
            'daily_capacity' => ['required', 'integer', 'min:1', 'max:200'],
            'average_wait_minutes' => ['required', 'integer', 'min:0', 'max:240'],
            'satisfaction_rate' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        foreach ($data as $key => $value) {
            ClinicSetting::updateOrCreate(
                ['clinic_id' => $request->user()->clinic_id, 'key' => $key],
                ['value' => (string) $value],
            );
        }

        $audit->log($request, 'clinic_settings.updated', null, ['keys' => array_keys($data)]);

        return response()->json($this->settings($request->user()->clinic_id));
    }

    public static function settings(?int $clinicId = null): array
    {
        $clinicId ??= Auth::user()?->clinic_id;

        $defaults = [
            'clinic_name' => 'Clinic SaaS',
            'document' => '',
            'phone' => '',
            'email' => 'contato@clinic.test',
            'address' => '',
            'opening_time' => '08:00',
            'closing_time' => '18:00',
            'appointment_duration' => '30',
            'daily_capacity' => '32',
            'average_wait_minutes' => '14',
            'satisfaction_rate' => '94',
        ];

        $stored = $clinicId
            ? ClinicSetting::query()->where('clinic_id', $clinicId)->pluck('value', 'key')->all()
            : [];

        return array_merge($defaults, $stored);
    }
}
