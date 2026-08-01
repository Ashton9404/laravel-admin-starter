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
    define: {
        // vue-i18n ships both APIs and a devtools bridge unless told otherwise.
        // We only use the Composition API, so drop the rest from the bundle.
        __VUE_I18N_FULL_INSTALL__: false,
        __VUE_I18N_LEGACY_API__: false,
        __INTLIFY_PROD_DEVTOOLS__: false,
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
            // The project is bind-mounted from a Windows (or macOS) host into a
            // Linux container, and inotify events do not cross that boundary.
            // Without polling the watcher never fires: HMR goes silent and Vite
            // keeps serving modules from a transform cache it never invalidates,
            // so edits appear to do nothing at all.
            usePolling: true,
            interval: 500,
            // Polling means every ignored path is a path we do NOT stat twice a
            // second. Leaving vendor/ (tens of thousands of files) in scope over
            // a Windows bind mount starves the dev server badly enough that it
            // stops answering module requests altogether.
            ignored: [
                '**/.git/**',
                '**/node_modules/**',
                '**/vendor/**',
                '**/storage/**',
                '**/public/build/**',
                '**/tests/**',
            ],
        },
    },
});
