# Laraseed Package Generator V4

Laraseed Package Generator V4 is an enterprise-grade modular scaffolding and lifecycle management engine designed for Laravel 12 and Concord-driven architectures. It generates self-contained, isolated packages with full support for domain models, contracts, proxies, HTTP layers, notifications, mail, admin capabilities, web capabilities, and transactional filesystem modifications.

---

## Key Features in V4

- **Dual Architecture Scaffolding:**
  - **Modular Concord Packages:** Full integration with Concord modules, model contracts, proxies, and service provider composition.
  - **Plain PSR-4 Libraries:** Lightweight standalone libraries generated via `--plain` with clean `composer.json` metadata.
- **Atomic Model & Proxy Composition:**
  - Generate Model, Contract, and Concord ModelProxy in a single atomic operation with `--contract` and `--proxy`.
- **Expanded Presentation & Messaging Generators:**
  - **Middleware Generator:** Standard Laravel HTTP middleware scaffolding.
  - **Mail Generator:** Plain, HTML-view, and Markdown-view Mailables with companion Blade templates.
  - **Notification Generator:** Multi-channel notification scaffolding supporting Mail, Database, and Broadcast channels with dynamic import hygiene and `--broadcast` shortcut.
- **Extensible Web Capability Engine:**
  - Pluggable Web template registry with runtime configuration support and strict Content Security Policy (CSP) compliance.
- **Enterprise Filesystem Safety:**
  - Cross-process file locking via [`PackageLock`](file:///home/hosam/Documents/CampusHub-main/packages/Laraseed/PackageGenerator/src/Support/PackageLock.php) (`flock` + PID metadata) to prevent concurrent manifest races.
  - Transactional rollback via [`FilesystemTransaction`](file:///home/hosam/Documents/CampusHub-main/packages/Laraseed/PackageGenerator/src/Generators/FilesystemTransaction.php) leaving zero orphan files on generation failure.
  - Strict filesystem containment via [`PathGuard`](file:///home/hosam/Documents/CampusHub-main/packages/Laraseed/PackageGenerator/src/Support/PathGuard.php) preventing path traversal and symlink escapes.
- **Frictionless Runtime Bootstrap:**
  - Dynamic PSR-4 ClassLoader mapping in [`config/laraseed.php`](file:///home/hosam/Documents/CampusHub-main/config/laraseed.php) eliminates Catch-22 bootstrap crashes before `composer dump-autoload` is executed.

---

## Documentation Index

- [Command Reference](file:///home/hosam/Documents/CampusHub-main/docs/package-generator/v4/COMMAND_REFERENCE.md) — Comprehensive guide to all Artisan commands, options, and parameters.
- [Architecture Guide](file:///home/hosam/Documents/CampusHub-main/docs/package-generator/v4/ARCHITECTURE.md) — Deep dive into generator internals, transactions, concurrency locks, and discovery.
- [Production Deployment Guide](file:///home/hosam/Documents/CampusHub-main/docs/package-generator/v4/DEPLOYMENT.md) — Best practices for deploying optional packages, Composer optimization, and caching.
- [Release Checklist](file:///home/hosam/Documents/CampusHub-main/docs/package-generator/v4/RELEASE_CHECKLIST.md) — Pre-flight release verification steps.
- [Release Notes](file:///home/hosam/Documents/CampusHub-main/docs/package-generator/v4/RELEASE_NOTES.md) — Summary of changes, new capabilities, and compatibility notes.

---

## Quick Start

### 1. Generate a Concord Modular Package
```bash
php artisan laraseed:make-package Acme/Billing
```

### 2. Generate a Domain Model with Contract and Proxy
```bash
php artisan laraseed:make-model Acme/Billing Invoice --contract --proxy
```

### 3. Add Presentation & Messaging Components
```bash
# HTTP Controller (API Mode)
php artisan laraseed:make-controller Acme/Billing InvoiceController --api

# HTTP Middleware
php artisan laraseed:make-middleware Acme/Billing VerifyBillingSignature

# Markdown Mailable
php artisan laraseed:make-mail Acme/Billing InvoiceReceiptMail --markdown=emails.receipt

# Multi-Channel Notification
php artisan laraseed:make-notification Acme/Billing InvoiceDueNotification --database --broadcast --queued
```

### 4. Add Admin & Web Capabilities
```bash
# Admin Panel Module (DataGrid, ACL, Menu, Routes)
php artisan laraseed:make-admin Acme/Billing

# Public Web Portal (Vite, Tailwind, Blade Views)
php artisan laraseed:make-web Acme/Billing --template=starter
```

### 5. Activate Package
Enable the package in your `.env` file:
```dotenv
LARASEED_OPTIONAL_PACKAGES="billing"
```

Rebuild Composer autoloader for production optimization:
```bash
composer dump-autoload -o
```
