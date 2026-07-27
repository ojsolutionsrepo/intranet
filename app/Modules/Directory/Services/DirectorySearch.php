<?php

namespace App\Modules\Directory\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class DirectorySearch
{
    /**
     * @param  array{q?: string, department_id?: int|string|null, role?: string|null, expertise?: string|null}  $filters
     * @return LengthAwarePaginator<int, User>
     */
    public function search(array $filters, int $perPage = 24): LengthAwarePaginator
    {
        $query = User::query()
            ->with(['profile', 'departments', 'teams', 'roles'])
            ->where('is_active', true)
            ->orderBy('name');

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $query->where(function (Builder $builder) use ($q): void {
                $builder->where('name', 'like', '%'.$q.'%')
                    ->orWhere('email', 'like', '%'.$q.'%')
                    ->orWhereHas('profile', function (Builder $profile) use ($q): void {
                        $profile->where('bio', 'like', '%'.$q.'%')
                            ->orWhere('location', 'like', '%'.$q.'%')
                            ->orWhere('phone', 'like', '%'.$q.'%');
                    });
            });
        }

        if (! empty($filters['department_id'])) {
            $query->whereHas('departments', fn (Builder $d) => $d->where('departments.id', $filters['department_id']));
        }

        if (! empty($filters['role'])) {
            $query->role($filters['role']);
        }

        if (! empty($filters['expertise'])) {
            $tag = strtolower((string) $filters['expertise']);
            $query->whereHas('profile', function (Builder $profile) use ($tag): void {
                $profile->whereNotNull('expertise')
                    ->where(function (Builder $inner) use ($tag): void {
                        $inner->where('expertise', 'like', '%"'.$tag.'"%')
                            ->orWhere('expertise', 'like', '%'.ucfirst($tag).'%')
                            ->orWhere('expertise', 'like', '%'.$tag.'%');
                    });
            });
        }

        $results = $query->paginate($perPage)->withQueryString();

        // Typo tolerance for short result sets: if exact search empty and q length >= 3, soften match.
        if ($results->total() === 0 && strlen($q) >= 3) {
            $soft = User::query()
                ->with(['profile', 'departments', 'teams', 'roles'])
                ->where('is_active', true)
                ->get()
                ->filter(fn (User $user) => $this->fuzzyMatch($user->name, $q))
                ->values();

            return new \Illuminate\Pagination\LengthAwarePaginator(
                $soft->forPage(1, $perPage)->values(),
                $soft->count(),
                $perPage,
                1,
                ['path' => request()->url(), 'query' => request()->query()],
            );
        }

        return $results;
    }

    public function fuzzyMatch(string $name, string $needle): bool
    {
        $name = strtolower($name);
        $needle = strtolower($needle);

        if (str_contains($name, $needle)) {
            return true;
        }

        foreach (preg_split('/\s+/', $name) ?: [] as $part) {
            if ($part !== '' && levenshtein($part, $needle) <= 1) {
                return true;
            }
        }

        return levenshtein($name, $needle) <= 2 && strlen($needle) >= 4;
    }

    /**
     * @return Collection<int, string>
     */
    public function allExpertiseTags(): Collection
    {
        return User::query()
            ->with('profile')
            ->whereHas('profile')
            ->get()
            ->flatMap(fn (User $u) => $u->expertiseTags())
            ->map(fn ($t) => (string) $t)
            ->unique()
            ->sort()
            ->values();
    }
}
