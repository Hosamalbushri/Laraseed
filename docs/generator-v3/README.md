# Laraseed Generator V3 — Developer Documentation

Welcome to the official developer documentation for **Laraseed Generator V3**, the modular Web capability scaffolding and template architecture for Laravel and Laraseed applications.

Laraseed Generator V3 allows developers to scaffold, customize, and maintain standalone, package-isolated web interfaces (public websites, customer portals, marketing sites, and domain frontends) without coupling them to framework core files, admin dashboards, or shared global assets.

---

## Documentation Navigation Index

| # | Document | Primary Focus | Target Audience |
| :--- | :--- | :--- | :--- |
| **01** | [**Architecture & System Lifecycle**](01_ARCHITECTURE.md) | Full generation lifecycle, class responsibilities, Mermaid diagrams, package composition, and strict boundaries. | Architects & Lead Developers |
| **02** | [**Template Structure & Stubs Inventory**](02_TEMPLATE_STRUCTURE.md) | File-by-file audit of the `starter` template, template catalog mechanics, placeholders, and customization points. | Template Authors & Developers |
| **03** | [**Creating a New Template**](03_CREATE_NEW_TEMPLATE.md) | Step-by-step tutorial creating a custom `business` template from scratch with pages, branding, and assets. | Extension Developers |
| **04** | [**Blade Components Reference**](04_BLADE_COMPONENTS.md) | Complete property, slot, and accessibility reference for all package-owned Blade UI components. | Frontend Engineers |
| **05** | [**Styling & Frontend Asset Pipeline**](05_STYLING_AND_ASSETS.md) | Vite build pipeline, Tailwind CSS configuration, Cairo typography, dark mode, and asset isolation. | Frontend Engineers |
| **06** | [**Routes, Navigation & Domain Mounting**](06_ROUTES_AND_NAVIGATION.md) | Route prefixing, root domain mounting (`/`), navigation configuration, active link states, and multi-package coexistence. | Backend Developers |
| **07** | [**Localization & RTL Support**](07_LOCALIZATION.md) | Arabic & English translation parity, RTL/LTR layout directionality, Cairo typography, and locale switching. | Full-Stack Developers |
| **08** | [**Authentication & Guard Isolation**](08_AUTHENTICATION.md) | Optional decoupled authentication, guard isolation, CSRF-protected POST logout, and redirect isolation. | Security & Backend Developers |
| **09** | [**Testing, Certification & Release**](09_TESTING_AND_RELEASE.md) | Automated testing workflow, translation parity tests, asset compilation checks, and browser visual certification. | QA & Release Engineers |
| **10** | [**Troubleshooting & Diagnostic Guide**](10_TROUBLESHOOTING.md) | Comprehensive troubleshooting catalog for autoloading, routing, styling, authentication, and server issues. | All Developers |

---

## Core Architectural Principles

Laraseed Generator V3 is built on six fundamental architectural pillars:

1. **Zero Foundation Mutation:**  
   Generating a Web capability never modifies `packages/Webkul/*`, `bootstrap/providers.php`, or global application files. All files reside exclusively inside the target package directory (`packages/{Vendor}/{Package}`).

2. **Isolated Package-Owned Assets:**  
   Each Web package contains its own `package.json`, `vite.config.js`, `tailwind.config.js`, and PostCSS configuration. Assets are compiled to `public/{package-slug}/web/build/` with a standalone `manifest.json`, completely isolated from Admin or application assets.

3. **Dynamic Capability Composition:**  
   Web capabilities register via `extra.laraseed.capabilities.web` in `composer.json`. Activation is declarative via `LARASEED_OPTIONAL_PACKAGES` in `.env`. When disabled, all routes, views, translations, and middleware are completely omitted from the Laravel lifecycle.

4. **Independent Route Ownership:**  
   Web packages default to a clean URL prefix (`/{package-slug}`) and can optionally claim the root domain (`/`). Strict route ownership checks prevent route collisions and redirect hijacking across packages and Webkul Admin.

5. **First-Class Bilingual Architecture:**  
   Full English (`en`) and Arabic (`ar`) support with 100% translation key parity, automatic RTL/LTR layout switching, and Cairo typography configured out of the box.

6. **Decoupled Optional Authentication:**  
   Web capabilities run in public website mode by default. When authentication is needed, packages can attach to any configured Laravel guard (`customer`, `member`, `user`) without embedding custom authentication tables or corrupting Admin authentication.

---

## Quick Start: 3-Minute Web Package Scaffolding

Follow these 4 simple steps to generate and run a complete Web package:

```bash
# 1. Generate the base package skeleton
php artisan laraseed:make-package Acme/Portal

# 2. Add the Web capability using the Starter template
php artisan laraseed:make-web Acme/Portal --template=starter

# 3. Register Composer autoloading
# Add "Acme\\Portal\\": "packages/Acme/Portal/src" to root composer.json autoload.psr-4
composer dump-autoload

# 4. Compile frontend assets
cd packages/Acme/Portal
npm install
npm run build
```

Activate the package in `.env`:
```dotenv
LARASEED_OPTIONAL_PACKAGES=contacts,acme_portal
```

Visit the website at: `http://localhost:8000/acme-portal`

---

## Document Reading Paths

- **If you are building a new website using an existing package:**  
  Start with [Architecture](01_ARCHITECTURE.md), then read [Routes & Navigation](06_ROUTES_AND_NAVIGATION.md), [Blade Components](04_BLADE_COMPONENTS.md), and [Styling & Assets](05_STYLING_AND_ASSETS.md).

- **If you are creating a new template design for the generator:**  
  Read [Template Structure](02_TEMPLATE_STRUCTURE.md) followed by [Creating a New Template](03_CREATE_NEW_TEMPLATE.md).

- **If you are adding user login/portal functionality:**  
  Read [Authentication & Guard Isolation](08_AUTHENTICATION.md).

- **If you are resolving build, route, or styling issues:**  
  Refer directly to [Troubleshooting](10_TROUBLESHOOTING.md).
