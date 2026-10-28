<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(Request $request): View
    {
        $photoOptions = [
            'https://images.unsplash.com/photo-1602143407151-7111542de6e8?auto=format&fit=crop&w=360&h=240&q=75',
            'https://images.unsplash.com/photo-1500534623283-312aade485b7?auto=format&fit=crop&w=360&h=240&q=75',
            'https://images.unsplash.com/photo-1470770841072-f978cf4d019e?auto=format&fit=crop&w=360&h=240&q=75',
            'https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=360&h=240&q=75',
            'https://images.unsplash.com/photo-1501004318641-b39e6451bec6?auto=format&fit=crop&w=360&h=240&q=75',
            'https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?auto=format&fit=crop&w=360&h=240&q=75',
            'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=360&h=240&q=75',
            'https://images.unsplash.com/photo-1517849845537-4d257902454a?auto=format&fit=crop&w=360&h=240&q=75',
            'https://images.unsplash.com/photo-1490645935967-10de6ba17061?auto=format&fit=crop&w=360&h=240&q=75',
        ];

        $previousTarget = $request->session()->get('login_captcha_target');
        $availableTargets = array_values(array_filter(
            $photoOptions,
            static fn (string $photoUrl): bool => $photoUrl !== $previousTarget,
        ));
        $targetUrl = $availableTargets[array_rand($availableTargets)];
        shuffle($photoOptions);

        $captchaChoices = [];
        $correctChoices = [];

        foreach ($photoOptions as $photoUrl) {
            $token = bin2hex(random_bytes(16));
            $captchaChoices[] = [
                'token' => $token,
                'url' => $photoUrl,
            ];

            if ($photoUrl === $targetUrl) {
                $correctChoices[] = $token;
            }
        }

        $request->session()->put('login_captcha_target', $targetUrl);
        $request->session()->put('login_captcha_correct_choices', $correctChoices);
        $request->session()->forget('login_captcha_verified');

        return view('auth.login', [
            'captchaChoices' => $captchaChoices,
            'captchaTarget' => $targetUrl,
        ]);
    }

    public function verifyLoginCaptcha(Request $request): JsonResponse
    {
        $data = $request->validate([
            'captcha_choices' => ['required', 'array', 'min:1', 'max:9'],
            'captcha_choices.*' => ['required', 'string', 'size:32'],
        ]);

        $expectedChoices = $request->session()->get('login_captcha_correct_choices');
        $submittedChoices = array_values(array_unique($data['captcha_choices']));

        if (! is_array($expectedChoices)) {
            return response()->json(['message' => 'This challenge has expired. Reload the page and try again.'], 422);
        }

        sort($expectedChoices);
        sort($submittedChoices);

        if ($expectedChoices !== $submittedChoices) {
            return response()->json(['message' => 'That selection does not match. Try again.'], 422);
        }

        $request->session()->forget('login_captcha_correct_choices');
        $request->session()->put('login_captcha_verified', true);

        return response()->json(['message' => 'Verification complete.']);
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function login(Request $request): RedirectResponse
    {
        if (! $request->session()->pull('login_captcha_verified', false)) {
            return back()->withErrors(['captcha' => 'Complete the human check before signing in.'])->onlyInput('email', 'role');
        }

        $credentials = $request->validate([
            'role' => ['required', 'string', 'in:customer,rider,admin'],
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])
            ->where('role', $credentials['role'])
            ->first();

        if (! $user) {
            $phoneMatches = User::where('phone', $credentials['email'])
                ->where('role', $credentials['role'])
                ->limit(2)
                ->get();
            $user = $phoneMatches->count() === 1 ? $phoneMatches->first() : null;
        }

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors(['email' => 'Those details do not match an account.'])->onlyInput('email', 'role');
        }

        if (! $user->is_approved) {
            return back()->withErrors(['email' => 'Your account is awaiting administrator approval.'])->onlyInput('email', 'role');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
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

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function showChangePassword(): View
    {
        return view('auth.change-password');
    }

    public function changePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(8)],
        ]);

        $request->user()->password = $data['password'];
        $request->user()->save();
        $request->session()->regenerate();

        return redirect()->route('password.edit')->with('success', 'Your password has been changed.');
    }
}