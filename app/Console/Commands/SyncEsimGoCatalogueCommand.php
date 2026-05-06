<?php

namespace App\Console\Commands;

use App\Models\EsimPlan;
use App\Services\EsimGo\CatalogueMapper;
use App\Services\EsimGo\EsimGoClient;
use Illuminate\Console\Command;
use Illuminate\Http\Client\RequestException;
use Throwable;

class SyncEsimGoCatalogueCommand extends Command
{
    protected $signature = 'blipblap:sync-catalogue
        {--per-page=100 : Bundles to fetch per API page}
        {--max-pages= : Stop after this many pages}
        {--start-page=1 : API page to start from}';

    protected $description = 'Sync eSIM Go catalogue bundles into local eSIM plans.';

    public function handle(EsimGoClient $client, CatalogueMapper $mapper): int
    {
        $perPage = max(1, min(100, (int) $this->option('per-page')));
        $page = max(1, (int) $this->option('start-page'));
        $maxPages = $this->option('max-pages') !== null ? max(1, (int) $this->option('max-pages')) : null;

        $totalSynced = 0;
        $totalFailed = 0;
        $pagesFetched = 0;
        $pageCount = null;

        while (true) {
            if ($maxPages !== null && $pagesFetched >= $maxPages) {
                break;
            }

            try {
                $response = $client->catalogue([
                    'page' => $page,
                    'perPage' => $perPage,
                ]);
            } catch (RequestException $exception) {
                $status = $exception->response?->status() ?? 'unknown';
                $body = $exception->response?->json() ?? $exception->response?->body();

                $this->error("API failed on page {$page}. Status: {$status}");
                $this->line(is_string($body) ? $body : json_encode($body));

                return self::FAILURE;
            }

            $items = $response['bundles'] ?? $response['items'] ?? $response['data'] ?? [];
            $pageCount = isset($response['pageCount']) ? (int) $response['pageCount'] : $pageCount;
            $sourceCount = is_countable($items) ? count($items) : 0;

            if ($sourceCount === 0) {
                $this->line("Page {$page}: no bundles returned, stopping.");
                break;
            }

            foreach ($items as $item) {
                if (! is_array($item)) {
                    $totalFailed++;
                    continue;
                }

                $supplierCode = (string) ($item['name'] ?? $item['bundle'] ?? $item['id'] ?? '');

                if ($supplierCode === '') {
                    $totalFailed++;
                    continue;
                }

                try {
                    EsimPlan::query()->updateOrCreate(
                        ['supplier_code' => $supplierCode],
                        $mapper->map($item)
                    );

                    $totalSynced++;
                } catch (Throwable $exception) {
                    $totalFailed++;
                    $this->warn("Skipped {$supplierCode}: {$exception->getMessage()}");
                }
            }

            $pagesFetched++;
            $this->line("Page {$page}: {$sourceCount} bundles fetched, {$totalSynced} synced so far.");

            if ($pageCount !== null && $page >= $pageCount) {
                break;
            }

            if ($sourceCount < $perPage) {
                break;
            }

            $page++;
        }

        $this->newLine();
        $this->info("Sync complete. Pages: {$pagesFetched}. Synced: {$totalSynced}. Failed: {$totalFailed}. Local plans: " . EsimPlan::query()->count() . '.');

        return $totalFailed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
