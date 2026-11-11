<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        if ($user->role === 'admin') {
            return view('admin.dashboard', [
                'orders' => Order::with('user')->whereNull('archived_at')->latest()->take(8)->get(),
            ]);
        }

        if ($user->role === 'rider') {
            $orders = $user->assignedDeliveries()
                ->with(['user', 'deliveryUpdates'])
                ->whereNull('archived_at')
                ->latest()
                ->paginate(12);
            $availableOrders = Order::with('user')
                ->where('status', 'processing')
                ->whereNull('rider_id')
                ->whereNull('archived_at')
                ->latest()
                ->paginate(12, ['*'], 'available_page');

            return view('rider.dashboard', [
                'orders' => $orders,
                'activeCount' => $user->assignedDeliveries()->whereNotIn('status', ['delivered', 'cancelled'])->count(),
                'availableOrders' => $availableOrders,
            ]);
        }

        $orders = $user->orders()->latest()->paginate(6);
        $deliveryAlerts = $user->unreadNotifications()->latest()->get();

        foreach ($deliveryAlerts as $deliveryAlert) {
            $deliveryAlert->markAsRead();
        }

        return view('customer.dashboard', [
            'orders' => $orders,
            'deliveryAlerts' => $deliveryAlerts,
            'notifications' => $user->notifications()->latest()->take(5)->get(),
        ]);
    }
}
