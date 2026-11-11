<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderDeliveredNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function orders(Request $request): View
    {
        $selectedStatus = $request->query('status');
        $orders = Order::with(['user', 'rider'])->whereNull('archived_at')->latest();

        if (is_string($selectedStatus) && array_key_exists($selectedStatus, Order::STATUSES)) {
            $orders->where('status', $selectedStatus);
        }

        return view('admin.orders.index', [
            'orders' => $orders->paginate(15)->withQueryString(),
            'statuses' => Order::STATUSES,
            'selectedStatus' => $selectedStatus,
        ]);
    }

    public function editOrder(Request $request, Order $order): View
    {
        $this->ensureOrderEditable($order);

        return view('admin.orders.edit', [
            'order' => $order->load('user'),
            'riders' => User::where('role', 'rider')->orderBy('name')->get(),
        ]);
    }

    public function saveOrder(Request $request, Order $order): RedirectResponse
    {
        $this->ensureOrderEditable($order);

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'delivery_address' => ['required', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'rider_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'rider')],
        ]);

        DB::transaction(function () use ($order, $data): void {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $this->ensureOrderEditable($lockedOrder);
            $lockedOrder->update($data);
        });

        return redirect()->route('admin.orders')
            ->with('success', 'Order #'.$order->display_number.' details updated.');
    }

    public function updateOrder(Request $request, Order $order): RedirectResponse
    {
        $this->ensureOrderEditable($order);

        DB::transaction(function () use ($order, $request): void {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $this->ensureOrderEditable($lockedOrder);

            // Validate the latest status while locked, in case another update just arrived.
            $data = $request->validate([
                'status' => ['required', Rule::in(array_keys($lockedOrder->availableStatusOptions()))],
            ], [
                'status.in' => 'Choose the current status or a later delivery step. Orders cannot move backwards.',
            ]);

            $lockedOrder->fill($data);

            if (! $lockedOrder->isDirty('status')) {
                return;
            }

            $lockedOrder->save();

            if ($lockedOrder->status === 'delivered') {
                $lockedOrder->user->notify(new OrderDeliveredNotification($lockedOrder));
            }
        });

        return back()->with('success', 'Order #'.$order->display_number.' status updated.');
    }

    private function ensureOrderEditable(Order $order): void
    {
        abort_if($order->status === 'cancelled', 403, 'Cancelled orders cannot be changed.');
        abort_if($order->archived_at !== null, 403, 'Archived orders cannot be changed.');
    }
}
