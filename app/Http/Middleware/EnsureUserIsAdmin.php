<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse as LaravelRedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->is_admin) {
            if ($request->expectsJson()) {
                abort(403);
            }

            return (new LaravelRedirectResponse('/my-account'))
                ->setSession($request->session())
                ->with('status', 'You need an admin account to access that page.');
        }

        return $next($request);
    }
}
