<?php

namespace App\Modules\Documents\Models;

use App\Models\User;
use App\Shared\Services\AudienceResolver;
use App\Shared\Support\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class DocumentCategory extends Model
{
    use Auditable;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'visibility',
        'audience',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'audience' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (DocumentCategory $category): void {
            if (blank($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('order');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'category_id');
    }

    public function isVisibleTo(User $user): bool
    {
        if ($this->visibility === 'all' || blank($this->visibility)) {
            return true;
        }

        return app(AudienceResolver::class)->allows($this->audience, $user);
    }

    /**
     * @return list<DocumentCategory>
     */
    public function breadcrumbs(): array
    {
        $crumbs = [];
        $node = $this;
        while ($node) {
            array_unshift($crumbs, $node);
            $node = $node->parent;
        }

        return $crumbs;
    }
}
