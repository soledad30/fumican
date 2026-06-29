import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const basePath = (env.VITE_BASE_PATH || '').replace(/\/$/, '');

    return {
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
                '@': path.resolve(__dirname, 'resources/js'),
                'ziggy': path.resolve(__dirname, 'vendor/tightenco/ziggy/dist/index.esm.js'),
                'ziggy-js': path.resolve(__dirname, 'vendor/tightenco/ziggy/dist/index.js'),
            },
        },
    };
});
