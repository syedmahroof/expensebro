<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse|Response
    {
        $user = Auth::user();

        // Bell dropdown uses XMLHttpRequest; return JSON for backwards compatibility.
        if ($request->header('X-Requested-With') === 'XMLHttpRequest' && ! $request->header('X-Inertia')) {
            $notifications = $user->appNotifications()
                ->orderByDesc('created_at')
                ->limit(15)
                ->get();

            return response()->json([
                'notifications' => $notifications,
                'unread_count' => $notifications->where('read', false)->count(),
            ]);
        }

        $notifications = $user->appNotifications()
            ->orderByDesc('created_at')
            ->paginate(30);

        $unreadCount = $user->appNotifications()->where('read', false)->count();

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }

    public function markRead(Request $request): JsonResponse|RedirectResponse
    {
        $ids = $request->input('ids', []);

        AppNotification::where('user_id', Auth::id())
            ->when($ids, fn ($q) => $q->whereIn('id', $ids), fn ($q) => $q)
            ->update(['read' => true]);

        if ($request->header('X-Requested-With') === 'XMLHttpRequest' && ! $request->header('X-Inertia')) {
            return response()->json(['ok' => true]);
        }

        return back();
    }
}
