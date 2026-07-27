import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
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
                    900: '#0e1a2b',
                    800: '#16273d',
                    700: '#1f374f',
                    600: '#2c4a67',
                    500: '#3d6489',
                    400: '#5b83a8',
                    300: '#8aa8c4',
                    200: '#bcd0e0',
                    100: '#e2ebf2',
                    50: '#f2f6fa',
                },
                signal: {
                    600: '#b5641a',
                    500: '#d97b22',
                    400: '#e89a4d',
                    100: '#fbeeda',
                },
                ok: { 600: '#2f6d42', 100: 'var(--ok-100)' },
                warn: { 600: '#9a6212', 100: 'var(--warn-100)' },
                err: { 600: '#a3312d', 100: 'var(--err-100)' },
                info: { 600: '#245d8a', 100: 'var(--info-100)' },
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
