<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Login admin sederhana, cukup untuk mengaktifkan mode edit (@auth). */
class AuthController extends Controller
{
    private const MAKS_PERCOBAAN = 5; // per menit, per email + IP

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $kredensial = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $kunci = 'login:' . Str::lower($kredensial['email']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($kunci, self::MAKS_PERCOBAAN)) {
            throw ValidationException::withMessages([
                'email' => 'Terlalu banyak percobaan masuk. Coba lagi dalam ' . RateLimiter::availableIn($kunci) . ' detik.',
            ]);
        }

        if (! Auth::attempt($kredensial, $request->boolean('remember'))) {
            RateLimiter::hit($kunci, 60);

            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi salah.',
            ]);
        }

        RateLimiter::clear($kunci);
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
