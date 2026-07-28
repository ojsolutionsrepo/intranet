<?php

namespace App\Modules\Projects\Models;

use App\Models\User;
use App\Shared\Services\AudienceResolver;
use App\Shared\Support\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Project extends Model
{
    use Auditable, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'external_ref',
        'source',
        'status',
        'summary',
        'rag',
        'deep_link',
        'audience',
        'metrics',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'audience' => 'array',
            'metrics' => 'array',
            'synced_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Project $project): void {
            if (blank($project->slug)) {
                $project->slug = Str::slug($project->name).'-'.Str::lower(Str::random(5));
            }
        });
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class)->orderBy('order');
    }

    public function isVisibleTo(User $user): bool
    {
        return app(AudienceResolver::class)->allows($this->audience, $user);
    }

    public function isStale(): bool
    {
        if (! in_array($this->source, ['plane', 'governex'], true)) {
            return false;
        }

        $minutes = (int) config('integrations.projects.staleness_minutes', 60);
        if ($this->synced_at === null) {
            return true;
        }

        return $this->synced_at->lt(now()->subMinutes($minutes));
    }

    public function ragBadge(): string
    {
        return match (strtolower((string) $this->rag)) {
            'green', 'g' => 'ok',
            'amber', 'a', 'yellow' => 'warn',
            'red', 'r' => 'err',
            default => 'info',
        };
    }
}
