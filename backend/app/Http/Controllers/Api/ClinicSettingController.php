<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClinicSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClinicSettingController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json($this->settings());
    }

    public function update(Request $request): JsonResponse
    {
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
            ClinicSetting::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        }

        return response()->json($this->settings());
    }

    public static function settings(): array
    {
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

        $stored = ClinicSetting::query()->pluck('value', 'key')->all();

        return array_merge($defaults, $stored);
    }
}
