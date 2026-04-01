import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

import { VitePWA } from 'vite-plugin-pwa';

const devHost = process.env.VITE_DEV_HOST ?? '127.0.0.1';
const devPort = Number(process.env.VITE_DEV_PORT ?? 5173);
const hmrHost = process.env.VITE_DEV_HMR_HOST ?? devHost;

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        VitePWA({
            registerType: 'autoUpdate',
            outDir: 'public/build',
            manifest: {
                name: 'Zinus IT Support Center',
                short_name: 'Zinus IT',
                description: 'IT Ticketing and Support System for Zinus',
                theme_color: '#12824C',
                background_color: '#F6F9F8',
                display: 'standalone',
                orientation: 'portrait',
                icons: [
                    {
                        src: '/favicon.png',
                        sizes: '1024x1024',
                        type: 'image/png'
                    },
                    {
                        src: '/favicon.png',
                        sizes: '192x192',
                        type: 'image/png'
                    },
                    {
                        src: '/favicon.png',
                        sizes: '512x512',
                        type: 'image/png'
                    },
                    {
                        src: '/favicon.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'maskable'
                    }
                ]
            }
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
