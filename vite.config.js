import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import path from 'node:path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': path.resolve(import.meta.dirname, 'resources/js'),
        },
    },
    server: {
        // Bind to every interface so the dev server is reachable from outside
        // the `node` container; the browser still talks to it via localhost.
        host: '0.0.0.0',
        port: 5173,
        hmr: {
            host: 'localhost',
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
            // The project is bind-mounted from a Windows (or macOS) host into a
            // Linux container, and inotify events do not cross that boundary.
            // Without polling the watcher never fires: HMR goes silent and Vite
            // keeps serving modules from a transform cache it never invalidates,
            // so edits appear to do nothing at all. Costs a little CPU; costs far
            // less than the hour spent wondering why a save did nothing.
            usePolling: true,
            interval: 300,
        },
    },
});
