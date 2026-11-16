<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function customers(Request $request): View
    {
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

    public function updateCustomer(Request $request, User $user): RedirectResponse
    {
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
        abort_unless($user->role === 'customer', 404);

        $user->delete();

        return back()->with('success', 'Customer and their order history were removed.');
    }
}
