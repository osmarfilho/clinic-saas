<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClinicNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClinicNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = ClinicNotification::query()
            ->where(function ($query) use ($request) {
                $query
                    ->whereNull('user_id')
                    ->orWhere('user_id', $request->user()?->id);
            })
            ->latest()
            ->paginate($request->integer('per_page', 30));

        return response()->json([
            'unread_count' => ClinicNotification::query()
                ->where(function ($query) use ($request) {
                    $query->whereNull('user_id')->orWhere('user_id', $request->user()?->id);
                })
                ->whereNull('read_at')
                ->count(),
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead(Request $request, ClinicNotification $notification): JsonResponse
    {
        abort_unless($notification->user_id === null || $notification->user_id === $request->user()?->id, 403);

        $notification->update(['read_at' => now()]);

        return response()->json($notification->refresh());
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        ClinicNotification::query()
            ->where(function ($query) use ($request) {
                $query->whereNull('user_id')->orWhere('user_id', $request->user()?->id);
            })
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'Notificações marcadas como lidas.']);
    }
}
