<?php

namespace App\Modules\Calendar\Services;

use App\Models\User;
use App\Modules\Calendar\Models\CalendarEvent;
use App\Shared\Services\AudienceResolver;
use App\Shared\Services\AuditLogger;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class CalendarService
{
    /** @var array<string, string> */
    public const CATEGORY_COLOURS = [
        'General' => '#0ea5e9',
        'Training' => '#16a34a',
        'Deadline' => '#dc2626',
        'Social' => '#7c3aed',
        'Department' => '#ea580c',
    ];

    public function __construct(
        private readonly AudienceResolver $audience,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, array $data): CalendarEvent
    {
        $category = $data['category'] ?? 'General';

        $event = CalendarEvent::query()->create([
            'created_by' => $actor->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'category' => $category,
            'colour' => $data['colour'] ?? (self::CATEGORY_COLOURS[$category] ?? '#0ea5e9'),
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'all_day' => (bool) ($data['all_day'] ?? false),
            'location' => $data['location'] ?? null,
            'audience' => $this->audience->normalize($data['audience'] ?? []),
            'rrule' => $data['rrule'] ?? null,
        ]);

        $this->audit->log('calendar.event_created', $event);

        return $event;
    }

    /**
     * @return Collection<int, CalendarEvent>
     */
    public function eventsFor(User $user, CarbonInterface $from, CarbonInterface $to, ?string $category = null): Collection
    {
        return CalendarEvent::query()
            ->where(function ($q) use ($from, $to): void {
                $q->whereBetween('starts_at', [$from, $to])
                    ->orWhereBetween('ends_at', [$from, $to])
                    ->orWhere(function ($inner) use ($from, $to): void {
                        $inner->where('starts_at', '<=', $from)->where('ends_at', '>=', $to);
                    });
            })
            ->when($category, fn ($q) => $q->where('category', $category))
            ->orderBy('starts_at')
            ->get()
            ->filter(fn (CalendarEvent $event) => $event->isVisibleTo($user))
            ->values();
    }

    /**
     * @return Collection<int, CalendarEvent>
     */
    public function upcomingFor(User $user, int $limit = 5): Collection
    {
        return CalendarEvent::query()
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->limit(50)
            ->get()
            ->filter(fn (CalendarEvent $event) => $event->isVisibleTo($user))
            ->take($limit)
            ->values();
    }

    public function ensureIcsToken(User $user): string
    {
        if (! $user->ics_token) {
            $user->forceFill(['ics_token' => Str::random(40)])->save();
        }

        return (string) $user->ics_token;
    }

    /**
     * @param  Collection<int, CalendarEvent>  $events
     */
    public function toIcs(Collection $events, string $calendarName = 'OJ Intranet'): string
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//OJ Solutions//Intranet//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:'.$calendarName,
        ];

        foreach ($events as $event) {
            $uid = $event->slug.'@oj-intranet';
            $stamp = $event->updated_at?->utc()->format('Ymd\THis\Z') ?? now()->utc()->format('Ymd\THis\Z');
            $start = $event->all_day
                ? $event->starts_at->format('Ymd')
                : $event->starts_at->utc()->format('Ymd\THis\Z');
            $end = $event->all_day
                ? $event->ends_at->copy()->addDay()->format('Ymd')
                : $event->ends_at->utc()->format('Ymd\THis\Z');

            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:'.$uid;
            $lines[] = 'DTSTAMP:'.$stamp;
            $lines[] = ($event->all_day ? 'DTSTART;VALUE=DATE:' : 'DTSTART:').$start;
            $lines[] = ($event->all_day ? 'DTEND;VALUE=DATE:' : 'DTEND:').$end;
            $lines[] = 'SUMMARY:'.$this->escapeIcs((string) $event->title);
            if ($event->description) {
                $lines[] = 'DESCRIPTION:'.$this->escapeIcs((string) $event->description);
            }
            if ($event->location) {
                $lines[] = 'LOCATION:'.$this->escapeIcs((string) $event->location);
            }
            $lines[] = 'CATEGORIES:'.$this->escapeIcs((string) $event->category);
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines)."\r\n";
    }

    private function escapeIcs(string $value): string
    {
        return str_replace(["\r\n", "\n", ',', ';'], ['\\n', '\\n', '\\,', '\\;'], $value);
    }
}
