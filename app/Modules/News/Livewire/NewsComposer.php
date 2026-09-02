<?php

namespace App\Modules\News\Livewire;

use App\Models\User;
use App\Modules\News\Models\Post;
use App\Modules\News\Services\NewsService;
use App\Shared\Models\Department;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class NewsComposer extends Component
{
    use WithFileUploads;

    public string $title = '';

    public string $summary = '';

    public string $body_html = '';

    public string $category = 'General';

    public string $status = 'draft';

    public bool $is_pinned = false;

    public bool $is_alert = false;

    /** @var list<string> */
    public array $department_ids = [];

    /** @var list<TemporaryUploadedFile> */
    public array $attachments = [];

    public ?TemporaryUploadedFile $inlineImage = null;

    public function updatedInlineImage(NewsService $news): void
    {
        $this->validate([
            'inlineImage' => ['required', 'image', 'max:5120'],
        ]);

        $user = Auth::user();
        if (! $user instanceof User || ! $this->inlineImage) {
            return;
        }

        $url = $news->storeInlineImage($user, $this->inlineImage);
        $this->inlineImage = null;
        $this->dispatch('rich-editor-insert-image', url: $url);
    }

    public function removeAttachment(int $index): void
    {
        if (! isset($this->attachments[$index])) {
            return;
        }

        $files = $this->attachments;
        unset($files[$index]);
        $this->attachments = array_values($files);
    }

    public function save(NewsService $news): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:200'],
            'summary' => ['nullable', 'string', 'max:500'],
            'body_html' => ['required', 'string'],
            'category' => ['required', 'string', 'max:80'],
            'status' => ['required', 'in:draft,in_review,published'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt,csv,ppt,pptx'],
        ]);

        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        $post = $news->create($user, [
            'title' => $this->title,
            'summary' => $this->summary,
            'body_html' => $this->body_html,
            'category' => $this->category,
            'status' => $this->status,
            'is_pinned' => $this->is_pinned,
            'is_alert' => $this->is_alert,
            'audience' => [
                'departments' => $this->department_ids,
            ],
            'attachments' => $this->attachments,
        ]);

        session()->flash('status', 'Post saved.');

        if ($post->status === Post::STATUS_PUBLISHED) {
            $this->redirect(route('news.show', $post), navigate: true);
        } else {
            $this->redirect(route('news.index'), navigate: true);
        }
    }

    public function render()
    {
        return view('news::livewire.composer', [
            'departments' => Department::query()->orderBy('name')->get(),
        ]);
    }
}
