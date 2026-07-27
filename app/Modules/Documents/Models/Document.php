<?php

namespace App\Modules\Documents\Models;

use App\Models\User;
use App\Shared\Services\AudienceResolver;
use App\Shared\Support\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Document extends Model
{
    use Auditable, SoftDeletes;

    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'owner_id',
        'visibility',
        'audience',
        'is_policy',
        'mandatory_ack',
        'review_at',
        'current_version_id',
        'trashed_at',
    ];

    protected function casts(): array
    {
        return [
            'audience' => 'array',
            'is_policy' => 'boolean',
            'mandatory_ack' => 'boolean',
            'review_at' => 'date',
            'trashed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Document $document): void {
            if (blank($document->slug)) {
                $document->slug = Str::slug($document->title).'-'.Str::lower(Str::random(4));
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'category_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'current_version_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class)->orderByDesc('version_number');
    }

    public function acknowledgements(): HasMany
    {
        return $this->hasMany(DocumentAcknowledgement::class);
    }

    public function scopeNotTrashed(Builder $query): Builder
    {
        return $query->whereNull('trashed_at');
    }

    public function scopePolicies(Builder $query): Builder
    {
        return $query->where('is_policy', true);
    }

    public function isVisibleTo(User $user): bool
    {
        if ($user->can('documents.manage') || $this->owner_id === $user->id) {
            return true;
        }

        if ($this->trashed_at !== null && ! $user->can('documents.manage')) {
            return false;
        }

        $visibility = $this->visibility === 'inherit'
            ? ($this->category?->visibility ?? 'all')
            : $this->visibility;

        $audience = $this->visibility === 'inherit'
            ? ($this->category?->audience)
            : $this->audience;

        if ($visibility === 'all' || blank($visibility)) {
            return $this->category?->isVisibleTo($user) ?? true;
        }

        return app(AudienceResolver::class)->allows($audience, $user);
    }

    public function reviewStatus(): string
    {
        if (! $this->review_at) {
            return 'current';
        }

        if ($this->review_at->isPast()) {
            return 'overdue';
        }

        if ($this->review_at->lte(now()->addDays(30))) {
            return 'due';
        }

        return 'current';
    }
}
