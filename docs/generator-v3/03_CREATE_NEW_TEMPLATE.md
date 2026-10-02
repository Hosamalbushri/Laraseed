# Laraseed Generator V3 — Creating a New Template Tutorial

This tutorial provides a complete, step-by-step guide for developers wishing to design, author, register, and test a new Web capability template for Laraseed Generator V3.

For this tutorial, we will design a hypothetical enterprise template named **`business`**.

> [!IMPORTANT]
> **Documentation Note:**  
> The `business` template described in this tutorial is a comprehensive reference example. In accordance with architectural stability rules for milestone G08, no new templates are registered in the production `WebTemplateCatalog` during this phase.

---

## 1. Overview of Template Architecture

A template in Laraseed Generator V3 consists of two core elements:
1. **Stub Directory (`packages/Laraseed/PackageGenerator/stubs/templates/{id}/`):**  
   The physical `.stub` files that will be parsed, substituted, and generated into the target package.
2. **Catalog Entry (`WebTemplateCatalog::$templates`):**  
   A declarative array mapping target package destination paths to template stub files.

---

## 2. Step-by-Step Tutorial: Authoring the `business` Template

```
+-----------------------------------------------------------------------------------+
|                        NEW TEMPLATE CREATION WORKFLOW                             |
+-----------------------------------------------------------------------------------+
| 1. Create directory: stubs/templates/business/                                    |
| 2. Choose files to inherit or customize from starter                              |
| 3. Create corporate layout, header, footer, & navigation stubs                    |
| 4. Create business page views (Home, Services, Team, Pricing)                     |
| 5. Create business-specific Blade UI components (Pricing Card, Testimonial)       |
| 6. Configure branding defaults & navigation structure in config.php.stub          |
| 7. Create bilingual translation dictionaries with 100% key parity (en & ar)       |
| 8. Configure Tailwind palette & Vite build pipeline                               |
| 9. Register template in WebTemplateCatalog                                        |
| 10. Scaffold, compile, and verify a disposable test package in the browser        |
+-----------------------------------------------------------------------------------+
```

---

### Step 1: Create the Template Stub Directory

Create the dedicated stub folder under `PackageGenerator`:

```bash
mkdir -p packages/Laraseed/PackageGenerator/stubs/templates/business
```

---

### Step 2: Choose Which Files to Reuse vs. Customize

When creating a new template, determine which files require distinct visual markup and which follow standard backend scaffolding:

| File Category | Strategy for `business` Template |
| :--- | :--- |
| **Tooling (`package.json`, `postcss.config.js`)** | **Reuse verbatim** from `starter` template. |
| **Build Configuration (`vite.config.js`)** | **Reuse with matching paths** (`{{ PACKAGE_SLUG }}/web/build`). |
| **Styling (`tailwind.config.js`, `app.css`)** | **Customize:** Introduce a corporate slate/indigo palette and enterprise button styles. |
| **Providers & Middleware** | **Reuse architecture:** `provider.php.stub` and `middleware_auth.php.stub`. |
| **Configuration (`config.php.stub`)** | **Customize:** Set `'template' => 'business'`, custom brand color (`#1E40AF`), and corporate navigation links (`services`, `pricing`, `team`). |
| **Layout & Chrome (`layout`, `header`, `footer`)** | **Customize:** Multi-column enterprise footer, top announcement bar, and corporate navigation styling. |
| **Page Views (`view_home`, `view_page`)** | **Customize:** Professional hero, service grid, client testimonials, and pricing table. |
| **Translations (`lang_en`, `lang_ar`)** | **Customize:** Complete business translation dictionary with exact key parity. |

---

### Step 3: Create the Master Layout Stub

Create `packages/Laraseed/PackageGenerator/stubs/templates/business/layout.blade.php.stub`:

```blade
<!DOCTYPE html>
<html
    class="{{ request()->cookie('dark_mode') ? 'dark' : '' }}"
    lang="{{ app()->getLocale() }}"
    dir="{{ in_array(app()->getLocale(), ['fa', 'ar']) ? 'rtl' : 'ltr' }}"
>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? trans('{{ PACKAGE_KEY }}_web::app.web.title') }}</title>

    @php
        $assetsBuilt = file_exists(public_path('{{ PACKAGE_KEY }}-web-vite.hot')) || file_exists(public_path('{{ PACKAGE_SLUG }}/web/build/manifest.json'));
    @endphp

    @if ($assetsBuilt)
        {{ vite()->set(['src/Web/Resources/assets/css/app.css', 'src/Web/Resources/assets/js/app.js'], '{{ PACKAGE_KEY }}_web') }}
    @endif

    <style>
        :root {
            --brand-color: {{ config('{{ PACKAGE_KEY }}_web.branding.color', '#1E40AF') }};
        }
        :root, body {
            font-family: 'Cairo', ui-sans-serif, system-ui, sans-serif;
        }
    </style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100 flex flex-col antialiased">
    @if (! $assetsBuilt)
        <aside class="bg-amber-600 text-white text-xs font-semibold px-4 py-2 text-center">
            [Laraseed Diagnostic] Frontend assets for <strong>{{ '{{ PACKAGE_TITLE }}' }}</strong> are not built. Run <code>npm run build</code> in <code>packages/{{ '{{ VENDOR }}' }}/{{ '{{ PACKAGE }}' }}</code>.
        </aside>
    @endif

    <div id="app" class="flex min-h-screen flex-col">
        <x-{{ PACKAGE_KEY }}_web::layouts.header />

        <main class="flex-1">
            {{ $slot }}
        </main>

        <x-{{ PACKAGE_KEY }}_web::layouts.footer />
    </div>

    @stack('scripts')
</body>
</html>
```

---

### Step 4: Create Business Components

Add an enterprise pricing card component stub at `stubs/templates/business/component_pricing_card.blade.php.stub`:

```blade
@props([
    'title',
    'price',
    'period' => '/month',
    'featured' => false,
    'cta' => 'Get Started',
    'href' => '#',
])

<div {{ $attributes->merge(['class' => 'rounded-3xl p-8 transition-all ' . ($featured ? 'border-2 border-[var(--brand-color)] bg-white shadow-xl dark:bg-slate-900' : 'border border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-900/50')]) }}>
    <h3 class="text-xl font-bold text-slate-900 dark:text-white">{{ $title }}</h3>
    <div class="mt-4 flex items-baseline text-slate-900 dark:text-white">
        <span class="text-4xl font-extrabold tracking-tight">{{ $price }}</span>
        <span class="ml-1 text-sm font-semibold text-slate-500">{{ $period }}</span>
    </div>
    
    <div class="mt-6 space-y-4 text-sm text-slate-600 dark:text-slate-300">
        {{ $slot }}
    </div>

    <div class="mt-8">
        <x-{{ PACKAGE_KEY }}_web::button :href="$href" :variant="$featured ? 'primary' : 'outline'" class="w-full justify-center">
            {{ $cta }}
        </x-{{ PACKAGE_KEY }}_web::button>
    </div>
</div>
```

---

### Step 5: Configure Business Defaults (`config.php.stub`)

Create `packages/Laraseed/PackageGenerator/stubs/templates/business/config.php.stub`:

```php
<?php

return [
    'template' => 'business',

    'prefix' => '{{ PACKAGE_SLUG }}',

    'middleware' => ['web'],

    'branding' => [
        'name'  => '{{ PACKAGE_TITLE }} Enterprise',
        'color' => '#1E40AF', // Corporate Royal Blue
        'logo'  => null,
    ],

    'auth' => [
        'enabled'          => false,
        'guard'            => null,
        'provider_package' => null,
        'routes'           => [
            'login'  => null,
            'logout' => null,
        ],
    ],

    'navigation' => [
        'home' => [
            'name'  => '{{ PACKAGE_KEY }}_web::app.web.home',
            'route' => '{{ PACKAGE_KEY }}.web.home',
            'sort'  => 1,
        ],
        'services' => [
            'name'   => '{{ PACKAGE_KEY }}_web::app.web.services',
            'route'  => '{{ PACKAGE_KEY }}.web.pages.show',
            'params' => ['page' => 'services'],
            'sort'   => 2,
        ],
        'pricing' => [
            'name'   => '{{ PACKAGE_KEY }}_web::app.web.pricing',
            'route'  => '{{ PACKAGE_KEY }}.web.pages.show',
            'params' => ['page' => 'pricing'],
            'sort'   => 3,
        ],
        'contact' => [
            'name'   => '{{ PACKAGE_KEY }}_web::app.web.contact',
            'route'  => '{{ PACKAGE_KEY }}.web.pages.show',
            'params' => ['page' => 'contact'],
            'sort'   => 4,
        ],
    ],
];
```

---

### Step 6: Create Bilingual Translation Dictionaries

Ensure exact key parity between English (`lang_en.php.stub`) and Arabic (`lang_ar.php.stub`):

**English (`lang_en.php.stub`):**
```php
<?php

return [
    'web' => [
        'title' => '{{ PACKAGE_TITLE }} Enterprise',
        'home' => 'Home',
        'services' => 'Services',
        'pricing' => 'Pricing',
        'contact' => 'Contact',
        'auth' => [
            'login' => 'Client Portal',
            'logout' => 'Logout',
            'account' => 'Account',
            'dashboard' => 'Dashboard',
        ],
        'hero' => [
            'headline' => 'Scale Your Enterprise with {{ PACKAGE_TITLE }}',
            'subheadline' => 'Robust, secure, and modern enterprise software architecture.',
            'primary_action' => 'Schedule Consultation',
            'secondary_action' => 'Explore Services',
        ],
        'footer' => [
            'tagline' => 'Enterprise Solutions by Laraseed.',
            'rights' => 'All rights reserved.',
        ],
    ],
];
```

**Arabic (`lang_ar.php.stub`):**
```php
<?php

return [
    'web' => [
        'title' => '{{ PACKAGE_TITLE }} للشركات',
        'home' => 'الرئيسية',
        'services' => 'الخدمات',
        'pricing' => 'الأسعار',
        'contact' => 'اتصل بنا',
        'auth' => [
            'login' => 'بوابة العملاء',
            'logout' => 'تسجيل الخروج',
            'account' => 'الحساب',
            'dashboard' => 'لوحة التحكم',
        ],
        'hero' => [
            'headline' => 'طوّر أعمال شركتك مع {{ PACKAGE_TITLE }}',
            'subheadline' => 'حلول برمجية مؤسسية حديثة، آمنة وقابلة للتوسع.',
            'primary_action' => 'طلب استشارة',
            'secondary_action' => 'استعراض الخدمات',
        ],
        'footer' => [
            'tagline' => 'حلول تقنية مؤسسية مدعومة بلاراسيد.',
            'rights' => 'جميع الحقوق محفوظة.',
        ],
    ],
];
```

---

### Step 7: Register Template in `WebTemplateCatalog`

In `packages/Laraseed/PackageGenerator/src/Templates/WebTemplateCatalog.php`:

```php
protected static array $templates = [
    'starter' => [ ... ],

    'business' => [
        'id' => 'business',
        'name' => 'Business Enterprise',
        'description' => 'Corporate template with pricing matrices, testimonial blocks, and slate/indigo theme.',
        'files' => [
            'package.json'                                                      => 'templates/business/package.json.stub',
            'vite.config.js'                                                    => 'templates/business/vite.config.js.stub',
            'tailwind.config.js'                                                => 'templates/business/tailwind.config.js.stub',
            'postcss.config.js'                                                 => 'templates/business/postcss.config.js.stub',
            'src/Web/Providers/WebServiceProvider.php'                          => 'templates/business/provider.php.stub',
            'src/Web/Config/web.php'                                            => 'templates/business/config.php.stub',
            'src/Web/Http/Middleware/AuthenticateWeb.php'                       => 'templates/business/middleware_auth.php.stub',
            'src/Web/Http/Controllers/HomeController.php'                       => 'templates/business/controller_home.php.stub',
            'src/Web/Http/Controllers/PageController.php'                       => 'templates/business/controller_page.php.stub',
            'src/Web/Http/Controllers/AccountController.php'                    => 'templates/business/controller_account.php.stub',
            'src/Web/Routes/web.php'                                            => 'templates/business/routes_web.php.stub',
            'src/Web/Resources/lang/en/app.php'                                 => 'templates/business/lang_en.php.stub',
            'src/Web/Resources/lang/ar/app.php'                                 => 'templates/business/lang_ar.php.stub',
            'src/Web/Resources/views/components/layouts/index.blade.php'         => 'templates/business/layout.blade.php.stub',
            'src/Web/Resources/views/components/layouts/header/index.blade.php'  => 'templates/business/header.blade.php.stub',
            'src/Web/Resources/views/components/layouts/header/navbar.blade.php' => 'templates/business/navbar.blade.php.stub',
            'src/Web/Resources/views/components/layouts/footer/index.blade.php'  => 'templates/business/footer.blade.php.stub',
            'src/Web/Resources/views/components/container/index.blade.php'       => 'templates/business/component_container.blade.php.stub',
            'src/Web/Resources/views/components/section/index.blade.php'         => 'templates/business/component_section.blade.php.stub',
            'src/Web/Resources/views/components/card/index.blade.php'            => 'templates/business/component_card.blade.php.stub',
            'src/Web/Resources/views/components/button/index.blade.php'          => 'templates/business/component_button.blade.php.stub',
            'src/Web/Resources/views/components/modal/index.blade.php'           => 'templates/business/component_modal.blade.php.stub',
            'src/Web/Resources/views/components/pricing-card/index.blade.php'    => 'templates/business/component_pricing_card.blade.php.stub',
            'src/Web/Resources/views/components/form/control-group/index.blade.php' => 'templates/business/component_form_control_group.blade.php.stub',
            'src/Web/Resources/views/home/index.blade.php'                      => 'templates/business/view_home.blade.php.stub',
            'src/Web/Resources/views/pages/show.blade.php'                      => 'templates/business/view_page.blade.php.stub',
            'src/Web/Resources/views/account/dashboard.blade.php'               => 'templates/business/view_account_dashboard.blade.php.stub',
            'src/Web/Resources/assets/css/app.css'                              => 'templates/business/asset_css.css.stub',
            'src/Web/Resources/assets/js/app.js'                               => 'templates/business/asset_js.js.stub',
            'tests/Feature/Web/WebPageTest.php'                                 => 'templates/business/feature_test.php.stub',
        ],
    ],
];
```

---

### Step 8: Generating & Verifying a Disposable Test Package

```bash
# 1. Generate base test package
php artisan laraseed:make-package Acme/EnterprisePortal

# 2. Generate web capability using the new template
php artisan laraseed:make-web Acme/EnterprisePortal --template=business

# 3. Update composer autoload map
composer dump-autoload

# 4. Compile frontend assets
cd packages/Acme/EnterprisePortal
npm install
npm run build

# 5. Run tests
php artisan test --filter=Acme\\EnterprisePortal
```

---

## 3. Template Authoring Best Practices

1. **Always Maintain Translation Parity:**  
   Every key defined in `lang_en.php.stub` must exist in `lang_ar.php.stub`.
2. **Never Break the Vite Build Path Contract:**  
   `buildDirectory` must always equal `{{ PACKAGE_SLUG }}/web/build`.
3. **Include the Asset Diagnostic Banner:**  
   Always include the `@if (! $assetsBuilt)` block in `layout.blade.php.stub` to assist developers during local setup.
4. **Use `var(--brand-color)` for Primary Elements:**  
   Ensure that user-configured brand colors dynamically theme buttons, active nav items, and hero accents.
