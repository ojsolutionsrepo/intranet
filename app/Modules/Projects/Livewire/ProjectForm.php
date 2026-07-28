<?php

namespace App\Modules\Projects\Livewire;

use App\Models\User;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectMilestone;
use App\Shared\Services\AudienceResolver;
use App\Shared\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

class ProjectForm extends Component
{
    public string $name = '';

    public string $summary = '';

    public string $rag = 'green';

    public string $status = 'active';

    public string $deep_link = '';

    public string $milestone_title = '';

    public function save(AudienceResolver $audience, AuditLogger $audit): void
    {
        $user = Auth::user();
        abort_unless($user instanceof User && $user->can('projects.manage'), 403);

        $this->validate([
            'name' => 'required|string|max:180',
            'summary' => 'nullable|string|max:2000',
            'rag' => 'nullable|in:green,amber,red',
            'status' => 'required|in:active,on_hold,completed,archived',
            'deep_link' => 'nullable|url|max:500',
            'milestone_title' => 'nullable|string|max:180',
        ]);

        $project = Project::query()->create([
            'name' => $this->name,
            'slug' => Str::slug($this->name).'-'.Str::lower(Str::random(5)),
            'source' => 'manual',
            'status' => $this->status,
            'summary' => $this->summary ?: null,
            'rag' => $this->rag ?: null,
            'deep_link' => $this->deep_link ?: null,
            'audience' => $audience->normalize([]),
            'metrics' => ['added_by' => $user->name],
            'synced_at' => now(),
        ]);

        if (filled($this->milestone_title)) {
            ProjectMilestone::query()->create([
                'project_id' => $project->id,
                'title' => $this->milestone_title,
                'status' => 'planned',
                'order' => 0,
            ]);
        }

        $audit->log('project.created', $project, null, $project->only(['name', 'source', 'rag']));

        $this->redirect(route('projects.show', $project), navigate: true);
    }

    public function render()
    {
        return view('projects::livewire.form');
    }
}
