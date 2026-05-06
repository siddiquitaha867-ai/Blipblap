<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EsimPlan;
use App\Services\EsimGo\CatalogueMapper;
use App\Services\EsimGo\EsimGoClient;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogueSyncController extends Controller
{
    public function __invoke(Request $request, EsimGoClient $client, CatalogueMapper $mapper): JsonResponse
    {
        try {
            $response = $client->catalogue($request->only([
                'page',
                'perPage',
                'direction',
                'orderBy',
                'description',
                'group',
                'countries',
                'region',
            ]));
        } catch (RequestException $exception) {
            return response()->json([
                'message' => 'eSIM Go catalogue sync failed.',
                'status' => $exception->response?->status(),
                'provider_response' => $exception->response?->json() ?? $exception->response?->body(),
            ], 502);
        }

        $items = $response['bundles'] ?? $response['items'] ?? $response['data'] ?? [];
        $synced = 0;

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            EsimPlan::query()->updateOrCreate(
                ['supplier_code' => (string) ($item['name'] ?? $item['bundle'] ?? $item['id'] ?? '')],
                $mapper->map($item)
            );

            $synced++;
        }

        return response()->json([
            'synced' => $synced,
            'source_count' => is_countable($items) ? count($items) : 0,
        ]);
    }
}
