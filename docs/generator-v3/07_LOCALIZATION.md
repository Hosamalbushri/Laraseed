# Laraseed Generator V3 — Localization & RTL Architecture

This document details the bilingual architecture, translation namespaces, language switching mechanisms, RTL (Right-to-Left) layout handling, and Cairo typography in **Laraseed Generator V3**.

---

## 1. Bilingual Architecture Principles

Laraseed treats Arabic (`ar`) and English (`en`) as equal, first-class citizens:

1. **100% Key Parity:** Every translation key present in English must exist in Arabic with accurate terminology.
2. **Dynamic Directionality:** The master layout dynamically sets `dir="rtl"` or `dir="ltr"` based on the active application locale.
3. **Typography Optimization:** Cairo font is configured across all headings and body text for aesthetic harmony in both Arabic and Latin characters.
4. **Namespace Isolation:** Translation files are registered under the package namespace (`{package_key}_web`), avoiding global language file collisions.

---

## 2. Translation File Structure & Namespaces

Translation files are located in `src/Web/Resources/lang/`:

```
packages/Vendor/Package/src/Web/Resources/lang/
├── en/
│   └── app.php   (English translations)
└── ar/
    └── app.php   (Arabic translations)
```

### Namespace Registration in `WebServiceProvider::boot()`
```php
if (is_dir(__DIR__ . '/../Resources/lang')) {
    $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', '{{ PACKAGE_KEY }}_web');
}
```

### Accessing Translations in Blade & Controllers
```blade
<!-- In Blade Views -->
<h1>@lang('acme_portal_web::app.web.hero.headline')</h1>
<p>{{ trans('acme_portal_web::app.web.hero.subheadline') }}</p>

<!-- In PHP / Controllers -->
$title = trans('acme_portal_web::app.web.title');
```

---

## 3. Translation Schema & Key Structure

The Starter template includes a structured translation dictionary:

```php
// src/Web/Resources/lang/en/app.php
return [
    'web' => [
        'title' => 'Portal',
        'home' => 'Home',
        'about' => 'About Us',
        'features' => 'Features',
        'contact' => 'Contact',
        'auth' => [
            'login' => 'Login',
            'logout' => 'Logout',
            'account' => 'Account',
            'dashboard' => 'Dashboard',
        ],
        'hero' => [
            'headline' => 'Welcome to Portal',
            'subheadline' => 'An independent, responsive, modern web interface.',
            'primary_action' => 'Get Started',
            'secondary_action' => 'Learn More',
        ],
        'highlights' => [
            'fast' => [
                'title' => 'Fast & Light',
                'description' => 'Powered by Vite, Tailwind CSS, and optimized Blade components.',
            ],
            'modular' => [
                'title' => 'Truly Modular',
                'description' => 'Completely package-owned routes and templates.',
            ],
            'bilingual' => [
                'title' => 'Arabic & English Ready',
                'description' => 'Built-in support for RTL and LTR with Cairo typography.',
            ],
        ],
        'footer' => [
            'tagline' => 'Modern modular architecture with Laravel & Laraseed.',
            'rights' => 'All rights reserved.',
        ],
    ],
];
```

The Arabic counterpart (`lang/ar/app.php`) mirrors every key exactly:

```php
// src/Web/Resources/lang/ar/app.php
return [
    'web' => [
        'title' => 'البوابة',
        'home' => 'الرئيسية',
        'about' => 'من نحن',
        'features' => 'المميزات',
        'contact' => 'اتصل بنا',
        'auth' => [
            'login' => 'تسجيل الدخول',
            'logout' => 'تسجيل الخروج',
            'account' => 'الحساب',
            'dashboard' => 'لوحة التحكم',
        ],
        'hero' => [
            'headline' => 'مرحباً بك في البوابة',
            'subheadline' => 'واجهة ويب حديثة ومستقلة ومتجاوبة مبنية وفق معايير لاراسيد.',
            'primary_action' => 'ابدأ الآن',
            'secondary_action' => 'المزيد من التفاصيل',
        ],
        'highlights' => [
            'fast' => [
                'title' => 'أداء سريع وخفيف',
                'description' => 'مدعوم بأحدث تقنيات Vite و Tailwind CSS ومكونات Blade المحسّنة.',
            ],
            'modular' => [
                'title' => 'معمارية مستقلة بالكامل',
                'description' => 'ملفات التوجيه والقوالب والترجمات مملوكة بالكامل للحزمة.',
            ],
            'bilingual' => [
                'title' => 'دعم ثنائي للغة مع الخط العربي',
                'description' => 'دعم كامل للاتجاهين RTL و LTR مع خط Cairo الجميل بشكل افتراضي.',
            ],
        ],
        'footer' => [
            'tagline' => 'معمارية نمطية حديثة مدعومة بإطار لارافيل ولاراسيد.',
            'rights' => 'جميع الحقوق محفوظة.',
        ],
    ],
];
```

---

## 4. Dynamic Language Switching

The Starter template controllers (`HomeController` and `PageController`) support dynamic language switching via URL query parameters:

```php
public function index(Request $request): View
{
    if ($request->has('locale') && in_array($request->query('locale'), ['en', 'ar'])) {
        app()->setLocale($request->query('locale'));
        session(['locale' => $request->query('locale')]);
    } elseif (session()->has('locale')) {
        app()->setLocale(session('locale'));
    }

    return view('{{ PACKAGE_KEY }}_web::home.index');
}
```

### In the Header View (`header.blade.php`)
```blade
<!-- Language Switcher Button -->
<a
    href="?locale={{ app()->getLocale() === 'ar' ? 'en' : 'ar' }}"
    class="rounded-lg px-2.5 py-1 text-xs font-bold uppercase tracking-wider text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800 border border-gray-200 dark:border-gray-700 text-decoration-none transition-colors"
    title="Switch Language"
>
    {{ app()->getLocale() === 'ar' ? 'EN' : 'العربية' }}
</a>
```

---

## 5. RTL & LTR Layout Handling

### 5.1 Dynamic Document Direction
In `layout.blade.php`:
```blade
<html
    class="{{ request()->cookie('dark_mode') ? 'dark' : '' }}"
    lang="{{ app()->getLocale() }}"
    dir="{{ in_array(app()->getLocale(), ['fa', 'ar']) ? 'rtl' : 'ltr' }}"
>
```

### 5.2 Tailwind CSS RTL Rules
When designing or modifying templates, use logical CSS properties and Tailwind directional utilities:
- Use `text-start` instead of `text-left` (aligns left in LTR, right in RTL).
- Use `text-end` instead of `text-right` (aligns right in LTR, left in RTL).
- Use `ms-*` (margin-inline-start) and `me-*` (margin-inline-end) instead of `ml-*` and `mr-*`.
- Use `ps-*` and `pe-*` for directional padding.

---

## 6. Automated Translation Parity Testing

Every Web package generated includes an automated unit/feature test in `tests/Feature/Web/WebPageTest.php` asserting 100% key parity between English and Arabic:

```php
public function test_english_and_arabic_translations_have_parity(): void
{
    $en = require __DIR__ . '/../../../src/Web/Resources/lang/en/app.php';
    $ar = require __DIR__ . '/../../../src/Web/Resources/lang/ar/app.php';

    $this->assertSame(array_keys($en['web']), array_keys($ar['web']));
    $this->assertSame(array_keys($en['web']['auth']), array_keys($ar['web']['auth']));
    $this->assertSame(array_keys($en['web']['hero']), array_keys($ar['web']['hero']));
    $this->assertSame(array_keys($en['web']['highlights']), array_keys($ar['web']['highlights']));
    $this->assertSame(array_keys($en['web']['footer']), array_keys($ar['web']['footer']));
}
```
