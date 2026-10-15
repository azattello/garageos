import { defineConfig } from 'vite';
import path from 'node:path';
import os from 'node:os';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

const wifiAddress = Object.entries(os.networkInterfaces())
    .find(([name]) => /wi-?fi|wireless|беспроводн/i.test(name))?.[1]
    ?.find(({ family, internal }) => family === 'IPv4' && !internal)?.address;

export default defineConfig({
    server: {
        host: '0.0.0.0',
        ...(wifiAddress && {
            origin: `http://${wifiAddress}:5173`,
            hmr: { host: wifiAddress },
        }),
        cors: {
            origin: [
                'http://localhost:8010',
                'http://127.0.0.1:8010',
                ...(wifiAddress ? [`http://${wifiAddress}:8010`] : []),
            ],
        },
    },
    resolve: {
        alias: {
            '@': path.resolve(import.meta.dirname, 'resources/js'),
        },
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) {
                        return;
                    }

                    if (id.includes('react') || id.includes('scheduler') || id.includes('@inertiajs')) {
                        return 'react-core';
                    }

                    if (id.includes('lucide-react')) {
                        return 'icons';
                    }

                    if (id.includes('clsx') || id.includes('tailwindcss') || id.includes('@tailwindcss')) {
                        return 'ui-core';
                    }

                    return 'vendor';
                },
            },
        },
    },
    plugins: [
        react(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
