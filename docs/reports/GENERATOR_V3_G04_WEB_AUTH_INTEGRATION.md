# LARASEED GENERATOR V3 — G04: Optional Web Authentication Integration

## Executive Summary

Laraseed Generator V3 Milestone **G04** implements a lightweight, modular, and developer-configurable authentication integration for generated Web packages. Generated packages default to **Public Mode** (unrestricted access with zero authentication overhead), while offering seamless, declaratively configured **Authenticated Mode** when paired with an existing authentication provider or custom guard.

This implementation strictly preserves **complete isolation from Admin authentication** (`Bouncer` ACL and Admin session guards are never leaked to the public web surface), maintains package ownership over routes, middleware, and views, and provides explicit diagnostic exceptions whenever configuration discrepancies are detected.

---

## 1. Architectural Design & Philosophy

```
+-----------------------------------------------------------------------------------+
|                            Web Capability Runtime                                 |
+-----------------------------------------------------------------------------------+
|                                                                                   |
|  [Public Mode (Default)]                    [Authenticated Mode]                  |
|  * auth.enabled = false                     * auth.enabled = true                 |
|  * Unrestricted public routes               * Guard: 'customer' / 'user' / etc.  |
|  * Zero auth queries/session checks         * Provider: optional package check    |
|  * Clean header (no login/logout noise)     * Login/Logout: named routes          |
|                                                                                   |
|  +---------------------------+             +-----------------------------------+  |
|  | Public Routes             |             | Protected Routes                  |  |
|  | - [GET /]                 |             | - [GET /account/dashboard]        |  |
|  | - [GET /pages/{page}]     |             | Middleware: {pkg}_auth            |  |
|  +---------------------------+             +-----------------------------------+  |
|                                                                                   |
+-----------------------------------------------------------------------------------+
```

### Key Principles
1. **Public by Default:** A generated Web package works out-of-the-box without requiring user authentication, database tables, or auth providers.
2. **Zero Admin Coupling:** Web capabilities do not rely on Webkul Admin's `Bouncer` middleware, admin login routes (`admin.session.create`), or backend ACL roles.
3. **Package-Owned Middleware:** Each package registers its own route middleware alias (`{package_key}_auth`) pointing to its package-owned `AuthenticateWeb` middleware.
4. **Declarative Configuration Contract:** All auth settings are isolated in `src/Web/Config/web.php` and loaded dynamically via config merge.
5. **Robust Diagnostics:** Clear, actionable `RuntimeException` exceptions guide developers when guards are missing, routes are invalid, or provider packages are disabled.

---

## 2. Configuration Contract

The package-owned configuration at `src/Web/Config/web.php` exposes the following contract:

```php
return [
    'template' => 'starter',
    'prefix' => 'portal',
    'middleware' => ['web'],

    /**
     * Optional Web Authentication Configuration.
     * Default: false (Public website mode).
     */
    'auth' => [
        /**
         * Enable or disable authentication integration.
         */
        'enabled' => false,

        /**
         * The authentication guard configured in config/auth.php.
         * Example: 'customer', 'member', 'portal', 'user'.
         */
        'guard' => null,

        /**
         * Optional Laraseed package ID providing authentication.
         * Example: 'customer', 'members'.
         */
        'provider_package' => null,

        /**
         * Named routes for login and logout actions.
         */
        'routes' => [
            'login'  => null,
            'logout' => null,
        ],

        /**
         * Supported HTTP method for logout ('POST' or 'GET').
         */
        'logout_method' => 'POST',
    ],

    'navigation' => [ ... ],
];
```

---

## 3. Package-Owned Middleware Implementation

The generated middleware at `src/Web/Http/Middleware/AuthenticateWeb.php` validates the configuration and handles authentication enforcement:

```php
namespace Acme\Portal\Web\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class AuthenticateWeb
{
    public function handle(Request $request, Closure $next)
    {
        $authConfig = config('acme_portal_web.auth', []);
        $enabled = (bool) ($authConfig['enabled'] ?? false);

        if (! $enabled) {
            throw new \RuntimeException(
                'Authentication is disabled for package [acme_portal]. To protect routes, configure and enable auth in config/acme_portal_web.php.'
            );
        }

        // Verify provider package availability if specified
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

        // Verify guard definition in config/auth.php
        $guard = $authConfig['guard'] ?? null;
        if (! $guard || ! config("auth.guards.{$guard}")) {
            throw new \RuntimeException(sprintf(
                'Authentication configuration error: Package [%s] specifies guard [%s], which is not defined in config/auth.php.',
                'acme_portal',
                $guard ?? 'null'
            ));
        }

        // Enforce authentication check
        if (! auth()->guard($guard)->check()) {
            $loginRoute = $authConfig['routes']['login'] ?? null;
            if (! $loginRoute || ! Route::has($loginRoute)) {
                throw new \RuntimeException(sprintf(
                    'Authentication configuration error: Package [%s] requires a valid named login route, but route [%s] is not defined.',
                    'acme_portal',
                    $loginRoute ?? 'null'
                ));
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->guest(route($loginRoute));
        }

        return $next($request);
    }
}
```

---

## 4. Protected Routes & Account Controller

### Route Registration (`src/Web/Routes/web.php`)
```php
Route::name('acme_portal.web.')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('pages/{page?}', [PageController::class, 'show'])->name('pages.show');

    // Example protected route guarded by package-owned auth middleware
    Route::middleware('acme_portal_auth')->group(function () {
        Route::get('account/dashboard', [AccountController::class, 'dashboard'])->name('account.dashboard');
    });
});
```

### Account Controller (`src/Web/Http/Controllers/AccountController.php`)
```php
namespace Acme\Portal\Web\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AccountController extends Controller
{
    public function dashboard(Request $request): View
    {
        $guard = config('acme_portal_web.auth.guard', 'user');
        $user = auth()->guard($guard)->user();

        return view('acme_portal_web::account.dashboard', [
            'user' => $user,
        ]);
    }
}
```

---

## 5. Dynamic UI & Blade Header Integration

The header Blade component (`src/Web/Resources/views/components/layouts/header/index.blade.php`) dynamically responds to the auth configuration state:
- **Public Mode (`auth.enabled = false`):** No auth buttons or clutter are rendered.
- **Guest State (`auth.enabled = true`, Unauthenticated):** Displays a call-to-action Login link pointing to the configured named login route.
- **Authenticated State (`auth.enabled = true`, Authenticated):** Displays a link to the user dashboard (`account/dashboard`), user identity badge, and a CSRF-protected logout button (or GET link if configured).
- **Mobile Responsive Menu Drawer:** Full mirror parity of auth actions for mobile navigation.

---

## 6. Localization Parity

Arabic (`ar`) and English (`en`) language catalogs include 100% key parity for all authentication strings:

| Key | English (`en`) | Arabic (`ar`) |
|---|---|---|
| `web.auth.login` | Login | تسجيل الدخول |
| `web.auth.logout` | Logout | تسجيل الخروج |
| `web.auth.account` | Account | الحساب |
| `web.auth.dashboard` | Dashboard | لوحة التحكم |

---

## 7. Verification & Test Suite

Comprehensive automated test coverage was added to `tests/Feature/Laraseed/WebPackageGeneratorTest.php`:

| Test Name | Verified Behavior | Status |
|---|---|---|
| `test_make_web_generates_package_owned_web_capability_skeleton` | Asserts generation of `AuthenticateWeb.php`, `AccountController.php`, and `dashboard.blade.php`. | PASS |
| `test_arabic_and_english_translations_have_exact_parity` | Asserts exact key parity for `web.auth` across `en` and `ar`. | PASS |
| `test_public_mode_allows_unrestricted_guest_access_without_auth` | Verifies public routes load without auth and header remains clean. | PASS |
| `test_auth_mode_guest_is_redirected_to_configured_named_login_route` | Verifies unauthenticated guest is redirected to named login route. | PASS |
| `test_auth_mode_guest_json_request_receives_401_unauthenticated` | Verifies unauthenticated JSON API request receives HTTP 401. | PASS |
| `test_auth_mode_authenticated_user_accesses_protected_dashboard_route` | Verifies logged-in user can access guarded dashboard and sees profile info. | PASS |
| `test_auth_mode_middleware_throws_diagnostic_exception_when_auth_is_disabled` | Verifies `RuntimeException` with actionable advice if protected route accessed while auth is disabled. | PASS |
| `test_auth_mode_middleware_throws_diagnostic_exception_when_guard_is_undefined` | Verifies diagnostic error on invalid or missing guard in `config/auth.php`. | PASS |
| `test_auth_mode_middleware_throws_diagnostic_exception_when_provider_package_is_not_enabled` | Verifies diagnostic error when provider package is not enabled in composition. | PASS |
| `test_auth_mode_middleware_throws_diagnostic_exception_when_login_route_is_missing` | Verifies diagnostic error when named login route does not exist. | PASS |
| `test_auth_mode_header_renders_login_link_for_guest_and_csrf_logout_for_authenticated_user` | Verifies header displays login link for guest and CSRF logout form for authenticated user. | PASS |
| `test_auth_mode_header_renders_get_logout_link_when_configured` | Verifies header supports GET method for logout when explicitly configured. | PASS |

### Test Execution Summary
- **Generator Tests:** 31 passed (197 assertions).
- **Full Suite:** 373 passed (2,937 assertions, 100% green).

---

## 8. Status Indicators

```yaml
GENERATOR_V3_G04: PASS
PUBLIC_MODE: VERIFIED
OPTIONAL_AUTH: VERIFIED
PROTECTED_ROUTES: VERIFIED
ADMIN_AUTH_ISOLATION: VERIFIED
GENERATOR_REGRESSION: PASS
READY_FOR_GENERATOR_V3_G05: YES
```
