{{-- Anti-FOUC theme boot — must run before CSS paint --}}
<script>
(function () {
    try {
        var key = 'oj-theme';
        var schemeKey = 'oj-theme-scheme';
        // One-time glass-dark-v2 marker; keep an existing user choice if present.
        if (localStorage.getItem(schemeKey) !== 'glass-dark-v2') {
            if (!localStorage.getItem(key)) {
                localStorage.setItem(key, 'dark');
            }
            localStorage.setItem(schemeKey, 'glass-dark-v2');
        }
        var stored = localStorage.getItem(key) || 'dark';
        if (stored !== 'light' && stored !== 'dark' && stored !== 'system') {
            stored = 'dark';
        }
        document.documentElement.setAttribute('data-theme', stored);
        if (stored === 'system') {
            document.documentElement.setAttribute(
                'data-theme-resolved',
                window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
            );
        } else {
            document.documentElement.setAttribute('data-theme-resolved', stored);
        }
    } catch (e) {
        document.documentElement.setAttribute('data-theme', 'dark');
        document.documentElement.setAttribute('data-theme-resolved', 'dark');
    }
})();
</script>
