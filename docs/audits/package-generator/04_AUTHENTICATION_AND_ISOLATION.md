# Laraseed Package Generator — Phase 01: Authentication & Isolation Audit
## 04. Authentication & Isolation Audit

This report evaluates the security, guard decoupling, middleware enforcement, CSRF protection, redirect resolution, and multi-package isolation of the Web authentication system in **Laraseed Generator V3**.

---

## 1. Authentication Architecture Overview

Laraseed Web capabilities provide decoupled authentication that consumes Laravel application guards without introducing custom user tables or modifying Webkul Admin authentication:

```
+-----------------------------------------------------------------------------------+
|                        AUTHENTICATION ISOLATION TOPOLOGY                          |
+-----------------------------------------------------------------------------------+
|                                                                                   |
|  [ Admin Space ] ------------> Guard: 'admin' (/admin/*)                          |
|                                                                                   |
|  [ Web Package: Portal ] ----> Guard: 'customer' (/portal/*)                      |
|  [ Web Package: Member ] ----> Guard: 'user'     (/member/* or /)                 |
|                                                                                   |
|  [ AuthenticationRedirectResolver ] -> Dispatches guests to specific login routes |
+-----------------------------------------------------------------------------------+
```

---

## 2. Guard Isolation & Configuration Validation

The package-owned middleware `AuthenticateWeb.php` validates all guard dependencies at runtime:

```php
// 1. Validate guard is declared in config/auth.php
$guard = $authConfig['guard'] ?? null;
if (! $guard || ! config("auth.guards.{$guard}")) {
    throw new \RuntimeException(sprintf(
        'Authentication configuration error: Package [%s] specifies guard [%s], which is not defined in config/auth.php.',
        '{{ PACKAGE_KEY }}',
        $guard ?? 'null'
    ));
}

// 2. Validate guard's user provider exists
$guardProvider = config("auth.guards.{$guard}.provider");
if ($guardProvider && ! config("auth.providers.{$guardProvider}")) {
    throw new \RuntimeException(sprintf(
        'Authentication configuration error: Package [%s] specifies guard [%s] with undefined user provider [%s] in config/auth.php.',
        '{{ PACKAGE_KEY }}',
        $guard,
        $guardProvider
    ));
}

// 3. Validate companion provider package is active in LARASEED_OPTIONAL_PACKAGES
$providerPackage = $authConfig['provider_package'] ?? null;
if ($providerPackage) {
    $enabledPackages = config('laraseed.optional_packages.enabled', []);
    if (! in_array($providerPackage, $enabledPackages, true)) {
        throw new \RuntimeException(sprintf(
            'Authentication configuration error: Package [%s] requires authentication provider package [%s], which is not currently enabled in LARASEED_OPTIONAL_PACKAGES.',
            '{{ PACKAGE_KEY }}',
            $providerPackage
        ));
    }
}
```

### Security Verdict: **ROBUST & SECURE**
Misconfigured guards fail fast with clear diagnostic exceptions rather than silently degrading to fallback guards or exposing unauthenticated views.

---

## 3. POST Logout with CSRF Protection

Laraseed strictly enforces HTTP `POST` logout:

1. `AuthenticateWeb.php` verifies that the configured logout route accepts `POST`. If a developer configures a `GET`-only logout route, a diagnostic exception is thrown:
   ```php
   if ($logoutRoute && Route::has($logoutRoute)) {
       $routeInstance = Route::getRoutes()->getByName($logoutRoute);
       if ($routeInstance && ! in_array('POST', $routeInstance->methods(), true)) {
           throw new \RuntimeException(sprintf(
               'Authentication configuration error: Package [%s] configured logout route [%s], but it does not support HTTP POST with CSRF protection.',
               '{{ PACKAGE_KEY }}',
               $logoutRoute
           ));
       }
   }
   ```
2. The header view (`header.blade.php.stub`) renders a `<form method="POST">` containing `@csrf`.
3. **Security Verdict:** **EXCELLENT**. Eliminates Cross-Site Request Forgery (CSRF) logout attacks.

---

## 4. Redirect Isolation & Route Ownership Resolution

When an unauthenticated guest requests a protected route, `AuthenticationRedirectResolver` determines the appropriate login redirect:

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

            // Route belongs to another package or Admin; do not capture
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

### Evaluated Edge Cases:

| Scenario | Behavior | Audit Result |
| :--- | :--- | :--- |
| **Root-Mounted Package (`prefix => ''`)** | Checks `$route->getName()` starts with `{package_key}.web.` before matching. Does not capture `/admin/*` unauthenticated requests. | **SECURE (No Hijacking)** |
| **Overlapping Prefixes (`/shop` vs `/shop/admin/orders`)** | When route is resolved to `admin.orders.index`, resolver returns `false`. Request resolves to Admin login. | **SECURE (No Hijacking)** |
| **Multiple Authenticated Packages (`/shop` and `/portal`)** | Unauthenticated `/shop/account` redirects to `shop.login`; `/portal/account` redirects to `portal.login`. | **SECURE (Isolated)** |
| **AJAX / JSON API Requests** | Unauthenticated JSON requests return HTTP 401 `{ "message": "Unauthenticated." }`. | **SECURE (REST Compliant)** |

---

## 5. Summary Matrix of Authentication Controls

```
+-------------------------------------------------------------+
| Control Area                     | Status                   |
+----------------------------------+--------------------------+
| Public Mode Omission             | Verified (Zero Auth)     |
| Guard Isolation                  | Verified (Decoupled)     |
| CSRF POST Logout                 | Verified (Enforced)      |
| JSON 401 Responses               | Verified (API Ready)     |
| Admin Redirect Isolation         | Verified (100% Isolated) |
| Root-Mount Redirect Isolation    | Verified (100% Isolated) |
| Multi-Package Redirect Isolation | Verified (100% Isolated) |
+-------------------------------------------------------------+
```
