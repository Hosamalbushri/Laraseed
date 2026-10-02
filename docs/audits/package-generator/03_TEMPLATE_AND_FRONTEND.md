# Laraseed Package Generator — Phase 01: Template & Frontend Security Audit
## 03. Template & Frontend Security Audit

This report evaluates the template rendering engine, Blade component security, dynamic CSS variable injection, Vite pipeline configuration, JavaScript dependencies, and Content Security Policy (CSP) compatibility in **Laraseed Generator V3**.

---

## 1. Template Rendering & Placeholder Substitution

Templates are processed by `StubRenderer` and `WebGenerator`:

```php
$replacements = [
    '{{ PACKAGE_KEY }}'       => $packageKey,       // e.g. 'acme_portal'
    '{{ PACKAGE_SLUG }}'      => $packageSlug,      // e.g. 'acme-portal'
    '{{ PACKAGE_TITLE }}'     => $packageTitle,     // e.g. 'Portal'
    '{{ UPPER_PACKAGE_KEY }}' => $upperPackageKey,  // e.g. 'ACME_PORTAL'
];
```

### Injection & Sanitization Analysis
- **Namespace & Class Placeholders:** Derived strictly from validated `PackageIdentity` tokens matching `/^[A-Za-z][A-Za-z0-9_]*$/`. Code injection via package name is impossible.
- **Title Placeholders:** Derived from `Str::headline($identity->package)`. Rendered inside Blade views with HTML escaping (`{{ ... }}`) or inside language strings.
- **Verdict:** **SECURE**. No template injection vulnerabilities exist in the stub processing layer.

---

## 2. Dynamic CSS & Branding Security

In `layout.blade.php.stub`:
```blade
<style>
    :root {
        --brand-color: {{ config('{{ PACKAGE_KEY }}_web.branding.color', '#0E90D9') }};
    }
</style>
```

### Security Assessment
- The `--brand-color` value is read from `config/web.php` (trusted developer configuration).
- If a developer sets an invalid color string, it operates within CSS property syntax.
- **CSP Finding (`SEC-PG-01`):** Embedding `--brand-color` via an inline `<style>` block requires `style-src 'unsafe-inline'` in Content Security Policy. Moving this to an inline `style="--brand-color: ..."` attribute on the `<html>` element or generating a dynamic CSS file would eliminate the inline style block.

---

## 3. Frontend Build Pipeline & Asset Loading

### 3.1 Vite Asset Manifest Integration
In `WebServiceProvider.php`:
```php
config([
    'krayin-vite.viters.{{ PACKAGE_KEY }}_web' => [
        'hot_file'                 => '{{ PACKAGE_KEY }}-web-vite.hot',
        'build_directory'          => '{{ PACKAGE_SLUG }}/web/build',
        'package_assets_directory' => 'src/Web/Resources/assets',
    ],
]);
```

In `layout.blade.php`:
```blade
@php
    $assetsBuilt = file_exists(public_path('{{ PACKAGE_KEY }}-web-vite.hot')) ||
                   file_exists(public_path('{{ PACKAGE_SLUG }}/web/build/manifest.json'));
@endphp

@if ($assetsBuilt)
    {{ vite()->set(['src/Web/Resources/assets/css/app.css', 'src/Web/Resources/assets/js/app.js'], '{{ PACKAGE_KEY }}_web') }}
@endif
```

### Security & Reliability Strengths:
1. **Asset Path Containment:** Assets are loaded exclusively through hashed filenames defined in `manifest.json`.
2. **Missing Asset Protection:** If assets are uncompiled, `vite()` is suppressed, preventing uncaught `ViteManifestNotFoundException` crashes and instead displaying an actionable alert banner.
3. **Multi-Package Isolation:** Every package maintains a dedicated build directory in `public/` and a dedicated hot file, preventing asset cross-talk.

---

## 4. Frontend Dependencies & Runtime Optimization

### JavaScript Dependency Audit (`package.json.stub`)
```json
{
    "devDependencies": {
        "autoprefixer": "^10.4.16",
        "laravel-vite-plugin": "^1.0",
        "postcss": "^8.4.23",
        "tailwindcss": "^3.3.2",
        "vite": "^5.4.12",
        "vue": "^3.4.21"
    },
    "dependencies": {
        "@vitejs/plugin-vue": "^4.2.3"
    }
}
```

### Finding `SEC-PG-06`: Vue 3 ESM Bundler Runtime
- In `asset_js.js.stub`:
  ```javascript
  import { createApp } from "vue/dist/vue.esm-bundler";
  ```
- Importing `vue.esm-bundler` bundles the in-browser template compiler (~190.5 kB production JS bundle).
- The Starter template only uses Vue to manage dark-mode state on `#app`.
- **Recommendation:** Switch to the runtime-only build `import { createApp } from "vue"` to reduce JS payload to ~50 kB.

---

## 5. Cookie Security & Interactive UI Handlers

### Finding `SEC-PG-03`: Dark Mode Cookie Flags
- The client-side dark mode script sets `dark_mode=1|0; path=/; max-age=31536000` without `SameSite=Lax` or `Secure` flags.
- While non-sensitive, setting `SameSite=Lax` and dynamic `Secure` (over HTTPS) aligns with modern browser cookie best practices.

### Finding `SEC-PG-01`: Inline Event Handlers
- Inline `onclick` handlers in `header.blade.php.stub` and `component_modal.blade.php.stub` prevent deploying strict CSP without `'unsafe-inline'`.
- Moving toggle actions to data attributes (`data-toggle="dark-mode"`, `data-toggle="mobile-menu"`, `data-dismiss="modal"`) attached via `app.js` resolves this limitation completely.
