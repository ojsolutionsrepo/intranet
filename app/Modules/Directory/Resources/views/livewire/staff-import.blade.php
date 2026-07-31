<div>
    @if ($resultMessage)
        <div class="alert alert-info mb-4">{{ $resultMessage }}</div>
    @endif

    <form wire:submit="preview" class="card p-5 space-y-4 mb-5">
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">CSV / XLSX file</label>
            <input type="file" wire:model="file" accept=".csv,.txt,.xlsx,.xls" class="input">
            <p class="note mt-1">Required columns: name, email, department. Optional: team, job_title, role, phone, extension, location, expertise, start_date.</p>
            @error('file') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="btn btn-secondary" wire:loading.attr="disabled">Preview import</button>
    </form>

    @if ($hasPreview)
        @if (count($errors))
            <div class="alert alert-err mb-4">
                <p class="font-semibold mb-2">{{ count($errors) }} validation issue(s) — bad rows will be skipped on commit.</p>
                <ul class="list-disc pl-4 text-sm">
                    @foreach (array_slice($errors, 0, 20) as $error)
                        <li>{{ $error['message'] }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card overflow-x-auto mb-4">
            <table class="w-full text-[13px]">
                <thead>
                <tr class="text-left text-[11px] uppercase tracking-wider text-[var(--ink-500)] border-b-2 border-[var(--line-strong)]">
                    <th class="py-2 px-3">OK</th>
                    <th class="py-2 px-3">Name</th>
                    <th class="py-2 px-3">Email</th>
                    <th class="py-2 px-3">Department</th>
                    <th class="py-2 px-3">Role</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($previewRows as $row)
                    <tr class="border-b border-[var(--line)] {{ empty($row['_valid']) ? 'bg-[var(--err-100)]' : '' }}">
                        <td class="py-2 px-3">{{ ! empty($row['_valid']) ? '✓' : '✗' }}</td>
                        <td class="py-2 px-3">{{ $row['name'] }}</td>
                        <td class="py-2 px-3">{{ $row['email'] }}</td>
                        <td class="py-2 px-3">{{ $row['department'] }}</td>
                        <td class="py-2 px-3">{{ $row['role'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <button type="button" wire:click="commit" class="btn btn-primary" wire:loading.attr="disabled">
            Commit valid rows
        </button>
    @endif
</div>
