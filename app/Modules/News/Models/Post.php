<?php

namespace App\Modules\News\Models;

use App\Models\User;
use App\Shared\Services\AudienceResolver;
use App\Shared\Support\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Post extends Model
{
    use Auditable, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_IN_REVIEW = 'in_review';

    public const STATUS_PUBLISHED = 'published';

    protected $fillable = [
        'author_id',
        'title',
        'slug',
        'summary',
        'body_html',
        'category',
        'status',
        'is_pinned',
        'is_alert',
        'audience',
        'published_at',
        'scheduled_at',
    ];

    protected function casts(): array
    {
        return [
            'audience' => 'array',
            'is_pinned' => 'boolean',
            'is_alert' => 'boolean',
            'published_at' => 'datetime',
            'scheduled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Post $post): void {
            if (blank($post->slug)) {
                $post->slug = Str::slug($post->title).'-'.Str::lower(Str::random(5));
            }
        });
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(PostRead::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->where(function (Builder $q): void {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    public function isVisibleTo(User $user): bool
    {
        return app(AudienceResolver::class)->allows($this->audience, $user);
    }
}
