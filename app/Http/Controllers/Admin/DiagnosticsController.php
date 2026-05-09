<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EsimPlan;
use Illuminate\Foundation\Application;
use App\Services\EsimGo\CatalogueMapper;
use App\Services\EsimGo\EsimGoClient;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
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
                'public_outbound_ip' => $this->publicOutboundIp(),
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

    public function testEsimApi(): JsonResponse
    {
        if ((string) config('esim-go.api_key', '') === '') {
            return response()->json([
                'ok' => false,
                'message' => 'eSIM Go API key is missing.',
            ], 422);
        }

        try {
            $response = Http::baseUrl((string) config('esim-go.base_url'))
                ->timeout((int) config('esim-go.timeout'))
                ->acceptJson()
                ->withHeaders([
                    'X-API-Key' => (string) config('esim-go.api_key'),
                ])
                ->get('/organisation/groups');
        } catch (\Throwable $exception) {
            return response()->json([
                'ok' => false,
                'message' => 'Connection failed before eSIM Go responded.',
                'error' => $exception->getMessage(),
            ], 502);
        }

        return response()->json([
            'ok' => $response->successful(),
            'status' => $response->status(),
            'message' => $response->successful()
                ? 'Connected to eSIM Go successfully.'
                : 'eSIM Go returned an error.',
            'provider_response' => $response->json() ?? $response->body(),
        ], $response->successful() ? 200 : 502);
    }

    public function syncCatalogue(EsimGoClient $client, CatalogueMapper $mapper): JsonResponse
    {
        try {
            $response = $client->catalogue([
                'page' => 1,
                'perPage' => 100,
            ]);
        } catch (RequestException $exception) {
            return response()->json([
                'ok' => false,
                'message' => 'Catalogue sync failed.',
                'status' => $exception->response?->status(),
                'provider_response' => $exception->response?->json() ?? $exception->response?->body(),
            ], 502);
        }

        $items = $response['bundles'] ?? $response['items'] ?? $response['data'] ?? [];
        $synced = 0;
        $skipped = 0;

        foreach ($items as $item) {
            if (! is_array($item)) {
                $skipped++;
                continue;
            }

            $supplierCode = (string) ($item['name'] ?? $item['bundle'] ?? $item['id'] ?? '');

            if ($supplierCode === '') {
                $skipped++;
                continue;
            }

            EsimPlan::query()->updateOrCreate(
                ['supplier_code' => $supplierCode],
                $mapper->map($item)
            );

            $synced++;
        }

        return response()->json([
            'ok' => true,
            'message' => 'Catalogue sync completed.',
            'synced' => $synced,
            'skipped' => $skipped,
            'source_count' => is_countable($items) ? count($items) : 0,
            'local_plan_count' => EsimPlan::query()->count(),
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

    private function publicOutboundIp(): ?string
    {
        try {
            $response = Http::timeout(4)->acceptJson()->get('https://api.ipify.org', [
                'format' => 'json',
            ]);

            if (! $response->successful()) {
                return null;
            }

            $ip = $response->json('ip');

            return is_string($ip) && $ip !== '' ? $ip : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
