# Laraseed Web Starter — Developer Guide

This guide provides the authoritative reference for generating, configuring, styling, and customizing Web capabilities using the **Laraseed Web Starter Template** (Generator V3).

---

## 1. Quick Start: Scaffolding a Web Package

Generating a complete, modular, standalone Web capability within Laraseed requires two standard commands.

### Step 1: Generate Base Package Skeleton
```bash
php artisan laraseed:make-package Vendor/PackageName
```
*Example:*
```bash
php artisan laraseed:make-package Acme/Portal
```

### Step 2: Add Web Capability (Starter Template)
```bash
php artisan laraseed:make-web Acme/Portal --template=starter
```
*Note:* `--template=starter` is the default and can be omitted.

### Step 3: Register Autoloading
In the root `composer.json`, map the PSR-4 namespace of your new package:
```json
"autoload": {
    "psr-4": {
        "Acme\\Portal\\": "packages/Acme/Portal/src"
    }
}
```
Then regenerate the Composer autoload map:
```bash
composer dump-autoload
```

### Step 4: Activate Package in Laraseed
Enable your package ID in `.env` (or via `config/laraseed.php`):
```ini
LARASEED_OPTIONAL_PACKAGES=contacts,acme_portal
```

---

## 2. Compiling Frontend Assets

Each Web package is equipped with its own Vite build pipeline independent of Admin assets.

### Installing Dependencies
```bash
cd packages/Acme/Portal
npm install
```

### Development (Hot Module Replacement)
```bash
npm run dev
```

### Production Build
```bash
npm run build
```
Compiled production assets are output to `public/acme-portal/web/build/` and automatically loaded by the package layout via `manifest.json`.

---

## 3. Configuration & Routing

The package configuration resides at `packages/Acme/Portal/src/Web/Config/web.php`.

```php
return [
    'template' => 'starter',

    // Route prefix for all web pages (e.g. /portal)
    // Set to '' (empty string) to claim root domain route [/]
    'prefix' => 'acme-portal',

    // Middleware stack applied to package web routes
    'middleware' => ['web'],

    // Visual Branding
    'branding' => [
        'name'  => 'Acme Portal',
        'color' => '#0E90D9',
        'logo'  => null, // e.g. '/images/acme-logo.svg'
    ],

    // Optional Authentication
    'auth' => [
        'enabled'          => false,
        'guard'            => null,
        'provider_package' => null,
        'routes'           => [
            'login'  => null,
            'logout' => null,
        ],
    ],

    // Navigation Menu Items
    'navigation' => [
        'home' => [
            'name'  => 'acme_portal_web::app.web.home',
            'route' => 'acme_portal.web.home',
            'sort'  => 1,
        ],
        'about' => [
            'name'   => 'acme_portal_web::app.web.about',
            'route'  => 'acme_portal.web.pages.show',
            'params' => ['page' => 'about'],
            'sort'   => 2,
        ],
    ],
];
```

---

## 4. Reusable Blade Components

The Starter template includes 6 foundational UI components located under `src/Web/Resources/views/components/` and namespaced as `<x-{{ package_key }}_web::...>`:

### 1. Container (`<x-acme_portal_web::container>`)
Provides responsive horizontal padding and maximum width constraint.
```blade
<x-acme_portal_web::container class="py-12">
    <p>Constrained responsive content.</p>
</x-acme_portal_web::container>
```

### 2. Section (`<x-acme_portal_web::section>`)
Wraps structured page blocks with optional titles and subtitles.
```blade
<x-acme_portal_web::section title="Key Highlights" subtitle="Discover what sets our platform apart">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Cards / Columns -->
    </div>
</x-acme_portal_web::section>
```

### 3. Card (`<x-acme_portal_web::card>`)
Surface card container supporting light/dark themes and hover elevation.
```blade
<x-acme_portal_web::card title="Statistics Overview" subtitle="Updated every 15 minutes">
    <p class="text-3xl font-bold text-gray-900 dark:text-white">1,240</p>
</x-acme_portal_web::card>
```

### 4. Button (`<x-acme_portal_web::button>`)
Versatile button/link component supporting variants, sizes, and brand palette styling.
- **Variants:** `primary` (uses `--brand-color`), `secondary`, `outline`, `danger`
- **Sizes:** `sm`, `md`, `lg`
```blade
<!-- Primary Button -->
<x-acme_portal_web::button variant="primary" size="md" type="submit">
    Save Changes
</x-acme_portal_web::button>

<!-- Link Button -->
<x-acme_portal_web::button :href="route('acme_portal.web.pages.show', ['page' => 'pricing'])" variant="outline" size="sm">
    View Pricing
</x-acme_portal_web::button>
```

### 5. Modal Dialog (`<x-acme_portal_web::modal>`)
Accessible popup modal with backdrop and dismiss controls.
```blade
<x-acme_portal_web::modal id="terms-modal" title="Terms & Conditions">
    <p class="text-sm text-gray-600 dark:text-gray-300">Terms content...</p>
</x-acme_portal_web::modal>

<!-- Trigger Button -->
<button onclick="document.getElementById('terms-modal').classList.remove('hidden')">
    Open Terms
</button>
```

### 6. Form Control Group (`<x-acme_portal_web::form.control-group>`)
Field group wrapper with automatic label, required asterisk, and validation error message.
```blade
<x-acme_portal_web::form.control-group name="email" label="Email Address" required>
    <input type="email"
           name="email"
           id="email"
           value="{{ old('email') }}"
           class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:bg-gray-800 dark:border-gray-700 dark:text-white"
           placeholder="user@example.com" />
</x-acme_portal_web::form.control-group>
```

---

## 5. Adding Custom Pages

Adding a new page is completely native to Laravel:

1. **Create the View:**  
   `src/Web/Resources/views/pages/pricing.blade.php`:
   ```blade
   <x-acme_portal_web::layouts title="Pricing">
       <x-acme_portal_web::section title="Pricing Plans" subtitle="Select the best plan for you.">
           <x-acme_portal_web::card>
               <h3 class="text-xl font-bold">Standard Plan</h3>
               <p class="mt-2">$29 / month</p>
           </x-acme_portal_web::card>
       </x-acme_portal_web::section>
   </x-acme_portal_web::layouts>
   ```

2. **Register Route:**  
   `src/Web/Routes/web.php`:
   ```php
   Route::get('pricing', function () {
       return view('acme_portal_web::pages.pricing');
   })->name('pricing');
   ```

3. **Add Navigation Item:**  
   `src/Web/Config/web.php`:
   ```php
   'pricing' => [
       'name'   => 'acme_portal_web::app.web.pricing',
       'route'  => 'acme_portal.web.pricing',
       'sort'   => 3,
   ],
   ```

4. **Add Translation Keys:**  
   `src/Web/Resources/lang/en/app.php`:
   ```php
   'pricing' => 'Pricing',
   ```
   `src/Web/Resources/lang/ar/app.php`:
   ```php
   'pricing' => 'الأسعار',
   ```

---

## 6. Optional Authentication Integration

When your website needs user authentication, integrate an existing guard without building custom auth infrastructure.

### Enabling Authentication in `config/web.php`:
```php
'auth' => [
    'enabled'          => true,
    'guard'            => 'customer', // Guard configured in config/auth.php
    'provider_package' => null,       // Optional provider package ID
    'routes'           => [
        'login'  => 'customer.login',  // Named login route
        'logout' => 'customer.logout', // Named logout route (POST + CSRF)
    ],
],
```

### Route Protection:
Apply the package-owned middleware alias `{{ package_key }}_auth`:
```php
Route::middleware('acme_portal_auth')->group(function () {
    Route::get('account/dashboard', [AccountController::class, 'dashboard'])->name('account.dashboard');
});
```

### Multi-Package & Admin Isolation Guarantee:
Laraseed's `AuthenticationRedirectResolver` strictly checks route names and middleware ownership. Unauthenticated requests to `/admin/*` will always redirect to Admin login, while requests to `/portal/account/*` redirect to the portal login route, even when both share URL prefixes or root domains.

---

## 7. Troubleshooting & Best Practices

| Issue | Cause | Solution |
| :--- | :--- | :--- |
| `Class "WebServiceProvider" not found` | Composer autoload cache stale | Run `composer dump-autoload` after generating package. |
| `Route [...] not defined` | Package not enabled in environment | Add package ID to `LARASEED_OPTIONAL_PACKAGES` in `.env`. |
| Unstyled HTML in browser | Assets not compiled for production | Run `npm run build` in the package directory. |
| PHP Built-in Server 404 on package route | Built-in server checks directory before router | Ensure project root `server.php` checks `is_file()` before rewriting. |
