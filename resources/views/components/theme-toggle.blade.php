@props(['compact' => false])

@php
    $sizeClass = $compact ? 'theme-icon-btn-sm' : 'theme-icon-btn';
@endphp

<div class="theme-toggle" data-theme-toggle>
    <button
        type="button"
        class="theme-icon-btn {{ $sizeClass }}"
        data-theme-cycle
        aria-label="Colour theme"
        title="Colour theme"
    >
        <span class="theme-icon theme-icon-light" aria-hidden="true">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="4"/>
                <path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>
            </svg>
        </span>
        <span class="theme-icon theme-icon-dark" aria-hidden="true">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 14.5A8.5 8.5 0 1 1 9.5 3a7 7 0 0 0 11.5 11.5z"/>
            </svg>
        </span>
        <span class="theme-icon theme-icon-system" aria-hidden="true">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="14" rx="2"/>
                <path d="M8 20h8M12 18v2"/>
            </svg>
        </span>
        <span class="sr-only" data-theme-label>System</span>
    </button>
</div>
