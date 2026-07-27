<?php

namespace App\Modules\News\Livewire;

use App\Modules\News\Models\Post;
use App\Modules\News\Services\NewsService;
use App\Shared\Models\Department;
use Livewire\Component;

class NewsComposer extends Component
{
    public string $title = '';

    public string $summary = '';

    public string $body_html = '';

    public string $category = 'General';

    public string $status = 'draft';

    public bool $is_pinned = false;

    public bool $is_alert = false;

    /** @var list<string> */
    public array $department_ids = [];

    public function save(NewsService $news): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:200'],
            'summary' => ['nullable', 'string', 'max:500'],
            'body_html' => ['required', 'string'],
            'category' => ['required', 'string', 'max:80'],
            'status' => ['required', 'in:draft,in_review,published'],
        ]);

        $post = $news->create(auth()->user(), [
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
