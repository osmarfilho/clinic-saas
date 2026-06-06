<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\ClinicNotification;
use App\Models\FinancialTransaction;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $today = Carbon::today();
        $startOfMonth = $today->copy()->startOfMonth();
        $endOfMonth = $today->copy()->endOfMonth();
        $settings = ClinicSettingController::settings();
        $dailyCapacity = max((int) $settings['daily_capacity'], 1);

        $appointmentsToday = Appointment::query()->whereDate('starts_at', $today)->count();
        $pendingToday = Appointment::query()
            ->whereDate('starts_at', $today)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->count();
        $monthAppointments = Appointment::query()->whereBetween('starts_at', [$startOfMonth, $endOfMonth])->count();
        $monthNoShows = Appointment::query()
            ->whereBetween('starts_at', [$startOfMonth, $endOfMonth])
            ->where('status', 'no_show')
            ->count();

        $monthlyRevenue = FinancialTransaction::query()
            ->where('type', 'income')
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $monthlyExpenses = FinancialTransaction::query()
            ->where('type', 'expense')
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        return response()->json([
            'metrics' => [
                'active_patients' => Patient::query()->where('ativo', true)->count(),
                'appointments_today' => $appointmentsToday,
                'pending_today' => $pendingToday,
                'monthly_revenue' => (float) $monthlyRevenue,
                'monthly_expenses' => (float) $monthlyExpenses,
                'occupancy_rate' => round(($appointmentsToday / $dailyCapacity) * 100),
            ],
            'indicators' => [
                'average_wait_minutes' => (int) $settings['average_wait_minutes'],
                'satisfaction_rate' => (int) $settings['satisfaction_rate'],
                'no_show_rate' => $monthAppointments > 0 ? round(($monthNoShows / $monthAppointments) * 100) : 0,
            ],
            'schedule' => Appointment::query()
                ->with('patient:id,nome')
                ->whereDate('starts_at', $today)
                ->orderBy('starts_at')
                ->limit(8)
                ->get(),
            'activities' => ClinicNotification::query()
                ->where(function ($query) use ($request) {
                    $query->whereNull('user_id')->orWhere('user_id', $request->user()?->id);
                })
                ->latest()
                ->limit(6)
                ->get(),
        ]);
    }
}
