<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'middle_name' => ['nullable', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:40', 'unique:users,phone'],
            'address' => ['required', 'string', 'max:1000'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = new User($data);
        $user->is_approved = false;
        $user->save();

        return redirect()->route('login')->with('success', 'Your registration was submitted. You can sign in after an administrator approves your account.');
    }
}
