<?php

namespace App\Shared\Adapters\Plane;

use App\Shared\Contracts\PlaneAdapter;

/**
 * In-memory / seeded Plane stand-in for local and tests.
 */
final class PlaneFakeDriver implements PlaneAdapter
{
    /**
     * @param  list<array<string, mixed>>  $projects
     */
    public function __construct(
        private array $projects = [],
        private bool $fail = false,
    ) {}

    public function name(): string
    {
        return 'plane_fake';
    }

    public function fail(bool $fail = true): self
    {
        $this->fail = $fail;

        return $this;
    }

    public function setProjects(array $projects): self
    {
        $this->projects = $projects;

        return $this;
    }

    public function fetchProjects(): array
    {
        if ($this->fail) {
            throw new \RuntimeException('Plane unavailable (simulated).');
        }

        return $this->projects;
    }

    public function fetchMilestones(string $externalRef): array
    {
        foreach ($this->projects as $project) {
            if (($project['external_ref'] ?? '') === $externalRef) {
                return $project['milestones'] ?? [];
            }
        }

        return [];
    }

    public function deepLink(string $externalRef): ?string
    {
        return 'https://plane.example/projects/'.$externalRef;
    }

    public function health(): array
    {
        return [
            'ok' => ! $this->fail,
            'driver' => $this->name(),
            'message' => $this->fail ? 'Plane fake is failing' : 'Plane fake healthy',
        ];
    }
}
