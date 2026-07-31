{{-- Anti-FOUC theme boot — must run before CSS paint --}}
<script>
(function () {
    try {
        var key = 'oj-theme';
        var schemeKey = 'oj-theme-scheme';
        // One-time migrate to dark glass scheme (Governex-like canvas, OJ colours)
        if (localStorage.getItem(schemeKey) !== 'glass-dark-v1') {
            localStorage.setItem(key, 'dark');
            localStorage.setItem(schemeKey, 'glass-dark-v1');
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
