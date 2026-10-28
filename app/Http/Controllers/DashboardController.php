<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        if ($request->user()->role === 'admin') {
            return view('admin.dashboard', [
                'orderCount' => Order::count(),
                'openCount' => Order::whereNotIn('status', ['delivered', 'cancelled'])->count(),
                'customerCount' => User::where('role', 'customer')->count(),
                'deliveredCount' => Order::where('status', 'delivered')->count(),
                'orders' => Order::with('user')->whereNull('archived_at')->latest()->take(8)->get(),
            ]);
        }

        $user = $request->user();

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
            'activeCount' => $user->orders()->whereNotIn('status', ['delivered', 'cancelled'])->count(),
            'deliveredCount' => $user->orders()->where('status', 'delivered')->count(),
            'deliveryAlerts' => $deliveryAlerts,
            'notifications' => $user->notifications()->latest()->take(5)->get(),
        ]);
    }
}
