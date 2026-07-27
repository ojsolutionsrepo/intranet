<div>
    <form wire:submit="save" class="card overflow-x-auto p-0">
        <table class="w-full text-[12.5px]">
            <thead>
            <tr class="text-left text-[11px] uppercase tracking-wider text-[var(--ink-500)] border-b-2 border-[var(--line-strong)]">
                <th class="py-2 px-3 sticky left-0 bg-[var(--paper-0)]">Permission</th>
                @foreach ($roleNames as $roleName)
                    <th class="py-2 px-3 text-center">{{ $roleName }}</th>
                @endforeach
            </tr>
            </thead>
            <tbody>
            @foreach ($permissionNames as $permission)
                <tr class="border-b border-[var(--line)]">
                    <td class="py-2 px-3 font-mono text-[11px] sticky left-0 bg-[var(--paper-0)]">{{ $permission }}</td>
                    @foreach ($roleNames as $roleName)
                        @php $key = $this->key($roleName, $permission); @endphp
                        <td class="py-2 px-3 text-center">
                            <input
                                type="checkbox"
                                wire:click="toggle('{{ $roleName }}', '{{ $permission }}')"
                                @checked($matrix[$key] ?? false)
                            >
                        </td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="p-4 border-t border-[var(--line)]">
            <button type="submit" class="btn btn-primary">Save matrix</button>
        </div>
    </form>
</div>
