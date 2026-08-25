<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EsimPlan;
use Illuminate\Foundation\Application;
use App\Services\EsimGo\CatalogueMapper;
use App\Services\EsimGo\EsimGoClient;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class DiagnosticsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $apiKey = (string) config('esim-go.api_key', '');

        return Inertia::render('Admin/Diagnostics', [
            'csrfToken' => csrf_token(),
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
            'database' => $this->databaseHealth(),
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
        set_time_limit(300);

        $perPage = 100;
        $page = 1;
        $pageCount = null;
        $pagesFetched = 0;
        $synced = 0;
        $skipped = 0;
        $sourceCount = 0;

        while (true) {
            try {
                $response = $client->catalogue([
                    'page' => $page,
                    'perPage' => $perPage,
                ]);
            } catch (RequestException $exception) {
                return response()->json([
                    'ok' => false,
                    'message' => "Catalogue sync failed on page {$page}.",
                    'status' => $exception->response?->status(),
                    'pages_fetched' => $pagesFetched,
                    'synced' => $synced,
                    'skipped' => $skipped,
                    'provider_response' => $exception->response?->json() ?? $exception->response?->body(),
                ], 502);
            }

            $items = $response['bundles'] ?? $response['items'] ?? $response['data'] ?? [];
            $pageCount = isset($response['pageCount']) ? (int) $response['pageCount'] : $pageCount;
            $pageSourceCount = is_countable($items) ? count($items) : 0;
            $sourceCount += $pageSourceCount;

            if ($pageSourceCount === 0) {
                break;
            }

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

                try {
                    EsimPlan::query()->updateOrCreate(
                        ['supplier_code' => $supplierCode],
                        $mapper->map($item)
                    );

                    $synced++;
                } catch (Throwable) {
                    $skipped++;
                }
            }

            $pagesFetched++;

            if ($pageCount !== null && $page >= $pageCount) {
                break;
            }

            if ($pageSourceCount < $perPage) {
                break;
            }

            $page++;
        }

        return response()->json([
            'ok' => true,
            'message' => 'Full catalogue sync completed.',
            'pages_fetched' => $pagesFetched,
            'synced' => $synced,
            'skipped' => $skipped,
            'source_count' => $sourceCount,
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

    private function databaseHealth(): array
    {
        $requiredTables = [
            'users',
            'password_reset_tokens',
            'sessions',
            'esim_plans',
            'esim_orders',
            'customer_esims',
            'api_logs',
            'esim_events',
            'loyalty_events',
            'loyalty_balances',
            'campaign_events',
            'promotion_rules',
            'site_contents',
            'content_pages',
            'contact_requests',
        ];

        $requiredColumns = [
            'users' => ['id', 'name', 'email', 'password', 'is_admin', 'is_banned', 'email_verified_at', 'last_login_at', 'phone_number', 'country'],
            'esim_plans' => ['id', 'supplier_code', 'country_name', 'retail_price', 'currency', 'tax_amount', 'is_active'],
            'contact_requests' => ['id', 'name', 'email', 'topic', 'order_reference', 'status', 'message', 'metadata', 'resolved_at'],
        ];

        try {
            DB::connection()->getPdo();

            $missingTables = array_values(array_filter(
                $requiredTables,
                fn (string $table): bool => ! Schema::hasTable($table)
            ));

            $columnIssues = [];
            foreach ($requiredColumns as $table => $columns) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                $existingColumns = Schema::getColumnListing($table);
                $missingColumns = array_values(array_diff($columns, $existingColumns));

                if ($missingColumns !== []) {
                    $columnIssues[] = [
                        'table' => $table,
                        'missing_columns' => $missingColumns,
                    ];
                }
            }

            $pendingMigrations = $this->pendingMigrations();

            return [
                'ok' => $missingTables === [] && $columnIssues === [] && $pendingMigrations === [],
                'connection' => (string) config('database.default'),
                'database' => DB::connection()->getDatabaseName(),
                'missing_tables' => $missingTables,
                'column_issues' => $columnIssues,
                'pending_migrations' => $pendingMigrations,
            ];
        } catch (Throwable $exception) {
            return [
                'ok' => false,
                'connection' => (string) config('database.default'),
                'database' => null,
                'missing_tables' => $requiredTables,
                'column_issues' => [],
                'pending_migrations' => [],
                'error' => $exception->getMessage(),
            ];
        }
    }

    private function pendingMigrations(): array
    {
        $migrationFiles = glob(database_path('migrations/*.php')) ?: [];
        $available = array_map(
            fn (string $path): string => pathinfo($path, PATHINFO_FILENAME),
            $migrationFiles
        );

        if (! Schema::hasTable('migrations')) {
            return array_values($available);
        }

        $ran = DB::table('migrations')->pluck('migration')->all();

        return array_values(array_diff($available, $ran));
    }
}
