{{-- Anti-FOUC theme boot — must run before CSS paint --}}
<script>
(function () {
    try {
        var key = 'oj-theme';
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
        document.documentElement.setAttribute('data-theme', 'system');
    }
})();
</script>
