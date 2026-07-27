@props(['compact' => false])

<div class="theme-toggle" data-theme-toggle>
    <label class="sr-only" for="theme-select-{{ $compact ? 'c' : 'f' }}">Colour theme</label>
    <select
        id="theme-select-{{ $compact ? 'c' : 'f' }}"
        class="theme-select {{ $compact ? 'theme-select-sm' : '' }}"
        data-theme-select
        aria-label="Colour theme"
    >
        <option value="system">System</option>
        <option value="light">Light</option>
        <option value="dark">Dark</option>
    </select>
</div>
