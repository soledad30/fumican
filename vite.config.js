import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path';

const basePath = (process.env.VITE_BASE_PATH || '').replace(/\/$/, '');

export default defineConfig({
    base: basePath ? `${basePath}/build/` : '/build/',
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
            'ziggy': path.resolve(__dirname, 'vendor/tightenco/ziggy/dist/index.esm.js'),
            'ziggy-js': path.resolve(__dirname, 'vendor/tightenco/ziggy/dist/index.js'),
        },
    },
});
