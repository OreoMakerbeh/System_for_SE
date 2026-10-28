<?php

namespace App\Http\Controllers;

use App\Notifications\OrderDeliveredNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function unread(Request $request): JsonResponse
    {
        abort_unless($request->user()->role === 'customer', 403);

        $notifications = $request->user()->unreadNotifications()
            ->where('type', OrderDeliveredNotification::class)
            ->latest()
            ->get();

        $alerts = $notifications->map(function ($notification): array {
            $notification->markAsRead();

            return [
                'message' => $notification->data['message'],
                'url' => route('orders.show', $notification->data['order_id']),
            ];
        })->values();

        return response()->json(['alerts' => $alerts]);
    }

    public function destroy(Request $request, string $notification): RedirectResponse
    {
        abort_unless($request->user()->role === 'customer', 403);

        $request->user()->notifications()
            ->whereKey($notification)
            ->firstOrFail()
            ->delete();

        return back()->with('success', 'Notification deleted.');
    }
}
