<?php

use App\Models\User;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Models\DocumentCategory;
use App\Modules\Documents\Models\DocumentVersion;
use App\Modules\Documents\Services\DocumentService;
use App\Modules\Documents\Services\PolicyService;
use App\Modules\News\Models\Post;
use App\Modules\News\Services\NewsService;
use App\Shared\Models\Department;
use App\Shared\Services\AudienceResolver;
use App\Shared\Services\HtmlSanitizer;
use Database\Seeders\DirectorySeeder;
use Database\Seeders\Phase2Seeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function () {
    Storage::fake('local');
    seed(RoleSeeder::class);
    seed(DirectorySeeder::class);
    seed(Phase2Seeder::class);
});

it('UR-NEW-01 shows the news feed with core fields', function () {
    $staff = User::query()->where('email', 'staff@oj.local')->firstOrFail();

    actingAs($staff)
        ->get(route('news.index'))
        ->assertOk()
        ->assertSee('Welcome to the intranet news feed')
        ->assertSee('General');
});

it('UR-NEW-02 pins critical posts to the top', function () {
    $staff = User::query()->where('email', 'staff@oj.local')->firstOrFail();
    $feed = app(NewsService::class)->feedFor($staff);

    expect(collect($feed->items())->first()->is_pinned)->toBeTrue();
});

it('UR-NEW-03 sanitises XSS from rich HTML', function () {
    $clean = app(HtmlSanitizer::class)->clean('<p>Hi</p><script>alert(1)</script><img src=x onerror=alert(1)>');

    expect($clean)->toContain('<p>Hi</p>')
        ->and($clean)->not->toContain('<script>')
        ->and($clean)->not->toContain('onerror');

    $post = Post::query()->where('title', 'Engineering tooling update')->firstOrFail();
    expect($post->body_html)->not->toContain('<script>');
});

it('UR-NEW-03 composer stores rich text and attachments', function () {
    Storage::fake('public');
    $admin = User::query()->where('email', 'admin@oj.local')->firstOrFail();

    $image = UploadedFile::fake()->image('banner.png', 120, 80);
    $doc = UploadedFile::fake()->create('briefing.pdf', 120, 'application/pdf');

    \Livewire\Livewire::actingAs($admin)
        ->test(\App\Modules\News\Livewire\NewsComposer::class)
        ->set('title', 'Office reopening')
        ->set('summary', 'What to expect next week')
        ->set('body_html', '<p>Please <strong>arrive early</strong>.</p>')
        ->set('status', 'published')
        ->set('attachments', [$image, $doc])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $post = Post::query()->where('title', 'Office reopening')->firstOrFail();
    expect($post->body_html)->toContain('<strong>arrive early</strong>')
        ->and($post->attachments)->toHaveCount(2)
        ->and($post->attachments->where('is_image', true))->toHaveCount(1);

    actingAs($admin)
        ->get(route('news.show', $post))
        ->assertOk()
        ->assertSee('Please')
        ->assertSee('arrive early')
        ->assertSee('Attachments')
        ->assertSee('briefing.pdf');
});

it('UR-NEW-04 hides audience-targeted posts from non-members', function () {
    $jasmine = User::query()->where('email', 'jasmine@oj.local')->firstOrFail(); // People
    $staff = User::query()->where('email', 'staff@oj.local')->firstOrFail(); // Engineering
    $post = Post::query()->where('title', 'Engineering tooling update')->firstOrFail();

    expect($post->isVisibleTo($staff))->toBeTrue()
        ->and($post->isVisibleTo($jasmine))->toBeFalse();

    actingAs($jasmine)
        ->get(route('news.show', $post))
        ->assertForbidden();

    actingAs($staff)
        ->get(route('news.show', $post))
        ->assertOk();
});

it('UR-DOC-01 seeds nested document categories', function () {
    $policies = DocumentCategory::query()->where('slug', 'policies')->whereNull('parent_id')->firstOrFail();
    $hr = DocumentCategory::query()->where('slug', 'hr-policies')->firstOrFail();

    expect($hr->parent_id)->toBe($policies->id)
        ->and(DocumentCategory::query()->whereIn('slug', ['templates', 'guides', 'forms'])->count())->toBe(3);
});

it('UR-DOC-02 searches inside document body text', function () {
    $jasmine = User::query()->where('email', 'jasmine@oj.local')->firstOrFail();
    $results = app(DocumentService::class)->searchBody('remotely', $jasmine);

    expect($results->count())->toBeGreaterThan(0)
        ->and($results->first()->title)->toBe('Remote Working Policy');
});

it('UR-DOC-03/04/05 versions, downloads previous, and restore-as-new', function () {
    $admin = User::query()->where('email', 'admin@oj.local')->firstOrFail();
    $document = Document::query()->where('title', 'Remote Working Policy')->firstOrFail();
    $v1 = $document->currentVersion;
    expect($v1)->not->toBeNull();

    $file = UploadedFile::fake()->createWithContent(
        'remote-working-policy-v2.txt',
        "Remote Working Policy v2\n\nFour days remote with approval.\n",
    );

    $v2 = app(DocumentService::class)->uploadNewVersion($document, $admin, $file, 'Clarified days')['version'];

    $document->refresh();
    expect($document->currentVersion->id)->toBe($v2->id)
        ->and($document->versions()->count())->toBe(2);

    $downloaded = app(DocumentService::class)->download($document, $v1->fresh(), $admin);
    expect($downloaded)->toContain('three days');

    $v3 = app(DocumentService::class)->restoreVersionAsNew($document, $v1->fresh(), $admin);
    $document->refresh();

    expect($document->versions()->count())->toBe(3)
        ->and($document->current_version_id)->toBe($v3->id)
        ->and($v3->changelog)->toContain('Restored from v1');
});

it('UR-DOC-07 enforces department ACL on direct URL', function () {
    $document = Document::query()->where('title', 'Remote Working Policy')->firstOrFail();
    $opsOnly = User::factory()->create(['email' => 'ops.only@oj.local']);
    $opsOnly->assignRole('Staff');
    $ops = Department::query()->where('slug', 'operations')->firstOrFail();
    $opsOnly->departments()->sync([$ops->id => ['job_title' => 'Ops', 'is_lead' => false]]);

    // Grant view permission like other staff
    $opsOnly->givePermissionTo('documents.view');

    expect($document->isVisibleTo($opsOnly))->toBeFalse();

    actingAs($opsOnly)
        ->get(route('documents.show', $document))
        ->assertForbidden();
});

it('UR-DOC-10 soft-trashes documents with restore', function () {
    $admin = User::query()->where('email', 'admin@oj.local')->firstOrFail();
    $document = Document::query()->where('title', 'Remote Working Policy')->firstOrFail();
    $service = app(DocumentService::class);

    $service->trash($document);
    expect(Document::withTrashed()->find($document->id)?->trashed_at)->not->toBeNull();

    $service->restoreFromTrash(Document::withTrashed()->findOrFail($document->id));
    expect(Document::query()->find($document->id))->not->toBeNull();
});

it('UR-DOC-12 warns on duplicate checksum', function () {
    $admin = User::query()->where('email', 'admin@oj.local')->firstOrFail();
    $category = DocumentCategory::query()->where('slug', 'guides')->firstOrFail();

    $payload = "identical payload for checksum test\n";
    $file1 = UploadedFile::fake()->createWithContent('a.txt', $payload);
    $first = app(DocumentService::class)->upload($admin, [
        'title' => 'Guide A',
        'category_id' => $category->id,
        'visibility' => 'all',
        'audience' => [],
    ], $file1);

    $file2 = UploadedFile::fake()->createWithContent('b.txt', $payload);
    $second = app(DocumentService::class)->upload($admin, [
        'title' => 'Guide B',
        'category_id' => $category->id,
        'visibility' => 'all',
        'audience' => [],
    ], $file2);

    expect($second['duplicate_warning'])->not->toBeNull()
        ->and($second['duplicate_warning']->id)->toBe($first['document']->currentVersion->id);
});

it('rejects spoofed file extensions', function () {
    $admin = User::query()->where('email', 'admin@oj.local')->firstOrFail();
    $category = DocumentCategory::query()->where('slug', 'forms')->firstOrFail();

    // Client claims PDF; content is plain text so Symfony guesses txt/txt-like.
    $file = UploadedFile::fake()->createWithContent('evil.pdf', "not a real pdf, just text pretending\n");

    expect(fn () => app(DocumentService::class)->upload($admin, [
        'title' => 'Spoof',
        'category_id' => $category->id,
        'visibility' => 'all',
        'audience' => [],
    ], $file))->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

it('UR-POL-01/02/06 policy hub ack is version-specific and resets', function () {
    $jasmine = User::query()->where('email', 'jasmine@oj.local')->firstOrFail();
    $admin = User::query()->where('email', 'admin@oj.local')->firstOrFail();
    $document = Document::query()->where('title', 'Remote Working Policy')->firstOrFail();
    $policies = app(PolicyService::class);

    actingAs($jasmine)->get(route('policies.index'))->assertOk()->assertSee('Remote Working Policy');

    $policies->acknowledge($document, $jasmine);
    expect($policies->hasAcknowledgedCurrent($document, $jasmine))->toBeTrue();

    $file = UploadedFile::fake()->createWithContent('policy-v2b.txt', "Remote Working Policy revised again\n");
    app(DocumentService::class)->uploadNewVersion($document, $admin, $file, 'New mandatory version');

    $document->refresh();
    expect($policies->hasAcknowledgedCurrent($document, $jasmine))->toBeFalse()
        ->and($document->reviewStatus())->toBeIn(['due', 'current', 'overdue']);
});

it('UR-POL-03 exports compliance matrix CSV', function () {
    $admin = User::query()->where('email', 'admin@oj.local')->firstOrFail();
    $document = Document::query()->where('title', 'Remote Working Policy')->firstOrFail();

    $response = actingAs($admin)->get(route('policies.compliance', $document));
    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('compliance.csv');

    $rows = app(PolicyService::class)->complianceMatrix($document);
    expect($rows)->not->toBeEmpty()
        ->and($rows[0])->toHaveKeys(['user', 'email', 'status', 'acknowledged_at']);
});

it('AudienceResolver treats empty audience as company-wide', function () {
    $user = User::query()->where('email', 'staff@oj.local')->firstOrFail();
    expect(app(AudienceResolver::class)->allows([], $user))->toBeTrue()
        ->and(app(AudienceResolver::class)->allows(null, $user))->toBeTrue();
});
