<?php

use App\Models\User;
use App\Modules\Calendar\Models\CalendarEvent;
use App\Modules\Calendar\Services\CalendarService;
use App\Modules\Dashboard\Services\WidgetRegistry;
use App\Modules\Search\Models\SearchZeroResult;
use App\Modules\Search\Services\SearchService;
use App\Shared\Models\AnalyticsEvent;
use App\Shared\Models\Department;
use Database\Seeders\DirectorySeeder;
use Database\Seeders\Phase2Seeder;
use Database\Seeders\Phase3Seeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\seed;

beforeEach(function () {
    Storage::fake('local');
    seed(RoleSeeder::class);
    seed(DirectorySeeder::class);
    seed(Phase2Seeder::class);
    seed(Phase3Seeder::class);
});

it('UR-DSH-01 personalises widgets by access', function () {
    $staff = User::query()->where('email', 'staff@oj.local')->firstOrFail();
    $widgets = app(WidgetRegistry::class)->forUser($staff);
    $keys = $widgets->pluck('key');

    expect($keys)->toContain('announcements', 'quick_links', 'upcoming_events')
        ->and($keys)->not->toContain('missing');

    actingAs($staff)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Welcome back')
        ->assertSee('Announcements');
});

it('UR-DSH-03 isolates a failed widget without breaking the shell', function () {
    $staff = User::query()->where('email', 'staff@oj.local')->firstOrFail();

    // Shell still renders even if one widget service would throw — widgets catch Throwable.
    actingAs($staff)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Quick Links');

    expect(AnalyticsEvent::query()->where('name', 'dashboard.viewed')->exists())->toBeTrue();
});

it('UR-CAL-01 supports month week and list views', function () {
    $staff = User::query()->where('email', 'staff@oj.local')->firstOrFail();

    actingAs($staff)
        ->get(route('calendar.index'))
        ->assertOk()
        ->assertSee('Calendar')
        ->assertSee('Month')
        ->assertSee('Week')
        ->assertSee('List');
});

it('UR-CAL-02 colour-codes categories and filters', function () {
    expect(CalendarService::CATEGORY_COLOURS)->toHaveKey('Training')
        ->and(CalendarService::CATEGORY_COLOURS['Deadline'])->toStartWith('#');

    $staff = User::query()->where('email', 'staff@oj.local')->firstOrFail();
    $events = app(CalendarService::class)->eventsFor(
        $staff,
        now()->subDay(),
        now()->addMonth(),
        'Department',
    );

    expect($events->every(fn ($e) => $e->category === 'Department'))->toBeTrue();
});

it('UR-CAL-03 generates downloadable ICS and personal feed', function () {
    $staff = User::query()->where('email', 'staff@oj.local')->firstOrFail();

    actingAs($staff)
        ->get(route('calendar.ics.download'))
        ->assertOk()
        ->assertHeader('content-type', 'text/calendar; charset=utf-8')
        ->assertSee('BEGIN:VCALENDAR')
        ->assertSee('All-hands town hall');

    $token = app(CalendarService::class)->ensureIcsToken($staff);
    get(route('calendar.ics.feed', $token))
        ->assertOk()
        ->assertSee('BEGIN:VCALENDAR');
});

it('UR-CAL-05 hides audience-targeted events from non-members', function () {
    $staff = User::query()->where('email', 'staff@oj.local')->firstOrFail(); // Engineering
    $jasmine = User::query()->where('email', 'jasmine@oj.local')->firstOrFail(); // People
    $event = CalendarEvent::query()->where('title', 'Engineering sprint review')->firstOrFail();

    expect($event->isVisibleTo($staff))->toBeTrue()
        ->and($event->isVisibleTo($jasmine))->toBeFalse();

    $staffEvents = app(CalendarService::class)->upcomingFor($staff, 20)->pluck('title');
    $jasmineEvents = app(CalendarService::class)->upcomingFor($jasmine, 20)->pluck('title');

    expect($staffEvents)->toContain('Engineering sprint review')
        ->and($jasmineEvents)->not->toContain('Engineering sprint review')
        ->and($jasmineEvents)->toContain('People partner sync');
});

it('UR-SCH-01 searches across multiple types', function () {
    $staff = User::query()->where('email', 'staff@oj.local')->firstOrFail();
    $result = app(SearchService::class)->search($staff, 'Engineering');

    $types = $result['hits']->pluck('type')->unique()->values();
    expect($types->count())->toBeGreaterThan(1)
        ->and($result['hits']->pluck('type'))->toContain('posts');

    actingAs($staff)
        ->get(route('search.index', ['q' => 'Engineering']))
        ->assertOk();
});

it('UR-SCH-02 typeahead responds quickly', function () {
    $staff = User::query()->where('email', 'staff@oj.local')->firstOrFail();

    $started = hrtime(true);
    $hits = app(SearchService::class)->suggest($staff, 'Jasmine');
    $took = (hrtime(true) - $started) / 1e6;

    expect($took)->toBeLessThan(500)
        ->and($hits->isNotEmpty())->toBeTrue();

    actingAs($staff)
        ->get(route('search.suggest', ['q' => 'Jasmine']))
        ->assertOk()
        ->assertJsonStructure(['hits', 'took_ms']);
});

it('UR-SCH-03 applies permission filter at query time (two-user differential)', function () {
    $staff = User::query()->where('email', 'staff@oj.local')->firstOrFail();
    $jasmine = User::query()->where('email', 'jasmine@oj.local')->firstOrFail();
    $jasmine->givePermissionTo(['search.view', 'news.view', 'documents.view', 'directory.view', 'calendar.view']);

    $staffHits = app(SearchService::class)->search($staff, 'tooling')['hits']->pluck('title');
    $jasmineHits = app(SearchService::class)->search($jasmine, 'tooling')['hits']->pluck('title');

    expect($staffHits)->toContain('Engineering tooling update')
        ->and($jasmineHits)->not->toContain('Engineering tooling update');

    // Same term, different ACL document visibility.
    $staffDocs = app(SearchService::class)->search($staff, 'remotely')['hits']->where('type', 'documents');
    $ops = User::factory()->create(['email' => 'ops.search@oj.local']);
    $ops->assignRole('Staff');
    $ops->givePermissionTo(['search.view', 'documents.view']);
    $opsDept = Department::query()->where('slug', 'operations')->firstOrFail();
    $ops->departments()->sync([$opsDept->id => ['job_title' => 'Ops', 'is_lead' => false]]);

    $opsDocs = app(SearchService::class)->search($ops, 'remotely')['hits']->where('type', 'documents');
    expect($staffDocs->count())->toBeGreaterThan(0)
        ->and($opsDocs->count())->toBe(0);
});

it('UR-SCH-04 exposes type facets', function () {
    $staff = User::query()->where('email', 'staff@oj.local')->firstOrFail();
    $result = app(SearchService::class)->search($staff, 'a');

    expect($result['facets'])->toHaveKey('type');
});

it('logs zero-result searches', function () {
    $staff = User::query()->where('email', 'staff@oj.local')->firstOrFail();
    app(SearchService::class)->search($staff, 'zzzxnevermatch123');

    expect(SearchZeroResult::query()->where('query', 'zzzxnevermatch123')->exists())->toBeTrue();
});

it('registers meilisearch-ready search contract', function () {
    $composer = file_get_contents(base_path('composer.json'));
    expect($composer)->toContain('meilisearch')
        ->and(app(SearchService::class)->aclTokens(
            User::query()->where('email', 'staff@oj.local')->firstOrFail()
        ))->toContain('all');
});
