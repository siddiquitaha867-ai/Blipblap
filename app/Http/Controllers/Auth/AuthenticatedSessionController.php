<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(Request $request): Response
    {
        $this->rememberSafeIntendedUrl($request);

        return Inertia::render('Auth/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('These credentials do not match our records.'),
            ]);
        }

        $request->session()->regenerate();

        $request->user()?->forceFill([
            'last_login_at' => now(),
        ])->save();

        $fallbackRoute = $request->user()?->is_admin
            ? route('admin.dashboard')
            : route('home');

        return redirect()->intended($fallbackRoute);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function rememberSafeIntendedUrl(Request $request): void
    {
        $redirect = (string) $request->query('redirect', '');

        if ($redirect === '' || str_starts_with($redirect, '//')) {
            return;
        }

        if (str_starts_with($redirect, '/')) {
            $request->session()->put('url.intended', url($redirect));

            return;
        }

        if (str_starts_with($redirect, url('/'))) {
            $request->session()->put('url.intended', $redirect);
        }
    }
}
