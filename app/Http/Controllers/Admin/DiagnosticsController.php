<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DiagnosticsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $apiKey = (string) config('esim-go.api_key', '');

        return Inertia::render('Admin/Diagnostics', [
            'app' => [
                'name' => config('app.name'),
                'environment' => config('app.env'),
                'debug' => (bool) config('app.debug'),
                'url' => config('app.url'),
                'timezone' => config('app.timezone'),
                'laravel_version' => Application::VERSION,
                'php_version' => PHP_VERSION,
                'config_cached' => app()->configurationIsCached(),
                'routes_cached' => app()->routesAreCached(),
            ],
            'esim' => [
                'base_url' => config('esim-go.base_url'),
                'api_key_present' => $apiKey !== '',
                'api_key_masked' => $this->maskSecret($apiKey),
                'api_key_fingerprint' => $apiKey === '' ? null : substr(hash('sha256', $apiKey), 0, 12),
                'timeout' => config('esim-go.timeout'),
                'retry_times' => config('esim-go.retry_times'),
                'retry_sleep' => config('esim-go.retry_sleep'),
                'currency' => config('blipblap.currency'),
                'markup_percentage' => config('blipblap.markup_percentage'),
            ],
            'stripe' => [
                'publishable_key_present' => (string) config('services.stripe.key', '') !== '',
                'publishable_key_masked' => $this->maskSecret((string) config('services.stripe.key', '')),
                'secret_key_present' => (string) config('services.stripe.secret', '') !== '',
                'secret_key_masked' => $this->maskSecret((string) config('services.stripe.secret', '')),
                'webhook_secret_present' => (string) config('services.stripe.webhook_secret', '') !== '',
            ],
            'network' => [
                'request_ip' => $request->ip(),
                'client_ips' => $request->ips(),
                'remote_addr' => $request->server('REMOTE_ADDR'),
                'server_addr' => $request->server('SERVER_ADDR') ?: $request->server('LOCAL_ADDR'),
                'server_name' => $request->server('SERVER_NAME'),
                'host' => $request->getHost(),
                'port' => $request->getPort(),
                'scheme' => $request->getScheme(),
                'user_agent' => $request->userAgent(),
            ],
        ]);
    }

    private function maskSecret(string $secret): ?string
    {
        if ($secret === '') {
            return null;
        }

        if (strlen($secret) <= 10) {
            return str_repeat('*', strlen($secret));
        }

        return substr($secret, 0, 6) . str_repeat('*', max(strlen($secret) - 10, 8)) . substr($secret, -4);
    }
}
