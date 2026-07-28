<?php

namespace App\Shared\Adapters\Governex;

use App\Shared\Contracts\GovernexAdapter;
use App\Shared\Services\CircuitBreaker;
use Illuminate\Support\Facades\Http;

final class GovernexApiDriver implements GovernexAdapter
{
    public function __construct(
        private readonly ?string $baseUrl,
        private readonly ?string $apiKey,
        private readonly CircuitBreaker $breaker = new CircuitBreaker('governex'),
    ) {}

    public function name(): string
    {
        return 'governex_api';
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
                ->get(rtrim((string) $this->baseUrl, '/').'/projects');

            if (! $response->successful()) {
                throw new \RuntimeException('Governex API error HTTP '.$response->status());
            }

            return collect($response->json() ?? [])->map(fn (array $row) => [
                'external_ref' => (string) ($row['id'] ?? ''),
                'name' => (string) ($row['name'] ?? 'Untitled'),
                'status' => (string) ($row['status'] ?? 'active'),
                'summary' => $row['summary'] ?? null,
                'rag' => $row['rag'] ?? null,
                'deep_link' => $row['url'] ?? null,
                'metrics' => $row['metrics'] ?? [],
                'milestones' => $row['milestones'] ?? [],
            ])->all();
        }, $this->name());
    }

    public function health(): array
    {
        if (! $this->configured()) {
            return [
                'ok' => true,
                'driver' => $this->name(),
                'message' => 'Governex API not configured — CSV fallback available',
            ];
        }

        try {
            $this->fetchProjects();

            return ['ok' => true, 'driver' => $this->name(), 'message' => 'Governex API reachable'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'driver' => $this->name(), 'message' => $e->getMessage()];
        }
    }
}
