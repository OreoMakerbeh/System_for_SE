<?php

namespace App\Http\Controllers;

use App\Models\DeliveryUpdate;
use App\Models\Order;
use App\Notifications\OrderDeliveredNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DeliveryUpdateController extends Controller
{
    public function claim(Request $request, Order $order): RedirectResponse
    {
        abort_unless($request->user()->role === 'rider', 403);

        DB::transaction(function () use ($order, $request): void {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (
                $lockedOrder->status !== 'processing'
                || $lockedOrder->rider_id !== null
                || $lockedOrder->archived_at !== null
            ) {
                throw ValidationException::withMessages([
                    'order' => 'This order is no longer available to claim.',
                ]);
            }

            $lockedOrder->update(['rider_id' => $request->user()->id]);
        });

        return back()->with('success', 'Order #'.$order->display_number.' added to your deliveries.');
    }

    public function store(Request $request, Order $order): RedirectResponse
    {
        abort_unless($request->user()->role === 'rider' && $order->rider_id === $request->user()->id, 403);

        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(DeliveryUpdate::MILESTONES))],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($order, $request, $data): void {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedOrder->rider_id === $request->user()->id, 403);
            abort_if(in_array($lockedOrder->status, ['cancelled', 'delivered'], true), 403, 'This delivery is already closed.');
            abort_if($lockedOrder->archived_at !== null, 403, 'Archived orders cannot be updated.');
            abort_unless(in_array($lockedOrder->status, ['processing', 'out_for_delivery'], true), 403, 'This order is not ready for delivery updates.');

            $milestoneKeys = array_keys(DeliveryUpdate::MILESTONES);
            $latestUpdate = $lockedOrder->deliveryUpdates()->first();
            $latestMilestoneIndex = $latestUpdate
                ? array_search($latestUpdate->status, $milestoneKeys, true)
                : -1;
            $expectedMilestone = $milestoneKeys[$latestMilestoneIndex + 1] ?? null;

            if ($expectedMilestone !== $data['status']) {
                throw ValidationException::withMessages([
                    'status' => 'Delivery updates must follow the next milestone.',
                ]);
            }

            $lockedOrder->deliveryUpdates()->create([
                'rider_id' => $request->user()->id,
                'status' => $data['status'],
                'message' => $data['message'] ?? null,
            ]);

            if ($data['status'] === 'out_for_delivery') {
                $lockedOrder->update(['status' => 'out_for_delivery']);
            } elseif ($data['status'] === 'delivered') {
                $lockedOrder->update(['status' => 'delivered']);
                $lockedOrder->user->notify(new OrderDeliveredNotification($lockedOrder));
            }
        });

        return back()->with('success', 'Delivery update posted for order #'.$order->display_number.'.');
    }
}
