<?php

namespace App\Modules\Search\Services;

use App\Models\User;
use App\Modules\Calendar\Models\CalendarEvent;
use App\Modules\Documents\Models\Document;
use App\Modules\News\Models\Post;
use App\Modules\Search\Models\SearchZeroResult;
use App\Shared\Models\Department;
use App\Shared\Services\Analytics;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Meilisearch-ready search with ACL filtering at query time.
 * Uses an in-app index for local/dev when Meilisearch is unavailable.
 */
final class SearchService
{
    public const TYPES = ['posts', 'documents', 'users', 'departments', 'events', 'projects'];

    public function __construct(
        private readonly Analytics $analytics,
    ) {}

    /**
     * @param  array{type?: string|null, department_id?: int|null}  $filters
     * @return array{hits: Collection<int, array<string, mixed>>, facets: array<string, array<string, int>>, took_ms: float}
     */
    public function search(User $user, string $query, array $filters = [], int $limit = 30): array
    {
        $started = hrtime(true);
        $q = trim($query);
        $typeFilter = $filters['type'] ?? null;
        $deptFilter = isset($filters['department_id']) ? (int) $filters['department_id'] : null;

        $hits = collect();

        if ($q !== '') {
            $hits = $hits
                ->merge($this->searchPosts($user, $q))
                ->merge($this->searchDocuments($user, $q))
                ->merge($this->searchUsers($user, $q))
                ->merge($this->searchDepartments($user, $q))
                ->merge($this->searchEvents($user, $q))
                ->merge($this->searchProjects($q));
        }

        if ($typeFilter) {
            $hits = $hits->where('type', $typeFilter)->values();
        }

        if ($deptFilter) {
            $hits = $hits->filter(function (array $hit) use ($deptFilter) {
                $depts = $hit['department_ids'] ?? [];

                return $depts === [] || in_array($deptFilter, $depts, true);
            })->values();
        }

        $hits = $hits->sortByDesc('score')->take($limit)->values();

        $facets = [
            'type' => $hits->groupBy('type')->map->count()->all(),
            'department' => [],
        ];

        $tookMs = (hrtime(true) - $started) / 1e6;

        $this->analytics->track('search.performed', [
            'query' => Str::limit($q, 120),
            'hits' => $hits->count(),
            'took_ms' => round($tookMs, 2),
            'filters' => $filters,
        ], $user->id);

        if ($q !== '' && $hits->isEmpty()) {
            SearchZeroResult::query()->create([
                'user_id' => $user->id,
                'query' => Str::limit($q, 190),
                'filters' => $filters,
            ]);
        }

        return [
            'hits' => $hits,
            'facets' => $facets,
            'took_ms' => $tookMs,
        ];
    }

    /**
     * Typeahead suggestions (< 500ms target).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function suggest(User $user, string $query, int $limit = 8): Collection
    {
        if (strlen(trim($query)) < 2) {
            return collect();
        }

        return $this->search($user, $query, [], $limit)['hits'];
    }

    /**
     * ACL entitlements for the user (for Meilisearch filter strings later).
     *
     * @return list<string>
     */
    public function aclTokens(User $user): array
    {
        $tokens = ['all', 'user:'.$user->id];
        foreach ($user->getRoleNames() as $role) {
            $tokens[] = 'role:'.Str::lower((string) $role);
        }
        foreach ($user->departments()->pluck('departments.id') as $id) {
            $tokens[] = 'dept:'.$id;
        }
        foreach ($user->teams()->pluck('teams.id') as $id) {
            $tokens[] = 'team:'.$id;
        }

        return $tokens;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchPosts(User $user, string $q): Collection
    {
        if (! $user->can('news.view')) {
            return collect();
        }

        return Post::query()
            ->published()
            ->where(function ($query) use ($q): void {
                $query->where('title', 'like', "%{$q}%")
                    ->orWhere('summary', 'like', "%{$q}%")
                    ->orWhere('body_html', 'like', "%{$q}%");
            })
            ->limit(40)
            ->get()
            ->filter(fn (Post $post) => $post->isVisibleTo($user))
            ->map(fn (Post $post) => [
                'type' => 'posts',
                'id' => $post->id,
                'title' => $post->title,
                'subtitle' => $post->summary,
                'url' => route('news.show', $post),
                'score' => $this->score($q, $post->title),
                'department_ids' => array_map('intval', $post->audience['departments'] ?? []),
                'date' => optional($post->published_at)->toDateString(),
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchDocuments(User $user, string $q): Collection
    {
        if (! $user->can('documents.view')) {
            return collect();
        }

        return Document::query()
            ->with(['currentVersion', 'category'])
            ->notTrashed()
            ->where(function ($query) use ($q): void {
                $query->where('title', 'like', "%{$q}%")
                    ->orWhereHas('versions', fn ($v) => $v->where('extracted_text', 'like', "%{$q}%")
                        ->orWhere('original_filename', 'like', "%{$q}%"));
            })
            ->limit(40)
            ->get()
            ->filter(fn (Document $doc) => $doc->isVisibleTo($user))
            ->map(function (Document $doc) use ($q) {
                $audience = $doc->visibility === 'inherit'
                    ? ($doc->category?->audience['departments'] ?? [])
                    : ($doc->audience['departments'] ?? []);

                return [
                    'type' => 'documents',
                    'id' => $doc->id,
                    'title' => $doc->title,
                    'subtitle' => $doc->category?->name,
                    'url' => route('documents.show', $doc),
                    'score' => $this->score($q, $doc->title) + ($doc->currentVersion && str_contains(strtolower((string) $doc->currentVersion->extracted_text), strtolower($q)) ? 5 : 0),
                    'department_ids' => array_map('intval', $audience),
                    'date' => optional($doc->updated_at)->toDateString(),
                ];
            });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchUsers(User $user, string $q): Collection
    {
        if (! $user->can('directory.view')) {
            return collect();
        }

        return User::query()
            ->with(['profile', 'departments'])
            ->where('is_active', true)
            ->where(function ($query) use ($q): void {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhereHas('profile', fn ($p) => $p->where('bio', 'like', "%{$q}%")
                        ->orWhere('location', 'like', "%{$q}%"));
            })
            ->limit(20)
            ->get()
            ->map(fn (User $person) => [
                'type' => 'users',
                'id' => $person->id,
                'title' => $person->name,
                'subtitle' => $person->jobTitle() ?: $person->email,
                'url' => route('directory.show', $person),
                'score' => $this->score($q, $person->name),
                'department_ids' => $person->departments->pluck('id')->map(fn ($id) => (int) $id)->all(),
                'date' => null,
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchDepartments(User $user, string $q): Collection
    {
        if (! $user->can('directory.view')) {
            return collect();
        }

        return Department::query()
            ->where(function ($query) use ($q): void {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            })
            ->limit(10)
            ->get()
            ->map(fn (Department $dept) => [
                'type' => 'departments',
                'id' => $dept->id,
                'title' => $dept->name,
                'subtitle' => $dept->description,
                'url' => route('directory.department', $dept),
                'score' => $this->score($q, $dept->name),
                'department_ids' => [(int) $dept->id],
                'date' => null,
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchEvents(User $user, string $q): Collection
    {
        if (! $user->can('calendar.view')) {
            return collect();
        }

        return CalendarEvent::query()
            ->where(function ($query) use ($q): void {
                $query->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('location', 'like', "%{$q}%");
            })
            ->limit(20)
            ->get()
            ->filter(fn (CalendarEvent $event) => $event->isVisibleTo($user))
            ->map(fn (CalendarEvent $event) => [
                'type' => 'events',
                'id' => $event->id,
                'title' => $event->title,
                'subtitle' => $event->starts_at->format('j M Y H:i'),
                'url' => route('calendar.index', ['focus' => $event->starts_at->toDateString()]),
                'score' => $this->score($q, $event->title),
                'department_ids' => array_map('intval', $event->audience['departments'] ?? []),
                'date' => $event->starts_at->toDateString(),
            ]);
    }

    /**
     * Projects arrive Phase 4 — keep type in the index contract.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function searchProjects(string $q): Collection
    {
        return collect();
    }

    private function score(string $needle, string $haystack): float
    {
        $needle = strtolower($needle);
        $haystack = strtolower($haystack);

        if ($haystack === $needle) {
            return 100;
        }
        if (str_starts_with($haystack, $needle)) {
            return 80;
        }
        if (str_contains($haystack, $needle)) {
            return 60;
        }

        return 20;
    }
}
