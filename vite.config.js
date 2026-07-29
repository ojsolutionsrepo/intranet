import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    // Avoid public/hot writing http://[::1]:5173 — browsers on http://localhost
    // cannot load that, so the XAMPP app renders unstyled.
    server: {
        host: 'localhost',
        hmr: {
            host: 'localhost',
        },
        cors: true,
    },
});
