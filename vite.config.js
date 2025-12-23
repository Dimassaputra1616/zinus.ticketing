import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

const devHost = process.env.VITE_DEV_HOST ?? '127.0.0.1';
const devPort = Number(process.env.VITE_DEV_PORT ?? 5173);
const hmrHost = process.env.VITE_DEV_HMR_HOST ?? devHost;

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: {
        host: devHost,
        port: devPort,
        strictPort: true,
        hmr: {
            host: hmrHost,
            port: devPort,
        },
    },
});
