<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(Request $request): Response
    {
        $this->rememberSafeIntendedUrl($request);

        return Inertia::render('Auth/Register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'referral_code' => ['nullable', 'string', 'max:100'],
            'marketing_opt_in' => ['boolean'],
        ]);

        $name = trim($validated['first_name'] . ' ' . ($validated['last_name'] ?? ''));

        $user = User::query()->create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'] ?? null,
            'name' => $name,
            'email' => $validated['email'],
            'password' => $validated['password'],
            'referral_code' => $validated['referral_code'] ?? null,
            'marketing_opt_in' => (bool) ($validated['marketing_opt_in'] ?? false),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->intended(route('home'));
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
