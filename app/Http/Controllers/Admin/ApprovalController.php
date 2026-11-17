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
            'pendingAccounts' => User::whereIn('role', ['customer', 'rider'])
                ->where('is_approved', false)
                ->whereNull('declined_at')
                ->oldest()
                ->orderBy('id')
                ->paginate(12)
                ->withQueryString(),
            'declinedAccounts' => User::whereIn('role', ['customer', 'rider'])
                ->where('is_approved', false)
                ->whereNotNull('declined_at')
                ->orderByDesc('declined_at')
                ->orderByDesc('id')
                ->paginate(12, ['*'], 'declined_page')
                ->withQueryString(),
        ]);
    }

    public function approveAccount(Request $request, User $user): RedirectResponse
    {
        $updated = User::whereKey($user->id)
            ->whereIn('role', ['customer', 'rider'])
            ->where('is_approved', false)
            ->update(['is_approved' => true, 'declined_at' => null]);

        abort_unless($updated, 404);

        return redirect()->route('admin.approvals')->with('success', $user->name.' can now sign in.');
    }

    public function declineAccount(Request $request, User $user): RedirectResponse
    {
        $updated = User::whereKey($user->id)
            ->whereIn('role', ['customer', 'rider'])
            ->where('is_approved', false)
            ->whereNull('declined_at')
            ->update(['declined_at' => now()]);

        abort_unless($updated, 404);

        return redirect()->route('admin.approvals')
            ->with('success', $user->name.' was declined and cannot sign in.');
    }
}
