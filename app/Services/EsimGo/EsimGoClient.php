<?php

namespace App\Services\EsimGo;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class EsimGoClient
{
    public function catalogue(array $query = []): array
    {
        return $this->get('/catalogue', $query);
    }

    public function bundle(string $name): array
    {
        return $this->get('/catalogue/bundle/' . rawurlencode($name));
    }

    public function inventory(array $query = []): array
    {
        return $this->get('/inventory', $query);
    }

    public function bundleGroups(): array
    {
        return $this->get('/organisation/groups');
    }

    public function organisation(): array
    {
        return $this->get('/organisation');
    }

    public function balance(): array
    {
        return $this->get('/organisation/balance');
    }

    public function networks(array $query = []): array
    {
        return $this->get('/networks', $query);
    }

    public function createOrder(array $payload): array
    {
        return $this->post('/orders', $payload);
    }

    public function order(string $reference): array
    {
        return $this->get('/orders/' . rawurlencode($reference));
    }

    public function applyBundle(array $payload): array
    {
        return $this->post('/esims/apply', $payload);
    }

    public function installDetails(string $reference, array $query = []): array
    {
        return $this->get('/esims/assignments', array_merge(['reference' => $reference], $query));
    }

    public function esim(string $iccid): array
    {
        return $this->get('/esims/' . rawurlencode($iccid));
    }

    public function refreshEsim(string $iccid): array
    {
        return $this->get('/esims/' . rawurlencode($iccid) . '/refresh');
    }

    public function appliedBundles(string $iccid): array
    {
        return $this->get('/esims/' . rawurlencode($iccid) . '/bundles');
    }

    public function appliedBundleStatus(string $iccid, string $bundle): array
    {
        return $this->get('/esims/' . rawurlencode($iccid) . '/bundles/' . rawurlencode($bundle));
    }

    public function compatibility(string $iccid, string $bundle): array
    {
        return $this->get('/esims/' . rawurlencode($iccid) . '/compatible/' . rawurlencode($bundle));
    }

    private function get(string $path, array $query = []): array
    {
        return $this->decode($this->request()->get($path, $query));
    }

    private function post(string $path, array $payload = []): array
    {
        return $this->decode($this->request()->post($path, $payload));
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl((string) config('esim-go.base_url'))
            ->timeout((int) config('esim-go.timeout'))
            ->retry((int) config('esim-go.retry_times'), (int) config('esim-go.retry_sleep'))
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'X-API-Key' => (string) config('esim-go.api_key'),
            ]);
    }

    private function decode(Response $response): array
    {
        $response->throw();

        return $response->json() ?? [];
    }
}
