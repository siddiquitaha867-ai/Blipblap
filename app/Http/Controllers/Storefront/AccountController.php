<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\LoyaltyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function show(Request $request, LoyaltyService $loyalty): Response
    {
        $user = $request->user();

        return Inertia::render('Storefront/MyAccount', [
            'profile' => [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'name' => $user->name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'address_line1' => $user->address_line1,
                'town' => $user->town,
                'city' => $user->city,
                'country' => $user->country,
            ],
            'loyalty' => $loyalty->summaryForUser($user),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'town' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
        ]);

        $name = trim($data['first_name'] . ' ' . ($data['last_name'] ?? ''));

        $request->user()->update([
            ...$data,
            'last_name' => $data['last_name'] ?: null,
            'name' => $name,
        ]);

        return back()->with('status', 'Account details updated.');
    }
}
