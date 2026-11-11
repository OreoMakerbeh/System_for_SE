<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AdministratorController extends Controller
{
    public function administrators(Request $request): View
    {
        return view('admin.administrators', [
            'administrators' => User::where('role', 'admin')->orderBy('name')->paginate(12),
        ]);
    }

    public function storeAdministrator(Request $request): RedirectResponse
    {
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
}
