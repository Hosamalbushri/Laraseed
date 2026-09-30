import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { fileURLToPath, URL } from 'node:url';

export default defineConfig({
    root: fileURLToPath(new URL('.', import.meta.url)),

    define: {
        __VUE_OPTIONS_API__: true,
        __VUE_PROD_DEVTOOLS__: false,
        __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: false,
    },

    build: {
        emptyOutDir: true,
    },

    plugins: [
        laravel({
            hotFile: fileURLToPath(new URL('../../public/base-theme-vite.hot', import.meta.url)),
            publicDirectory: '../../public',
            buildDirectory: 'themes/base/build',
            input: [
                fileURLToPath(new URL('./assets/css/theme.css', import.meta.url)),
                fileURLToPath(new URL('../../packages/Webkul/Web/src/Resources/assets/js/web-interactions.js', import.meta.url)),
            ],
            refresh: false,
        }),
    ],
});
