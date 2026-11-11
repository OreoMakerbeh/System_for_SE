<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApprovalController extends Controller
{
    public function approvals(Request $request): View
    {
        return view('admin.approvals', [
            'pendingCustomers' => User::where('role', 'customer')
                ->where('is_approved', false)
                ->oldest()
                ->paginate(12),
        ]);
    }

    public function approveCustomer(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->role === 'customer' && ! $user->is_approved, 404);

        $user->is_approved = true;
        $user->save();

        return redirect()->route('admin.approvals')->with('success', $user->name.' can now sign in.');
    }
}
