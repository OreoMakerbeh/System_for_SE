<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderDeliveredNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function orders(Request $request): View
    {
        $this->ensureAdmin($request);

        $selectedStatus = $request->query('status');
        $orders = Order::with(['user', 'rider'])->whereNull('archived_at')->latest();

        if (array_key_exists($selectedStatus, Order::STATUSES)) {
            $orders->where('status', $selectedStatus);
        }

        return view('admin.orders', [
            'orders' => $orders->paginate(15)->withQueryString(),
            'statuses' => Order::STATUSES,
            'selectedStatus' => $selectedStatus,
        ]);
    }

    public function archive(Request $request): View
    {
        $this->ensureAdmin($request);

        return view('admin.archive', [
            'orders' => Order::with('user')
                ->whereNotNull('archived_at')
                ->latest('archived_at')
                ->paginate(15),
        ]);
    }

    public function editOrder(Request $request, Order $order): View
    {
        $this->ensureAdmin($request);
        $this->ensureOrderEditable($order);

        return view('admin.orders-edit', [
            'order' => $order->load('user'),
            'riders' => User::where('role', 'rider')->orderBy('name')->get(),
        ]);
    }

    public function saveOrder(Request $request, Order $order): RedirectResponse
    {
        $this->ensureAdmin($request);
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

    public function archiveOrder(Request $request, Order $order): RedirectResponse
    {
        $this->ensureAdmin($request);

        DB::transaction(function () use ($order): void {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedOrder->status === 'delivered', 403, 'Only delivered orders can be archived.');
            abort_if($lockedOrder->archived_at !== null, 403, 'This order is already archived.');

            $lockedOrder->update(['archived_at' => now()]);
        });

        return back()->with('success', 'Order #'.$order->display_number.' was added to Costumer Data.');
    }

    public function deleteArchivedOrder(Request $request, Order $order): RedirectResponse
    {
        $this->ensureAdmin($request);

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

    public function customers(Request $request): View
    {
        $this->ensureAdmin($request);

        return view('admin.customers', [
            'customers' => User::where('role', 'customer')
                ->where('is_approved', true)
                ->withCount('orders')
                ->withSum('orders', 'quantity')
                ->withMax('orders', 'created_at')
                ->orderBy('name')
                ->paginate(12),
        ]);
    }

    public function approvals(Request $request): View
    {
        $this->ensureAdmin($request);

        return view('admin.approvals', [
            'pendingCustomers' => User::where('role', 'customer')
                ->where('is_approved', false)
                ->oldest()
                ->paginate(12),
        ]);
    }

    public function approveCustomer(Request $request, User $user): RedirectResponse
    {
        $this->ensureAdmin($request);
        abort_unless($user->role === 'customer' && ! $user->is_approved, 404);

        $user->is_approved = true;
        $user->save();

        return redirect()->route('admin.approvals')->with('success', $user->name.' can now sign in.');
    }

    public function administrators(Request $request): View
    {
        $this->ensureAdmin($request);

        return view('admin.administrators', [
            'administrators' => User::where('role', 'admin')->orderBy('name')->paginate(12),
        ]);
    }

    public function riders(Request $request): View
    {
        $this->ensureAdmin($request);

        return view('admin.riders', [
            'riders' => User::where('role', 'rider')->withCount('assignedDeliveries')->orderBy('name')->paginate(12),
        ]);
    }

    public function storeRider(Request $request): RedirectResponse
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'middle_name' => ['nullable', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:40', 'unique:users,phone'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $rider = new User([
            'first_name' => $data['first_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
        ]);
        $rider->role = 'rider';
        $rider->save();

        return back()->with('success', 'Rider account created.');
    }

    public function storeAdministrator(Request $request): RedirectResponse
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'middle_name' => ['nullable', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $administrator = new User([
            'first_name' => $data['first_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);
        $administrator->role = 'admin';
        $administrator->save();

        return back()->with('success', 'Administrator account created.');
    }

    public function updateAdministrator(Request $request, User $user): RedirectResponse
    {
        $this->ensureAdmin($request);
        abort_unless($user->role === 'admin', 404);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'middle_name' => ['nullable', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);

        $user->first_name = $data['first_name'];
        $user->middle_name = $data['middle_name'] ?? null;
        $user->last_name = $data['last_name'];
        $user->email = $data['email'];

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        return back()->with('success', 'Administrator account updated.');
    }

    public function revokeAdministrator(Request $request, User $user): RedirectResponse
    {
        $this->ensureAdmin($request);
        abort_unless($user->role === 'admin', 404);
        abort_if($request->user()->is($user), 403, 'You cannot revoke your own administrator access.');

        DB::transaction(function () use ($user): void {
            $administrators = User::where('role', 'admin')
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id']);

            abort_if($administrators->count() <= 1, 403, 'The last administrator account cannot be revoked.');
            abort_unless($administrators->contains('id', $user->id), 404);

            $user->role = 'customer';
            $user->save();
        });

        return back()->with('success', 'Administrator access revoked. The account and its order history were kept.');
    }

    public function updateCustomer(Request $request, User $user): RedirectResponse
    {
        $this->ensureAdmin($request);
        abort_unless($user->role === 'customer', 404);

        $user->update($request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'middle_name' => ['nullable', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'string', 'max:40', Rule::unique('users', 'phone')->ignore($user->id)],
            'address' => ['required', 'string', 'max:1000'],
        ]));

        return back()->with('success', 'Customer details updated.');
    }

    public function deleteCustomer(Request $request, User $user): RedirectResponse
    {
        $this->ensureAdmin($request);
        abort_unless($user->role === 'customer', 404);

        $user->delete();

        return back()->with('success', 'Customer and their order history were removed.');
    }

    public function updateOrder(Request $request, Order $order): RedirectResponse
    {
        $this->ensureAdmin($request);
        abort_if($order->status === 'cancelled', 403, 'Cancelled orders cannot be changed.');
        abort_if($order->archived_at !== null, 403, 'Archived orders cannot be changed.');

        $data = $request->validate([
            'status' => ['required', Rule::in(array_diff(array_keys(Order::STATUSES), ['cancelled']))],
        ]);

        DB::transaction(function () use ($order, $data): void {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_if($order->status === 'cancelled', 403, 'Cancelled orders cannot be changed.');
            abort_if($order->archived_at !== null, 403, 'Archived orders cannot be changed.');
            $wasDelivered = $order->status === 'delivered';

            $order->update($data);

            if (! $wasDelivered && $order->status === 'delivered') {
                $order->user->notify(new OrderDeliveredNotification($order));
            }
        });

        return back()->with('success', 'Order #'.$order->display_number.' status updated.');
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()->role === 'admin', 403);
    }

    private function ensureOrderEditable(Order $order): void
    {
        abort_if($order->status === 'cancelled', 403, 'Cancelled orders cannot be changed.');
        abort_if($order->archived_at !== null, 403, 'Archived orders cannot be changed.');
    }
}
