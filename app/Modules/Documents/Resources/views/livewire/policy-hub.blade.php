<div>
    <div class="space-y-3">
        @forelse ($policies as $policy)
            <div class="card p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <a href="{{ route('documents.show', $policy) }}" class="font-display font-semibold text-lg hover:text-[var(--sig-600)]">{{ $policy->title }}</a>
                        <div class="flex flex-wrap gap-2 mt-2">
                            @php $chip = $policy->review_chip; @endphp
                            <span class="badge {{ $chip === 'overdue' ? 'badge-err' : ($chip === 'due' ? 'badge-warn' : 'badge-ok') }}">{{ ucfirst($chip) }}</span>
                            <span class="badge {{ $policy->ack_status === 'acknowledged' ? 'badge-ok' : 'badge-warn' }}">
                                {{ $policy->ack_status === 'acknowledged' ? 'Acknowledged' : 'Action needed' }}
                            </span>
                            <span class="badge badge-info">v{{ $policy->currentVersion?->version_number }}</span>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if ($policy->mandatory_ack && $policy->ack_status !== 'acknowledged')
                            <form method="POST" action="{{ route('policies.acknowledge', $policy) }}">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-sm">Acknowledge</button>
                            </form>
                        @endif
                        @can('policies.manage')
                            <a href="{{ route('policies.compliance', $policy) }}" class="btn btn-ghost btn-sm">Export compliance</a>
                        @endcan
                    </div>
                </div>
            </div>
        @empty
            <div class="card p-6"><p class="note">No policies in your audience yet.</p></div>
        @endforelse
    </div>
</div>
