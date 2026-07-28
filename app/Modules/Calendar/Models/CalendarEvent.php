<?php

namespace App\Modules\Calendar\Models;

use App\Models\User;
use App\Shared\Services\AudienceResolver;
use App\Shared\Support\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CalendarEvent extends Model
{
    use Auditable, SoftDeletes;

    protected $fillable = [
        'created_by',
        'title',
        'slug',
        'description',
        'category',
        'colour',
        'starts_at',
        'ends_at',
        'all_day',
        'location',
        'audience',
        'rrule',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'all_day' => 'boolean',
            'audience' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (CalendarEvent $event): void {
            if (blank($event->slug)) {
                $event->slug = Str::slug($event->title).'-'.Str::lower(Str::random(5));
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(EventAttendee::class);
    }

    public function isVisibleTo(User $user): bool
    {
        return app(AudienceResolver::class)->allows($this->audience, $user);
    }
}
