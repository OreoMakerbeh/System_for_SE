<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ArchiveController extends Controller
{
    public function archive(Request $request): View
    {
        return view('admin.archive', [
            'orders' => Order::with('user')
                ->whereNotNull('archived_at')
                ->latest('archived_at')
                ->paginate(15),
        ]);
    }

    public function archiveOrder(Request $request, Order $order): RedirectResponse
    {
        DB::transaction(function () use ($order): void {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedOrder->status === 'delivered', 403, 'Only delivered orders can be archived.');
            abort_if($lockedOrder->archived_at !== null, 403, 'This order is already archived.');

            $lockedOrder->update(['archived_at' => now()]);
        });

        return back()->with('success', 'Order #'.$order->display_number.' was added to Customer Data.');
    }

    public function deleteArchivedOrder(Request $request, Order $order): RedirectResponse
    {
        DB::transaction(function () use ($order): void {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedOrder->archived_at !== null, 403, 'Only archived orders can be deleted here.');

            $lockedOrder->user->notifications()->get()->each(function ($notification) use ($lockedOrder): void {
                if ((int) ($notification->data['order_id'] ?? 0) === $lockedOrder->id) {
                    $notification->delete();
                }
            });

            $lockedOrder->delete();
        });

        return back()->with('success', 'Archived order #'.$order->display_number.' was permanently deleted.');
    }
}
