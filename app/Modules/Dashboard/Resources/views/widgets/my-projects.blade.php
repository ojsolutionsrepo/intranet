<div class="card p-5 h-full">
    <h3 class="font-display font-semibold text-base mb-3">My Projects</h3>
    @if ($error)
        <p class="note">{{ $error }}</p>
    @elseif ($projects->isEmpty())
        <p class="note">No projects assigned to you.</p>
    @else
        <ul class="space-y-2 text-[13.5px]">
            @foreach ($projects as $project)
                <li class="flex items-center justify-between gap-2 border-b border-[var(--line)] py-1.5">
                    <a href="{{ route('projects.show', $project) }}" class="font-medium hover:text-[var(--sig-500)] truncate">
                        {{ $project->name }}
                    </a>
                    <span class="flex items-center gap-1 shrink-0">
                        @if ($project->rag)
                            <span class="badge badge-{{ $project->ragBadge() }}">{{ strtoupper($project->rag) }}</span>
                        @endif
                        @if ($project->isStale())
                            <span class="badge badge-warn">Stale</span>
                        @endif
                    </span>
                </li>
            @endforeach
        </ul>
        <a href="{{ route('projects.index') }}" class="note inline-block mt-3 hover:underline">View all</a>
    @endif
</div>
