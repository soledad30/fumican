import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig(({ mode }) => {
    const isProduction = mode === 'production';

    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js'],
                refresh: true,
                detectTls: false,
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
                '@': '/resources/js',
                'ziggy': path.resolve(__dirname, 'vendor/tightenco/ziggy/dist/index.esm.js'),
                'ziggy-js': path.resolve(__dirname, 'vendor/tightenco/ziggy/dist/index.js'),
            },
        },
        // Cambiar a ruta relativa
        base: './',  // Esto usa rutas relativas

        server: isProduction ? undefined : {
            host: '0.0.0.0',
            port: 5173,
            strictPort: false,
            cors: {
                origin: true,
                credentials: true,
            },
            hmr: {
                host: 'localhost',
                port: 5173,
            },
        },

        build: {
            manifest: true,
            outDir: 'public/build',
            emptyOutDir: true,
            rollupOptions: {
                input: {
                    app: 'resources/js/app.js',
                    css: 'resources/css/app.css',
                },
            },
        },
    };
});
