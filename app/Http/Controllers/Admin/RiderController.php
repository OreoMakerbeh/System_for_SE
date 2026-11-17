<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RiderController extends Controller
{
    public function riders(Request $request): View
    {
        return view('admin.riders', [
            'riders' => User::where('role', 'rider')->withCount('assignedDeliveries')->orderBy('name')->paginate(12),
        ]);
    }

    public function storeRider(Request $request): RedirectResponse
    {
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

    public function deleteRider(User $user): RedirectResponse
    {
        DB::transaction(function () use ($user): void {
            $rider = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            abort_unless($rider->role === 'rider', 404);

            if ($rider->assignedDeliveries()->whereNotIn('status', ['delivered', 'cancelled'])->exists()) {
                throw ValidationException::withMessages([
                    'rider' => 'Complete or reassign this rider’s unfinished deliveries before removing their account.',
                ]);
            }

            $rider->delete();
        }, 3);

        return redirect()->route('admin.riders')->with('success', 'Rider account removed. Delivery history was kept.');
    }
}
