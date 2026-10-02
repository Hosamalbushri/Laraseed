# Laraseed Generator V3 — Troubleshooting & Diagnostic Guide

This guide provides actionable diagnostic procedures and copy-paste solutions for common development, build, routing, authentication, and asset issues encountered in **Laraseed Generator V3**.

---

## Quick Reference Diagnostic Matrix

| Error / Symptom | Primary Cause | Immediate Solution |
| :--- | :--- | :--- |
| `Class "WebServiceProvider" not found` | Stale Composer autoload map | Run `composer dump-autoload` |
| `Route [...] not defined` | Package not enabled in `.env` | Add package ID to `LARASEED_OPTIONAL_PACKAGES` |
| Unstyled HTML / Amber banner in browser | Frontend assets not compiled | Run `npm run build` in the package directory |
| Giant / blown-out SVG icons | Unbuilt CSS without fallback styles | Recompile CSS or verify inline SVG constraints |
| `Route conflict: Package [...] attempted to claim root route [/]` | Two packages configuring `'prefix' => ''` | Ensure only one package claims root `/` |
| `Authentication configuration error: specifies guard [...]` | Guard missing in `config/auth.php` | Declare guard in `config/auth.php` |
| `Authentication configuration error: requires provider package [...]` | Companion auth package is disabled | Add provider package to `LARASEED_OPTIONAL_PACKAGES` |
| `Authentication configuration error: does not support HTTP POST` | Logout route configured as `GET` | Change logout route to `POST` with CSRF |
| Admin redirect hijacked by Web package | Resolver route name or prefix collision | Ensure package uses `{package_key}.web.*` routes |
| PHP Built-in Server 404 on package routes | `server.php` directory check vs router rewrite | Ensure `server.php` checks `is_file()` |
| Active nav link not highlighted | Parameter or route mismatch in `config/web.php` | Match `route` and `params` with current view |
| Arabic text rendered LTR / Latin font | Missing `dir="rtl"` or Cairo font definition | Ensure `app()->getLocale() === 'ar'` and CSS loaded |

---

## Detailed Troubleshooting Procedures

---

### 1. `Class "Vendor\Package\Web\Providers\WebServiceProvider" not found`

#### Symptom
When accessing the website or running `php artisan`, Laravel throws `Class "..." not found`.

#### Root Cause
Composer's PSR-4 autoload map was not updated after creating the new package or capability.

#### Solution
1. Verify root `composer.json` contains the package namespace:
   ```json
   "autoload": {
       "psr-4": {
           "Vendor\\Package\\": "packages/Vendor/Package/src"
       }
   }
   ```
2. Regenerate Composer's class map:
   ```bash
   composer dump-autoload
   ```

---

### 2. `Route [vendor_package.web.home] not defined`

#### Symptom
Blade view or controller throws `RouteNotFoundException`.

#### Root Cause
1. The package ID is missing from `LARASEED_OPTIONAL_PACKAGES` in `.env`.
2. Or the route lookup table was cached prior to package generation.

#### Solution
1. Check `.env`:
   ```ini
   LARASEED_OPTIONAL_PACKAGES=contacts,vendor_package
   ```
2. Clear cached routes:
   ```bash
   php artisan route:clear
   php artisan config:clear
   ```

---

### 3. Unstyled HTML / Amber Diagnostic Banner

#### Symptom
The webpage loads plain HTML without Tailwind CSS, and an amber alert banner appears:  
`[Laraseed Diagnostic] Frontend assets for ... are not built.`

#### Root Cause
The package Vite build has not been executed, and neither a `.hot` file nor a production `manifest.json` exists in `public/`.

#### Solution
Navigate to the package directory and compile the assets:
```bash
cd packages/Vendor/Package
npm install
npm run build
```
For local development with hot reload:
```bash
npm run dev
```

---

### 4. `Route conflict: Package [B] attempted to claim the root route [/], but it is already owned by [A]`

#### Symptom
Application fails to boot with a `\RuntimeException` mentioning root route ownership.

#### Root Cause
Two Web packages both configured `'prefix' => ''` in `src/Web/Config/web.php`. Laraseed enforces single-package root route ownership to prevent silent route overwrites.

#### Solution
Decide which package is the primary website. Set `'prefix' => ''` on the primary package, and give all other packages unique prefixes (e.g. `'prefix' => 'portal'` or `'prefix' => 'store'`).

---

### 5. `Authentication configuration error: Package [...] specifies guard [...], which is not defined in config/auth.php`

#### Symptom
Accessing a protected Web route throws a `RuntimeException`.

#### Root Cause
The guard declared in `config/web.php` (e.g. `'guard' => 'customer'`) has not been registered in root `config/auth.php`.

#### Solution
Add the guard to `config/auth.php`:
```php
'guards' => [
    'customer' => [
        'driver'   => 'session',
        'provider' => 'customers',
    ],
],
```

---

### 6. `Authentication configuration error: Package [...] configured logout route [...], but it does not support HTTP POST with CSRF protection`

#### Symptom
Accessing an authenticated Web page throws a `RuntimeException` during auth middleware handling.

#### Root Cause
The configured `routes.logout` route only accepts HTTP `GET`. Laraseed strictly requires `POST` logout for CSRF security.

#### Solution
Update your routes file to accept `POST`:
```php
// In Routes/web.php or auth routes file:
Route::post('logout', [AuthController::class, 'logout'])->name('customer.logout');
```

---

### 7. Admin Login Redirect Hijacked by Web Package

#### Symptom
An unauthenticated request to `/admin/*` redirects to the Web package login route instead of `admin.session.create`.

#### Root Cause
The Web package resolver rule matched URL paths indiscriminately on root-mounted packages without verifying route ownership.

#### Solution
Ensure the package `WebServiceProvider` implements the hardened route ownership check:
```php
$this->app->make(AuthenticationRedirectResolver::class)->register(
    '{{ PACKAGE_KEY }}_web',
    function (Request $request) use ($prefix): bool {
        $route = $request->route();

        if ($route) {
            $name = (string) $route->getName();
            if ($name !== '' && str_starts_with($name, '{{ PACKAGE_KEY }}.web.')) {
                return true;
            }

            $middleware = is_array($route->middleware()) ? $route->middleware() : [];
            if (in_array('{{ PACKAGE_KEY }}_auth', $middleware, true)) {
                return true;
            }

            return false;
        }

        $normalized = trim($prefix, '/');
        if ($normalized !== '') {
            return $request->is($normalized) || $request->is($normalized . '/*');
        }

        return false;
    },
    fn () => route($loginRoute),
    50
);
```

---

### 8. PHP Built-in Server 404 on Package Routes

#### Symptom
When serving the app using `php -S localhost:8000 server.php`, visiting `/acme-portal` returns a 404 error, but `/` works.

#### Root Cause
PHP's built-in web server treats existing directory paths as physical files before rewriting to `index.php`. If a directory named `acme-portal` exists in `public/`, the server bypasses Laravel's router.

#### Solution
Ensure the root `server.php` router script checks `is_file()` specifically rather than `file_exists()` before serving files directly:
```php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');

if ($uri !== '/' && is_file(__DIR__.'/public'.$uri)) {
    return false;
}

require_once __DIR__.'/public/index.php';
```
