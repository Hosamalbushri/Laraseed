# CampusHub Theme Authoring and Asset Build Guide

## 1. Overview

CampusHub implements a multi-layer presentation architecture:
- **`Webkul\Web`**: Owns generic public web runtime, contracts, registries, Blade component APIs (`<x-web::*>`), and the progressive Vue 3 interaction kernel.
- **`Webkul\Theme`**: Theme Engine that discovers themes, handles inheritance, and overrides namespaced Blade views.
- **`themes/*`**: Concrete themes that provide visual design tokens, CSS styling, and optional Blade layout/component overrides.

---

## 2. Directory Structure of a Theme

Every theme lives under `themes/{theme-id}/` and requires at minimum:

```text
themes/{theme-id}/
├── theme.json               # Mandatory manifest
├── package.json             # Build script
├── postcss.config.js        # PostCSS with Tailwind
├── tailwind.config.js       # Content scanner configuration
├── vite.config.js           # Vite build pipeline
├── assets/
│   └── css/
│       └── theme.css        # Design tokens & component styling
└── views/                   # Optional view overrides
    └── overrides/
        └── web/
            ├── layouts/master.blade.php
            └── components/*.blade.php
```

---

## 3. Theme Manifest (`theme.json`)

```json
{
    "id": "my-theme",
    "name": "My Custom Theme",
    "parent": "base",
    "views_path": "views",
    "assets_path": "assets",
    "version": "1.0.0"
}
```

- `id`: Lowercase alphanumeric with hyphens (e.g. `my-theme`).
- `parent`: Optional ID of parent theme for view resolution inheritance.
- `views_path`: Directory for Blade overrides (defaults to `views`).
- `assets_path`: Directory for assets (defaults to `assets`).

---

## 4. Theme Build Configuration (`vite.config.js`)

Each build-capable theme compiles its own CSS plus the generic Web interactions bundle:

```javascript
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
            hotFile: fileURLToPath(new URL('../../public/my-theme-vite.hot', import.meta.url)),
            publicDirectory: '../../public',
            buildDirectory: 'themes/my-theme/build',
            input: [
                fileURLToPath(new URL('./assets/css/theme.css', import.meta.url)),
                fileURLToPath(new URL('../../packages/Webkul/Web/src/Resources/assets/js/web-interactions.js', import.meta.url)),
            ],
            refresh: false,
        }),
    ],
});
```

---

## 5. Tailwind Content Scanning (`tailwind.config.js`)

To ensure optional packages (like `Website`) can be physically removed without breaking the build, Tailwind content paths must safely scan installed views:

```javascript
export default {
    content: {
        relative: true,
        files: [
            './views/**/*.blade.php',
            '../../packages/Webkul/Web/src/Resources/views/**/*.blade.php',
            '../../packages/Webkul/Web/src/Resources/assets/js/**/*.js',
            '../../packages/Webkul/Website/src/Resources/views/**/*.blade.php',
        ],
    },
    theme: {
        extend: {
            maxWidth: {
                content: '72rem',
            },
        },
    },
    plugins: [],
};
```

---

## 6. How to Build & Activate a Theme

1. **Build the Assets**:
   ```bash
   npx vite build --config themes/my-theme/vite.config.js
   ```
   This generates `public/themes/my-theme/build/manifest.json` containing compiled CSS and JS.

2. **Activate the Theme**:
   Update `.env`:
   ```dotenv
   APP_THEME=my-theme
   ```

3. **Verify Runtime**:
   Navigate to the public website. `ResolveWebLocale` middleware will detect `my-theme`, pass the inheritance chain to `ThemeViewFinder`, and load `@vite('assets/css/theme.css', 'themes/my-theme/build')`.
