<?php

namespace App\Modules\Projects\Services;

use App\Models\User;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectMilestone;
use App\Shared\Contracts\GovernexAdapter;
use App\Shared\Contracts\PlaneAdapter;
use App\Shared\Services\AudienceResolver;
use App\Shared\Services\AuditLogger;
use App\Shared\Services\CircuitBreaker;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

final class ProjectSyncService
{
    public function __construct(
        private readonly PlaneAdapter $plane,
        private readonly GovernexAdapter $governex,
        private readonly AudienceResolver $audience,
        private readonly AuditLogger $audit,
    ) {}

    public function visibleFor(User $user, int $perPage = 20): LengthAwarePaginator
    {
        $projects = Project::query()
            ->where('status', '!=', 'archived')
            ->orderBy('name')
            ->get()
            ->filter(fn (Project $p) => $p->isVisibleTo($user))
            ->values();

        $page = max(1, (int) request('page', 1));
        $slice = $projects->forPage($page, $perPage)->values();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $slice,
            $projects->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()],
        );
    }

    /**
     * @return Collection<int, Project>
     */
    public function forWidget(User $user, int $limit = 5): Collection
    {
        return Project::query()
            ->where('status', '!=', 'archived')
            ->orderByDesc('synced_at')
            ->orderBy('name')
            ->get()
            ->filter(fn (Project $p) => $p->isVisibleTo($user))
            ->take($limit)
            ->values();
    }

    /**
     * Sync Plane + Governex into the local projects mirror.
     *
     * @return array{plane: int, governex: int, errors: list<string>}
     */
    public function syncAll(): array
    {
        $result = ['plane' => 0, 'governex' => 0, 'errors' => []];

        try {
            $result['plane'] = $this->upsertFromSource('plane', $this->plane->fetchProjects());
            (new CircuitBreaker('plane'))->recordSync($this->plane->name(), 'Plane sync OK');
        } catch (Throwable $e) {
            $result['errors'][] = 'plane: '.$e->getMessage();
            (new CircuitBreaker('plane'))->markDown($this->plane->name(), $e->getMessage());
        }

        try {
            $result['governex'] = $this->upsertFromSource('governex', $this->governex->fetchProjects());
            (new CircuitBreaker('governex'))->recordSync($this->governex->name(), 'Governex sync OK');
        } catch (Throwable $e) {
            $result['errors'][] = 'governex: '.$e->getMessage();
            (new CircuitBreaker('governex'))->markDown($this->governex->name(), $e->getMessage());
        }

        $this->audit->log('integrations.sync', null, null, [
            'plane' => $result['plane'],
            'governex' => $result['governex'],
            'errors' => $result['errors'],
        ]);

        return $result;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function upsertFromSource(string $source, array $rows): int
    {
        $count = 0;

        foreach ($rows as $row) {
            $ref = (string) ($row['external_ref'] ?? '');
            if ($ref === '') {
                continue;
            }

            $project = Project::withTrashed()->firstOrNew([
                'source' => $source,
                'external_ref' => $ref,
            ]);

            if ($project->trashed()) {
                $project->restore();
            }

            $project->fill([
                'name' => (string) ($row['name'] ?? 'Untitled'),
                'status' => (string) ($row['status'] ?? 'active'),
                'summary' => $row['summary'] ?? null,
                'rag' => $row['rag'] ?? null,
                'deep_link' => $row['deep_link'] ?? null,
                'metrics' => $row['metrics'] ?? [],
                'audience' => $this->audience->normalize($row['audience'] ?? []),
                'synced_at' => now(),
            ]);

            if (! $project->exists || blank($project->slug)) {
                $project->slug = Str::slug((string) ($row['name'] ?? 'project')).'-'.Str::lower(Str::substr(md5($source.$ref), 0, 6));
            }

            $project->save();

            $milestones = $row['milestones'] ?? [];
            if (is_array($milestones)) {
                $project->milestones()->delete();
                foreach (array_values($milestones) as $i => $ms) {
                    ProjectMilestone::query()->create([
                        'project_id' => $project->id,
                        'title' => (string) ($ms['title'] ?? 'Milestone'),
                        'due_on' => $ms['due_on'] ?? null,
                        'status' => (string) ($ms['status'] ?? 'planned'),
                        'order' => $i,
                    ]);
                }
            }

            $count++;
        }

        return $count;
    }
}
