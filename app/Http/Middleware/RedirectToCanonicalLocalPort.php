<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectToCanonicalLocalPort
{
    public function handle(Request $request, Closure $next): Response
    {
        $appUrl = config('app.url', env('APP_URL'));

        if (! $appUrl) {
            return $next($request);
        }

        $target = parse_url($appUrl);
        $targetHost = $target['host'] ?? null;
        $targetPort = isset($target['port']) ? (int) $target['port'] : null;
        $requestPort = $request->getPort();

        if (
            $targetHost
            && $targetPort
            && $requestPort !== $targetPort
            && $this->isLocalHost($request->getHost())
            && $this->isLocalHost($targetHost)
        ) {
            $scheme = $target['scheme'] ?? $request->getScheme();

            return redirect()->away("{$scheme}://{$targetHost}:{$targetPort}{$request->getRequestUri()}");
        }

        return $next($request);
    }

    private function isLocalHost(string $host): bool
    {
        return in_array($host, ['127.0.0.1', 'localhost', '::1'], true);
    }
}
