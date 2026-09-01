import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    // Follow app theme attribute, not OS prefers-color-scheme alone.
    darkMode: ['selector', '[data-theme-resolved="dark"]'],
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/livewire/livewire/src/Features/SupportPagination/views/*.blade.php',
        './vendor/laravel/fortify/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './app/Modules/**/Resources/views/**/*.blade.php',
    ],
    theme: {
        extend: {
            colors: {
                oj: {
                    900: 'var(--oj-900)',
                    800: 'var(--oj-800)',
                    700: 'var(--oj-700)',
                    600: 'var(--oj-600)',
                    500: 'var(--oj-500)',
                    400: 'var(--oj-400)',
                    300: 'var(--oj-300)',
                    200: 'var(--oj-200)',
                    100: 'var(--oj-100)',
                    50: 'var(--oj-50)',
                },
                signal: {
                    600: 'var(--sig-600)',
                    500: 'var(--sig-500)',
                    400: 'var(--sig-400)',
                    100: 'var(--sig-100)',
                    on: 'var(--sig-on)',
                },
                ok: { 600: 'var(--ok-600)', 100: 'var(--ok-100)' },
                warn: { 600: 'var(--warn-600)', 100: 'var(--warn-100)' },
                err: { 600: 'var(--err-600)', 100: 'var(--err-100)' },
                info: { 600: 'var(--info-600)', 100: 'var(--info-100)' },
                ink: {
                    900: 'var(--ink-900)',
                    700: 'var(--ink-700)',
                    500: 'var(--ink-500)',
                    300: 'var(--ink-300)',
                },
                paper: {
                    0: 'var(--paper-0)',
                    1: 'var(--paper-1)',
                    2: 'var(--paper-2)',
                },
            },
            fontFamily: {
                display: ['Archivo', ...defaultTheme.fontFamily.sans],
                sans: ['Archivo', ...defaultTheme.fontFamily.sans],
                voice: ['Newsreader', ...defaultTheme.fontFamily.serif],
                mono: ['JetBrains Mono', ...defaultTheme.fontFamily.mono],
            },
            borderRadius: {
                sm: '4px',
                md: '6px',
                lg: '10px',
            },
        },
    },
    plugins: [],
};
