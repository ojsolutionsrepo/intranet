<?php

namespace App\Modules\News\Http\Controllers;

use App\Core\Modules\ModuleRegistry;
use App\Models\User;
use App\Modules\News\Models\Post;
use App\Modules\News\Models\PostAttachment;
use App\Modules\News\Services\NewsService;
use App\Shared\Models\Department;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NewsController extends Controller
{
    public function index(ModuleRegistry $registry): View
    {
        abort_unless($registry->isEnabled('news'), 404);

        return view('news::index');
    }

    public function create(ModuleRegistry $registry): View
    {
        abort_unless($registry->isEnabled('news'), 404);
        Gate::authorize('create', Post::class);

        return view('news::create', [
            'departments' => Department::query()->orderBy('name')->get(),
        ]);
    }

    /**
     * Classic multipart create — avoids Livewire tmpfile()/fileinfo on restricted hosts.
     */
    public function store(ModuleRegistry $registry, Request $request, NewsService $news): RedirectResponse
    {
        abort_unless($registry->isEnabled('news'), 404);
        Gate::authorize('create', Post::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'summary' => ['nullable', 'string', 'max:500'],
            'body_html' => ['required', 'string'],
            'category' => ['required', 'string', 'max:80'],
            'status' => ['required', 'in:draft,in_review,published'],
            'department_ids' => ['nullable', 'array'],
            'department_ids.*' => ['integer', 'exists:departments,id'],
            'is_pinned' => ['sometimes', 'boolean'],
            'is_alert' => ['sometimes', 'boolean'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'max:10240', 'extensions:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt,csv,ppt,pptx'],
        ]);

        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        try {
            $post = $news->create($user, [
                'title' => $validated['title'],
                'summary' => $validated['summary'] ?? null,
                'body_html' => $validated['body_html'],
                'category' => $validated['category'],
                'status' => $validated['status'],
                'is_pinned' => $request->boolean('is_pinned'),
                'is_alert' => $request->boolean('is_alert'),
                'audience' => [
                    'departments' => $validated['department_ids'] ?? [],
                ],
                'attachments' => $request->file('attachments', []) ?: [],
            ]);
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->withErrors(['attachments' => $e->getMessage()]);
        }

        if ($post->status === Post::STATUS_PUBLISHED) {
            return redirect()
                ->route('news.show', $post)
                ->with('status', 'Post published.');
        }

        return redirect()
            ->route('news.index')
            ->with('status', 'Post saved.');
    }

    public function storeInlineImage(ModuleRegistry $registry, Request $request, NewsService $news): JsonResponse
    {
        abort_unless($registry->isEnabled('news'), 404);
        Gate::authorize('create', Post::class);

        $request->validate([
            'image' => ['required', 'file', 'max:5120', 'extensions:jpg,jpeg,png,gif,webp'],
        ]);

        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        try {
            $url = $news->storeInlineImage($user, $request->file('image'));
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['url' => $url]);
    }

    public function show(ModuleRegistry $registry, Post $post, NewsService $news): View
    {
        abort_unless($registry->isEnabled('news'), 404);
        Gate::authorize('view', $post);

        $news->markRead($post, request()->user());
        $post->load(['author', 'attachments']);

        return view('news::show', compact('post'));
    }

    public function downloadAttachment(ModuleRegistry $registry, Post $post, PostAttachment $attachment): StreamedResponse
    {
        abort_unless($registry->isEnabled('news'), 404);
        abort_unless($attachment->post_id === $post->id, 404);
        Gate::authorize('view', $post);

        $disk = Storage::disk($attachment->disk);
        abort_unless($disk->exists($attachment->path), 404);

        return $disk->download($attachment->path, $attachment->original_name);
    }
}
