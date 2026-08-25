<?php

namespace App\Http\Middleware;

use App\Models\CustomerEsim;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'email_verified_at' => $request->user()->email_verified_at,
                    'is_admin' => (bool) $request->user()->is_admin,
                    'is_banned' => (bool) $request->user()->is_banned,
                    'customer_esims_count' => CustomerEsim::query()
                        ->where(function ($query) use ($request): void {
                            $query->where('user_id', $request->user()->id)
                                ->orWhere('customer_email', $request->user()->email);
                        })
                        ->count(),
                ] : null,
            ],
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
            ],
            'analytics' => [
                'googleAnalytics' => [
                    'measurementId' => config('services.google_analytics.measurement_id'),
                ],
            ],
        ];
    }
}
