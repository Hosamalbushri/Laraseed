import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    build: {
        emptyOutDir: true,
    },

    plugins: [
        laravel({
            hotFile: '../../public/base-theme-vite.hot',
            publicDirectory: '../../public',
            buildDirectory: 'themes/base/build',
            input: [
                'assets/css/theme.css',
                '../../packages/Webkul/Web/src/Resources/assets/js/web-interactions.js',
            ],
            refresh: false,
        }),
    ],
});
