<?php

namespace App\Modules\News\Services;

use App\Models\User;
use App\Modules\News\Models\Post;
use App\Modules\News\Models\PostAttachment;
use App\Modules\News\Models\PostRead;
use App\Shared\Contracts\VirusScanner;
use App\Shared\Services\AudienceResolver;
use App\Shared\Services\AuditLogger;
use App\Shared\Services\HtmlSanitizer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class NewsService
{
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public function __construct(
        private readonly HtmlSanitizer $sanitizer,
        private readonly AudienceResolver $audience,
        private readonly AuditLogger $audit,
        private readonly VirusScanner $virusScanner,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $author, array $data): Post
    {
        $post = Post::query()->create([
            'author_id' => $author->id,
            'title' => $data['title'],
            'slug' => Str::slug($data['title']).'-'.Str::lower(Str::random(5)),
            'summary' => $data['summary'] ?? null,
            'body_html' => $this->sanitizer->clean((string) $data['body_html']),
            'category' => $data['category'] ?? 'General',
            'status' => $data['status'] ?? Post::STATUS_DRAFT,
            'is_pinned' => (bool) ($data['is_pinned'] ?? false),
            'is_alert' => (bool) ($data['is_alert'] ?? false),
            'audience' => $this->audience->normalize($data['audience'] ?? []),
            'published_at' => ($data['status'] ?? null) === Post::STATUS_PUBLISHED ? now() : ($data['published_at'] ?? null),
            'scheduled_at' => $data['scheduled_at'] ?? null,
        ]);

        if (! empty($data['attachments']) && is_array($data['attachments'])) {
            foreach ($data['attachments'] as $file) {
                if ($file instanceof UploadedFile) {
                    $this->storeAttachment($post, $author, $file);
                }
            }
        }

        $this->audit->log('news.created', $post, null, $post->only(['title', 'status', 'audience']));

        return $post->load('attachments');
    }

    public function storeInlineImage(User $author, UploadedFile $file): string
    {
        $this->assertCleanUpload($file);

        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        if (! in_array($ext, self::IMAGE_EXTENSIONS, true)) {
            throw new \RuntimeException('Inline image type not allowed.');
        }

        $path = $file->storeAs(
            'news/inline/'.$author->id,
            Str::uuid()->toString().'.'.$ext,
            'public',
        );

        if (! is_string($path) || $path === '') {
            throw new \RuntimeException('Could not store inline image.');
        }

        return Storage::disk('public')->url($path);
    }

    public function storeAttachment(Post $post, User $author, UploadedFile $file): PostAttachment
    {
        $this->assertCleanUpload($file);

        $original = $file->getClientOriginalName();
        $ext = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $mime = $this->mimeFromExtension($ext);
        $isImage = in_array($ext, self::IMAGE_EXTENSIONS, true);
        $path = $file->storeAs(
            'news/attachments/'.$post->id,
            Str::uuid()->toString().'.'.$ext,
            'public',
        );

        if (! is_string($path) || $path === '') {
            throw new \RuntimeException('Could not store attachment.');
        }

        return PostAttachment::query()->create([
            'post_id' => $post->id,
            'disk' => 'public',
            'path' => $path,
            'original_name' => $original,
            'mime_type' => $mime,
            'size' => (int) $file->getSize(),
            'is_image' => $isImage,
            'uploaded_by' => $author->id,
        ]);
    }

    private function assertCleanUpload(UploadedFile $file): void
    {
        $real = $file->getRealPath();
        if (! is_string($real) || $real === '') {
            throw new \RuntimeException('Uploaded file is missing.');
        }

        $scan = $this->virusScanner->scan($real);
        if (! ($scan['clean'] ?? false)) {
            throw new \RuntimeException($scan['message'] ?? 'Upload failed virus scan.');
        }
    }

    private function mimeFromExtension(string $ext): string
    {
        return match (strtolower($ext)) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'txt' => 'text/plain',
            'csv' => 'text/csv',
            default => 'application/octet-stream',
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Post $post, array $data): Post
    {
        $old = $post->only(['title', 'status', 'body_html', 'audience', 'is_pinned']);

        $post->fill([
            'title' => $data['title'] ?? $post->title,
            'summary' => $data['summary'] ?? $post->summary,
            'body_html' => isset($data['body_html']) ? $this->sanitizer->clean((string) $data['body_html']) : $post->body_html,
            'category' => $data['category'] ?? $post->category,
            'status' => $data['status'] ?? $post->status,
            'is_pinned' => array_key_exists('is_pinned', $data) ? (bool) $data['is_pinned'] : $post->is_pinned,
            'is_alert' => array_key_exists('is_alert', $data) ? (bool) $data['is_alert'] : $post->is_alert,
            'audience' => isset($data['audience']) ? $this->audience->normalize($data['audience']) : $post->audience,
            'scheduled_at' => $data['scheduled_at'] ?? $post->scheduled_at,
        ]);

        if (($data['status'] ?? null) === Post::STATUS_PUBLISHED && $post->published_at === null) {
            $post->published_at = now();
        }

        $post->save();
        $this->audit->log('news.updated', $post, $old, $post->only(['title', 'status', 'body_html', 'audience', 'is_pinned']));

        return $post;
    }

    public function publish(Post $post): Post
    {
        return $this->update($post, ['status' => Post::STATUS_PUBLISHED]);
    }

    /**
     * @return LengthAwarePaginator<int, Post>
     */
    public function feedFor(User $user, int $perPage = 10): LengthAwarePaginator
    {
        $posts = Post::query()
            ->with('author')
            ->published()
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->get()
            ->filter(fn (Post $post) => $post->isVisibleTo($user))
            ->values();

        $page = \Illuminate\Pagination\Paginator::resolveCurrentPage();
        $items = $posts->forPage($page, $perPage)->values();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $posts->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()],
        );
    }

    /**
     * @return Collection<int, Post>
     */
    public function alertBannerFor(User $user): Collection
    {
        return Post::query()
            ->published()
            ->where('is_alert', true)
            ->orderByDesc('published_at')
            ->get()
            ->filter(fn (Post $post) => $post->isVisibleTo($user))
            ->values();
    }

    public function markRead(Post $post, User $user): void
    {
        PostRead::query()->updateOrCreate(
            ['post_id' => $post->id, 'user_id' => $user->id],
            ['read_at' => now()],
        );
    }
}
