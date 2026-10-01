# LARASEED GENERATOR V3 — G04-B: Optional Web Authentication Hardening

## Executive Summary

Milestone **G04-B** performed a comprehensive security and architectural audit of the Web capability authentication integration, resolving defects identified in initial implementations and hardening all authentication boundaries. 

The generated Web capability now guarantees:
1. **Zero Registration of Protected Routes in Public Mode:** When authentication is disabled (`auth.enabled => false`), protected example routes are completely omitted from the route collection, returning HTTP 404 rather than relying on middleware runtime rejection.
2. **Early Boot-Time Configuration Contract Validation:** Missing or invalid guards, missing provider packages, and missing login route configurations are validated during service provider boot, preventing broken runtime states.
3. **Admin Redirection Isolation via `AuthenticationRedirectResolver`:** When authentication is enabled, Web capabilities register high-priority resolution rules (`priority: 50`) with `AuthenticationRedirectResolver`, ensuring unauthenticated guests on Web routes are directed to the Web login route while preserving Admin's fallback redirection (`priority: -100`) to `admin.session.create`.
4. **Strict POST-Only Logout with CSRF Protection:** All generated Blade header templates and mobile drawers exclusively render `<form method="POST">` logout actions protected by `@csrf`. Incompatible GET logout routes are rejected with actionable diagnostic errors.
5. **No Guard Fallbacks in Account Controllers:** `AccountController` strictly inspects `config('{package_key}_web.auth.guard')`, throwing explicit exceptions if undefined, eliminating any silent fallback to Admin's `user` guard.

---

## 1. Verified Defects & Source Evidence

| Defect ID | Description | Source Location | Resolution |
|---|---|---|---|
| **DEF-01** | Unconditional protected route registration in Public Mode | `stubs/templates/starter/routes_web.php.stub` | Wrapped protected route definition in `if ((bool) config('...auth.enabled', false))` so routes are never registered in public mode. |
| **DEF-02** | Default fallback to `'user'` guard in `AccountController` | `stubs/templates/starter/controller_account.php.stub` | Removed default fallback; strictly validated configured guard against `config('auth.guards')`. |
| **DEF-03** | Permissive GET logout support in configuration and Blade templates | `stubs/templates/starter/config.php.stub`, `header.blade.php.stub` | Removed `logout_method` and GET links; strictly enforced POST forms with `@csrf`. |
| **DEF-04** | Missing integration with `AuthenticationRedirectResolver` | `stubs/templates/starter/provider.php.stub` | Registered scoped resolver rule (priority 50) in `WebServiceProvider` when auth is enabled. |
| **DEF-05** | Late validation of missing or invalid auth parameters | `stubs/templates/starter/provider.php.stub` | Added early validation in `WebServiceProvider::boot()` for guard existence, provider package enablement, and login route definition. |

---

## 2. Hardened Architecture & Configuration Contract

```
                                  +---------------------------------------+
                                  |     Incoming HTTP Request (Guest)     |
                                  +---------------------------------------+
                                                      |
                         +----------------------------+----------------------------+
                         |                                                         |
               [Route: /admin/*]                                      [Route: /acme-portal/*]
                         |                                                         |
         +-------------------------------+                         +-------------------------------+
         |  AuthenticationRedirect-      |                         |  AuthenticationRedirect-      |
         |  Resolver (Priority -100)     |                         |  Resolver (Priority 50)       |
         +-------------------------------+                         +-------------------------------+
                         |                                                         |
             Redirects to Admin Login                                   Redirects to Web Login
            [admin.session.create]                                     [configured named route]
```

### Configuration Contract (`src/Web/Config/web.php`)
```php
return [
    'template' => 'starter',
    'prefix' => 'portal',
    'middleware' => ['web'],

    'auth' => [
        'enabled'          => false,
        'guard'            => null,
        'provider_package' => null,
        'routes'           => [
            'login'  => null,
            'logout' => null, // Must accept POST with CSRF
        ],
    ],

    'navigation' => [ ... ],
];
```

---

## 3. Implementation Details

### A. Route Registration Hardening (`src/Web/Routes/web.php`)
```php
Route::name('acme_portal.web.')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('pages/{page?}', [PageController::class, 'show'])->name('pages.show');

    // Protected routes are only registered when authentication is explicitly enabled
    if ((bool) config('acme_portal_web.auth.enabled', false)) {
        Route::middleware('acme_portal_auth')->group(function () {
            Route::get('account/dashboard', [AccountController::class, 'dashboard'])->name('account.dashboard');
        });
    }
});
```

### B. Boot-Time Validation & Resolver Registration (`WebServiceProvider.php`)
```php
$authConfig = config('acme_portal_web.auth', []);
$authEnabled = (bool) ($authConfig['enabled'] ?? false);

if ($authEnabled) {
    $guard = $authConfig['guard'] ?? null;
    if (! $guard || ! config("auth.guards.{$guard}")) {
        throw new \RuntimeException(sprintf(
            'Authentication configuration error: Package [%s] specifies guard [%s], which is not defined in config/auth.php.',
            'acme_portal',
            $guard ?? 'null'
        ));
    }

    $providerPackage = $authConfig['provider_package'] ?? null;
    if ($providerPackage) {
        $enabledPackages = config('laraseed.optional_packages.enabled', []);
        if (! in_array($providerPackage, $enabledPackages, true)) {
            throw new \RuntimeException(sprintf(
                'Authentication configuration error: Package [%s] requires authentication provider package [%s], which is not currently enabled in LARASEED_OPTIONAL_PACKAGES.',
                'acme_portal',
                $providerPackage
            ));
        }
    }

    $loginRoute = $authConfig['routes']['login'] ?? null;
    if (! $loginRoute) {
        throw new \RuntimeException(sprintf(
            'Authentication configuration error: Package [%s] requires a configured login route when authentication is enabled.',
            'acme_portal'
        ));
    }

    if ($this->app->bound(AuthenticationRedirectResolver::class)) {
        $prefix = (string) config('acme_portal_web.prefix', 'portal');
        $this->app->make(AuthenticationRedirectResolver::class)->register(
            'acme_portal_web',
            function (Request $request) use ($prefix): bool {
                $normalized = trim($prefix, '/');
                return $normalized === '' || $request->is($normalized) || $request->is($normalized . '/*');
            },
            function () use ($loginRoute): string {
                return route($loginRoute);
            },
            50
        );
    }
}
```

### C. Incompatible Logout Route Rejection (`AuthenticateWeb.php`)
```php
$logoutRoute = $authConfig['routes']['logout'] ?? null;
if ($logoutRoute && Route::has($logoutRoute)) {
    $routeInstance = Route::getRoutes()->getByName($logoutRoute);
    if ($routeInstance && ! in_array('POST', $routeInstance->methods(), true)) {
        throw new \RuntimeException(sprintf(
            'Authentication configuration error: Package [%s] configured logout route [%s], but it does not support HTTP POST with CSRF protection.',
            'acme_portal',
            $logoutRoute
        ));
    }
}
```

---

## 4. Verification Results

### Generator Test Suite (`WebPackageGeneratorTest.php`)
- `test_public_mode_allows_unrestricted_guest_access_and_does_not_register_protected_routes`: Verified HTTP 404 on unauthenticated dashboard in public mode.
- `test_auth_mode_guest_is_redirected_to_configured_named_login_route_and_registers_protected_route`: Verified route registration and guest redirection.
- `test_auth_mode_guest_json_request_receives_401_unauthenticated`: Verified JSON 401 response for API requests.
- `test_auth_mode_authenticated_user_accesses_protected_dashboard_route`: Verified session access.
- `test_auth_mode_throws_diagnostic_exception_when_guard_is_undefined`: Verified boot diagnostic.
- `test_auth_mode_throws_diagnostic_exception_when_provider_package_is_not_enabled`: Verified composition diagnostic.
- `test_auth_mode_throws_diagnostic_exception_when_login_route_is_missing`: Verified configuration diagnostic.
- `test_auth_mode_middleware_throws_diagnostic_exception_when_login_route_does_not_exist`: Verified route existence diagnostic.
- `test_auth_mode_header_renders_csrf_post_logout_and_no_get_logout`: Verified CSRF form rendering.
- `test_auth_mode_throws_diagnostic_exception_when_logout_route_does_not_support_post`: Verified rejection of GET-only logout routes.
- `test_admin_auth_isolation_and_redirection_resolver_routing`: Verified priority-based guest redirection between Admin and Web packages.
- `test_multiple_web_packages_with_isolated_auth_configurations`: Verified multi-package isolation.

**Results:** `33 passed (213 assertions, Duration: 4.19s)`

### Full Application Test Suite
**Results:** `375 passed (2953 assertions, Duration: 15.60s, 100% green)`

---

## 5. Scope & Remaining Limitations

1. **Provider Compatibility:** The generator provides structural integration and runtime enforcement. A real external provider package must configure a compatible Eloquent/session guard in `config/auth.php` and expose named login and POST logout routes.
2. **Zero Foundation Mutations:** No changes were made to `packages/Webkul/*` or `packages/Laraseed/Contacts`.

---

## 6. Final Status Indicators

```yaml
GENERATOR_V3_G04B: PASS
PUBLIC_MODE: VERIFIED
AUTH_PROVIDER_VALIDATION: VERIFIED
SECURE_LOGOUT: VERIFIED
ADMIN_AUTH_ISOLATION: VERIFIED
GENERATOR_REGRESSION: PASS
READY_FOR_GENERATOR_V3_G05: YES
```
