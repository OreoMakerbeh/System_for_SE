<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        abort_unless($request->user()->role === 'customer', 403);

        return view('orders.create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === 'customer', 403);

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'delivery_address' => ['required', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $order = DB::transaction(function () use ($request, $data): Order {
            $sequence = DB::table('order_number_sequences')
                ->where('id', 1)
                ->lockForUpdate()
                ->firstOrFail();

            DB::table('order_number_sequences')
                ->where('id', 1)
                ->update(['lock_version' => $sequence->lock_version + 1]);

            $usedNumbers = Order::whereNotNull('order_number')
                ->orderBy('order_number')
                ->pluck('order_number');
            $orderNumber = 1;

            foreach ($usedNumbers as $usedNumber) {
                if ((int) $usedNumber > $orderNumber) {
                    break;
                }

                if ((int) $usedNumber === $orderNumber) {
                    $orderNumber++;
                }
            }

            return $request->user()->orders()->create([
                ...$data,
                'order_number' => $orderNumber,
            ]);
        });

        return redirect()->route('orders.show', $order)->with('success', 'Order received. We will confirm it shortly.');
    }

    public function show(Request $request, Order $order): View
    {
        abort_unless($request->user()->role === 'customer', 403);
        abort_unless($order->user_id === $request->user()->id, 404);

        $order->load(['rider', 'deliveryUpdates.rider']);

        return view('orders.show', compact('order'));
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        abort_unless($request->user()->role === 'customer', 403);
        abort_unless($order->user_id === $request->user()->id, 404);

        DB::transaction(function () use ($order, $request): void {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedOrder->user_id === $request->user()->id, 404);
            abort_unless($lockedOrder->status === 'pending', 403, 'Only pending orders can be cancelled.');

            $lockedOrder->update(['status' => 'cancelled']);
        });

        return back()->with('success', 'Order #'.$order->display_number.' was cancelled.');
    }

    public function destroy(Request $request, Order $order): RedirectResponse
    {
        abort_unless($request->user()->role === 'customer', 403);
        abort_unless($order->user_id === $request->user()->id, 404);

        DB::transaction(function () use ($order, $request): void {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedOrder->user_id === $request->user()->id, 404);
            abort_unless(
                in_array($lockedOrder->status, ['delivered', 'cancelled'], true),
                403,
                'Only delivered or cancelled orders can be deleted.'
            );
            abort_if($lockedOrder->archived_at !== null, 403, 'Archived orders can only be deleted by an administrator.');

            $request->user()->notifications()->get()->each(function ($notification) use ($lockedOrder): void {
                if ((int) ($notification->data['order_id'] ?? 0) === $lockedOrder->id) {
                    $notification->delete();
                }
            });

            $lockedOrder->delete();
        });

        return redirect()->route('dashboard')->with('success', 'Order #'.$order->display_number.' was deleted.');
    }
}
