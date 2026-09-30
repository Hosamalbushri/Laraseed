/** @type {import('tailwindcss').Config} */
export default {
    content: {
        relative: true,
        files: [
            './views/**/*.blade.php',
            '../../packages/Webkul/Web/src/Resources/views/**/*.blade.php',
            '../../packages/Webkul/Web/src/Resources/assets/js/**/*.js',
            '../../packages/Webkul/Theme/src/Resources/views/**/*.blade.php',
            '../../packages/Webkul/Website/src/Resources/views/**/*.blade.php',
        ],
    },
    theme: {
        extend: {
            maxWidth: {
                content: '72rem',
            },
            fontFamily: {
                sans: [
                    'system-ui',
                    '-apple-system',
                    'BlinkMacSystemFont',
                    'Segoe UI',
                    'Noto Sans Arabic',
                    'Noto Sans',
                    'Arial',
                    'sans-serif',
                ],
            },
        },
    },
    plugins: [],
};
