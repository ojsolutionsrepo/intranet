<div>
    @if ($projects->isEmpty())
        <p class="note">No projects visible to you yet.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-[13.5px]">
                <thead>
                <tr class="text-left text-[11px] uppercase tracking-wider text-[var(--ink-500)] border-b-2 border-[var(--line-strong)]">
                    <th class="py-2 px-3">Project</th>
                    <th class="py-2 px-3">Source</th>
                    <th class="py-2 px-3">RAG</th>
                    <th class="py-2 px-3">Synced</th>
                    <th class="py-2 px-3">Status</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($projects as $project)
                    <tr class="border-b border-[var(--line)]">
                        <td class="py-3 px-3">
                            <a href="{{ route('projects.show', $project) }}" class="font-semibold text-[var(--ink-900)] hover:text-[var(--sig-500)]">
                                {{ $project->name }}
                            </a>
                            @if ($project->isStale())
                                <span class="badge badge-warn ml-2" title="Last sync older than {{ config('integrations.projects.staleness_minutes') }} minutes">Stale</span>
                            @endif
                        </td>
                        <td class="py-3 px-3 font-mono text-xs">{{ $project->source }}</td>
                        <td class="py-3 px-3">
                            @if ($project->rag)
                                <span class="badge badge-{{ $project->ragBadge() }}">{{ strtoupper($project->rag) }}</span>
                            @else
                                <span class="note">—</span>
                            @endif
                        </td>
                        <td class="py-3 px-3 note">
                            {{ $project->synced_at?->diffForHumans() ?? 'Never' }}
                        </td>
                        <td class="py-3 px-3">{{ $project->status }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $projects->links() }}</div>
    @endif
</div>
