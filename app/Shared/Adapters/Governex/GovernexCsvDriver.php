<?php

namespace App\Shared\Adapters\Governex;

use App\Shared\Contracts\GovernexAdapter;
use App\Shared\Services\CircuitBreaker;

/**
 * CSV import driver — same contract as the API.
 */
final class GovernexCsvDriver implements GovernexAdapter
{
    public function __construct(
        private readonly ?string $path = null,
        private readonly CircuitBreaker $breaker = new CircuitBreaker('governex'),
    ) {}

    public function name(): string
    {
        return 'governex_csv';
    }

    public function fetchProjects(): array
    {
        return $this->breaker->call(function () {
            $path = $this->path ?: storage_path('app/integrations/governex.csv');
            if (! is_file($path)) {
                return [];
            }

            $handle = fopen($path, 'r');
            if ($handle === false) {
                throw new \RuntimeException('Could not read Governex CSV.');
            }

            $headers = null;
            $rows = [];
            while (($data = fgetcsv($handle)) !== false) {
                if ($headers === null) {
                    $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $data);

                    continue;
                }
                $row = [];
                foreach ($headers as $i => $header) {
                    $row[$header] = $data[$i] ?? null;
                }
                if (blank($row['external_ref'] ?? $row['id'] ?? null)) {
                    continue;
                }
                $rows[] = [
                    'external_ref' => (string) ($row['external_ref'] ?? $row['id']),
                    'name' => (string) ($row['name'] ?? 'Untitled'),
                    'status' => (string) ($row['status'] ?? 'active'),
                    'summary' => $row['summary'] ?? null,
                    'rag' => $row['rag'] ?? null,
                    'deep_link' => $row['deep_link'] ?? null,
                    'metrics' => [
                        'budget' => $row['budget'] ?? null,
                    ],
                    'milestones' => [],
                ];
            }
            fclose($handle);

            return $rows;
        }, $this->name());
    }

    public function health(): array
    {
        $path = $this->path ?: storage_path('app/integrations/governex.csv');
        $exists = is_file($path);

        return [
            'ok' => true,
            'driver' => $this->name(),
            'message' => $exists ? 'Governex CSV present' : 'Governex CSV not present (optional)',
        ];
    }
}
