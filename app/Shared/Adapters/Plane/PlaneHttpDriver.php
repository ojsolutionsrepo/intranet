<?php

namespace App\Shared\Adapters\Plane;

use App\Shared\Contracts\PlaneAdapter;
use App\Shared\Services\CircuitBreaker;
use Illuminate\Support\Facades\Http;

final class PlaneHttpDriver implements PlaneAdapter
{
    public function __construct(
        private readonly ?string $baseUrl,
        private readonly ?string $apiKey,
        private readonly CircuitBreaker $breaker = new CircuitBreaker('plane'),
    ) {}

    public function name(): string
    {
        return 'plane_http';
    }

    public function configured(): bool
    {
        return filled($this->baseUrl) && filled($this->apiKey);
    }

    public function fetchProjects(): array
    {
        if (! $this->configured()) {
            return [];
        }

        return $this->breaker->call(function () {
            $response = Http::timeout(10)
                ->withToken((string) $this->apiKey)
                ->get(rtrim((string) $this->baseUrl, '/').'/api/v1/projects');

            if (! $response->successful()) {
                throw new \RuntimeException('Plane API error HTTP '.$response->status());
            }

            $rows = $response->json('results') ?? $response->json() ?? [];

            return collect($rows)->map(function (array $row) {
                $ref = (string) ($row['id'] ?? $row['uuid'] ?? '');

                return [
                    'external_ref' => $ref,
                    'name' => (string) ($row['name'] ?? 'Untitled'),
                    'status' => (string) ($row['status'] ?? 'active'),
                    'summary' => $row['description'] ?? null,
                    'rag' => $row['rag'] ?? null,
                    'deep_link' => $this->deepLink($ref),
                    'metrics' => [
                        'progress' => $row['progress'] ?? null,
                    ],
                    'milestones' => [],
                ];
            })->all();
        }, $this->name());
    }

    public function fetchMilestones(string $externalRef): array
    {
        return [];
    }

    public function deepLink(string $externalRef): ?string
    {
        if (! $this->baseUrl || $externalRef === '') {
            return null;
        }

        return rtrim($this->baseUrl, '/').'/projects/'.$externalRef;
    }

    public function health(): array
    {
        if (! $this->configured()) {
            return [
                'ok' => true,
                'driver' => $this->name(),
                'message' => 'Plane not configured — manual/CSV projects still work',
            ];
        }

        try {
            $this->breaker->call(function () {
                $response = Http::timeout(5)
                    ->withToken((string) $this->apiKey)
                    ->get(rtrim((string) $this->baseUrl, '/').'/api/v1/projects');
                if (! $response->successful()) {
                    throw new \RuntimeException('HTTP '.$response->status());
                }

                return true;
            }, $this->name());

            return ['ok' => true, 'driver' => $this->name(), 'message' => 'Plane reachable'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'driver' => $this->name(), 'message' => $e->getMessage()];
        }
    }
}
