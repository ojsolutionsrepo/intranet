@extends('layouts.app')

@section('title', 'Import staff')

@section('breadcrumb')
    <a href="{{ route('directory.index') }}" class="text-[var(--ink-500)] hover:text-[var(--ink-900)]">Directory</a>
    <span class="mx-2 text-[var(--ink-400)]">/</span>
    <span class="text-[var(--ink-900)] font-medium">Import</span>
@endsection

@section('content')
    <div class="page-form-wide">
        <h1 class="font-display font-bold text-3xl tracking-tight mb-2">Import staff</h1>
        <p class="note mb-6">Upload CSV or XLSX, preview validation errors and account matches, then commit valid rows only.</p>

        @if (session('status'))
            <div class="alert alert-info mb-4">{{ session('status') }}</div>
        @endif
        @if (session('import_error'))
            <div class="alert alert-err mb-4">{{ session('import_error') }}</div>
        @endif

        @unless ($hasPreview)
            <form id="import-upload" method="POST" action="{{ route('directory.import.preview') }}" enctype="multipart/form-data" class="card p-5 space-y-4 mb-5">
                @csrf
                <div>
                    <label for="file" class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">CSV / XLSX file</label>
                    <input id="file" type="file" name="file" accept=".csv,.txt,.xlsx,.xls" class="input" required>
                    <p class="note mt-1">Either the directory template (required: name, email, department. Optional: team, job_title, role, phone, extension, location, expertise, start_date), or the Employee Information Form responses sheet downloaded from Google Sheets (File → Download → CSV or .xlsx).</p>
                    @error('file') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror

                    <div id="upload-progress" class="mt-3" hidden>
                        {{-- Inline styles: public/build CSS is prebuilt, so new utility classes wouldn't exist on the server. --}}
                        <div role="progressbar" aria-label="Upload progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"
                             style="height: 0.5rem; width: 100%; border-radius: 9999px; background: var(--line); overflow: hidden;">
                            <div id="upload-bar" style="height: 100%; width: 0%; background: var(--sig-600); transition: width 150ms linear;"></div>
                        </div>
                        <p id="upload-status" class="note text-xs mt-1" aria-live="polite"></p>
                    </div>
                </div>
                <button id="import-preview-btn" type="submit" class="btn btn-secondary">Preview import</button>
            </form>

            <script>
                (function () {
                    const form = document.getElementById('import-upload');
                    const input = document.getElementById('file');
                    const button = document.getElementById('import-preview-btn');
                    const wrap = document.getElementById('upload-progress');
                    const bar = document.getElementById('upload-bar');
                    const track = bar.parentElement;
                    const status = document.getElementById('upload-status');
                    let nextUrl = null;

                    // Without this script the form still works as a normal upload.
                    button.disabled = true;

                    function setProgress(pct, text, isError) {
                        wrap.hidden = false;
                        bar.style.width = pct + '%';
                        track.setAttribute('aria-valuenow', String(pct));
                        bar.style.background = isError ? 'var(--err-600)' : 'var(--sig-600)';
                        status.textContent = text;
                        status.style.color = isError ? 'var(--err-600)' : '';
                    }

                    input.addEventListener('change', function () {
                        nextUrl = null;
                        button.disabled = true;
                        if (!input.files.length) { wrap.hidden = true; return; }

                        const file = input.files[0];
                        const data = new FormData(form);
                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', form.action);
                        xhr.setRequestHeader('Accept', 'application/json');
                        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                        setProgress(0, 'Uploading ' + file.name + '…');
                        xhr.upload.addEventListener('progress', function (e) {
                            if (e.lengthComputable) {
                                const pct = Math.round((e.loaded / e.total) * 100);
                                setProgress(pct, 'Uploading ' + file.name + '… ' + pct + '%');
                            }
                        });
                        xhr.addEventListener('load', function () {
                            let body = {};
                            try { body = JSON.parse(xhr.responseText); } catch (e) {}
                            if (xhr.status >= 200 && xhr.status < 300 && body.ok) {
                                nextUrl = body.next;
                                setProgress(100, 'Upload complete. Click "Preview import" to continue.');
                                button.disabled = false;
                            } else {
                                const msg = (body.errors && body.errors.file && body.errors.file[0])
                                    || body.message
                                    || ('Upload failed (HTTP ' + xhr.status + '). Please try again.');
                                setProgress(100, msg, true);
                            }
                        });
                        xhr.addEventListener('error', function () {
                            setProgress(100, 'Upload failed. Check your connection and try again.', true);
                        });
                        xhr.send(data);
                    });

                    form.addEventListener('submit', function (e) {
                        if (nextUrl) { e.preventDefault(); window.location.href = nextUrl; }
                        else if (button.disabled) { e.preventDefault(); }
                    });
                })();
            </script>
        @else
            <div class="card p-4 mb-4 flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm">Previewing <strong>{{ $fileName }}</strong>. Nothing has been saved yet.</p>
                <form method="POST" action="{{ route('directory.import.cancel') }}">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-sm">Cancel and upload a different file</button>
                </form>
            </div>

            @if ($isFormImport)
                <div class="alert alert-info mb-4">Employee Information Form detected. Each row is matched to an existing account by its staff (work) email, which is the unique identifier for each person. Check the "Import into" column and change any wrong matches before committing. Matched accounts keep their login email, name and role; their staff information record, including next of kin and bank details, is filled in from the form. Photo, CV and DBS uploads are kept as Google Drive links.</div>
            @endif
            @if (count($importErrors))
                <div class="alert alert-err mb-4">
                    <p class="font-semibold mb-2">{{ count($importErrors) }} validation issue(s). Bad rows will be skipped on commit.</p>
                    <ul class="list-disc pl-4 text-sm">
                        @foreach (array_slice($importErrors, 0, 20) as $error)
                            <li>{{ $error['message'] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('directory.import.commit') }}">
                @csrf
                <div class="card overflow-x-auto mb-4">
                    <table class="w-full text-[13px]">
                        <thead>
                        <tr class="text-left text-[11px] uppercase tracking-wider text-[var(--ink-500)] border-b-2 border-[var(--line-strong)]">
                            <th class="py-2 px-3">OK</th>
                            <th class="py-2 px-3">Name</th>
                            <th class="py-2 px-3">Email</th>
                            <th class="py-2 px-3">{{ $isFormImport ? 'Job title' : 'Department' }}</th>
                            <th class="py-2 px-3">Role</th>
                            <th class="py-2 px-3">Import into</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($previewRows as $i => $row)
                            @php($selected = $row['match_user_id'] ? (string) $row['match_user_id'] : 'new')
                            <tr class="border-b border-[var(--line)] {{ empty($row['_valid']) ? 'bg-[var(--err-100)]' : '' }}">
                                <td class="py-2 px-3">{{ ! empty($row['_valid']) ? '✓' : '✗' }}</td>
                                <td class="py-2 px-3">{{ $row['name'] }}</td>
                                <td class="py-2 px-3">{{ $row['email'] }}</td>
                                <td class="py-2 px-3">{{ $isFormImport ? $row['job_title'] : $row['department'] }}</td>
                                <td class="py-2 px-3">{{ $row['role'] ?? 'Staff (new) / unchanged' }}</td>
                                <td class="py-2 px-3" style="min-width: 16rem;">
                                    @if (! empty($row['_valid']))
                                        <select name="mappings[{{ $i }}]" class="input text-[13px]" aria-label="Import {{ $row['name'] }} into">
                                            <option value="new" @selected($selected === 'new')>➕ Create new account</option>
                                            <option value="skip">Skip this row</option>
                                            <optgroup label="Existing staff">
                                                @foreach ($users as $user)
                                                    <option value="{{ $user->id }}" @selected($selected === (string) $user->id)>{{ $user->name }} ({{ $user->email }})</option>
                                                @endforeach
                                            </optgroup>
                                        </select>
                                        @if ($row['match_reason'])
                                            <p class="note text-xs mt-1">Auto-matched by {{ $row['match_reason'] }}</p>
                                        @endif
                                    @else
                                        <span class="note text-xs">Skipped (fix errors)</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <button type="submit" class="btn btn-primary">Commit valid rows</button>
            </form>
        @endif
    </div>
@endsection
