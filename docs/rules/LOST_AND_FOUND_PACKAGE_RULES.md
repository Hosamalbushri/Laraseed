# Webkul\LostAndFound Architectural Rules Contract

> **AUTHORITY DECLARATION**
> These rules are mandatory for all human developers and AI coding agents making changes to `Webkul\LostAndFound`.
> 
> 1. Repository-wide authoritative rules remain higher priority.
> 2. Existing CampusHub architecture is the implementation reference.
> 3. No developer or AI model may introduce a parallel architecture inside `Webkul\LostAndFound` without explicit architectural approval.

---

## 1. Rule Priority Hierarchy

```text
1. Repository-wide authoritative instructions / rules
2. Current verified CampusHub architecture
3. LostAndFound package rules (this document)
4. Current Phase / Step implementation prompt
5. General Laravel conventions
6. Developer / model preference
```

---

## 2. Rule Classification Scheme

Every rule in this document is classified as follows:
- **`[A]` CAMPUSHUB ARCHITECTURE RULE**: Mandatory pattern derived directly from existing CampusHub source code (`packages/Webkul/Event`, `packages/Webkul/Student`, `packages/Webkul/Core`, `packages/Webkul/DataGrid`).
- **`[D]` LOSTANDFOUND DOMAIN RULE**: Pure business domain invariant or transition rule governing Lost & Found capabilities.
- **`[S]` SECURITY RULE**: Critical security invariant protecting against data leakage, authentication secret storage, or unauthorized access.
- **`[P]` FUTURE POLICY — NOT YET DECIDED**: Speculative policy placeholder subject to future business specification; MUST NOT be treated as implemented mandatory behavior.

---

## 3. Core Architectural & Development Principles

### Rule 3.1 `[A]` — Canonical Package Blueprint & Registration Pattern
Package structure and registration MUST strictly conform to established CampusHub conventions:
- **Package Provider Registration**: Registered in `bootstrap/providers.php` (`Webkul\LostAndFound\Providers\LostAndFoundServiceProvider::class`). [Reference: `packages/Webkul/Event/src/Providers/EventServiceProvider.php`]
- **Module Concord Registration**: Registered in `config/concord.php` (`Webkul\LostAndFound\Providers\ModuleServiceProvider::class`). [Reference: `packages/Webkul/Event/src/Providers/ModuleServiceProvider.php`]
- **Root PSR-4 Autoloading**: Mapped in root `composer.json` under `"Webkul\\LostAndFound\\": "packages/Webkul/LostAndFound/src"`. [Reference: root `composer.json`]
- **Package Composer Manifest**: Defined in `packages/Webkul/LostAndFound/composer.json`. [Reference: `packages/Webkul/Event/composer.json`]
- **Domain Logic Location**: Placed in `src/Services/` (not `src/Domain/`). [Reference: `packages/Webkul/Event/src/Services/`]
- **Model Contracts**: Placed in `src/Contracts/` ONLY when paired 1:1 with Eloquent models registered in `ModuleServiceProvider::$models`. Empty contracts without consumers are prohibited. [Reference: `packages/Webkul/Event/src/Contracts/`]
- **Enum Location**: Placed in `src/Enums/`. [Reference: `packages/Webkul/DataGrid/src/Enums/`]

### Rule 3.2 `[A]` — Inspect Before Modify
Every step touching `Webkul\LostAndFound` must begin by inspecting current package state and reference implementations in CampusHub (`Webkul/Event`, `Webkul/Student`, `Webkul/User`, `Webkul/Core`). Never assume before generating code.

### Rule 3.3 `[A]` — Current Code Is Evidence
Decisions must be based on actual verified CampusHub source code, not outdated prompt assumptions or generic Laravel memory.

### Rule 3.4 `[A]` — Package Ownership & Boundaries
Domain logic for Lost & Found belongs exclusively to `Webkul\LostAndFound`. Do NOT place business logic inside `Webkul\Admin`, `Webkul\Shop`, `Webkul\Student`, `Webkul\User`, `Webkul\Event`, or `Webkul\Core`. Presentation packages (`Admin`, `Shop`) may host presentation integration views/controllers only.

### Rule 3.5 `[A]` — Dependency Direction
Domain logic must remain presentation-independent. `Webkul\LostAndFound` core code must NEVER depend on `Webkul\Admin` or `Webkul\Shop`. Presentation shells depend on `LostAndFound`, not vice-versa.

### Rule 3.6 `[A]` — Authentication & ACL Reuse
- **Students**: Reuses existing `student` guard (`auth:student`) and `Webkul\Student\Models\Student`. [Reference: `packages/Webkul/Student/src/Providers/StudentServiceProvider.php`]
- **Employees**: Reuses existing `user` guard (`auth:user`) and Bouncer ACL (`Webkul\User\Models\Role` / `Permission`). [Reference: `packages/Webkul/User/src/Models/User.php`]
- **NEVER** create a new users table, new auth provider, or secondary ACL library.

### Rule 3.7 `[S]` — Student Ownership Authorization
Student authorization is strictly ownership-based. Students can create/edit ONLY their own lost reports and view the status of their own claims. Students MUST NOT receive employee ACL capabilities, view private staff verification evidence, or perform handovers.

### Rule 3.8 `[A]` — Localization & Parity
Hardcoding user-visible text is strictly prohibited. All strings must use translation keys (`lost_found::app.*`). When adding keys, translation parity must be maintained across all 7 supported CampusHub locales (`en`, `ar`, `es`, `fa`, `pt_BR`, `tr`, `vi`). [Reference: `packages/Webkul/Student/src/Resources/lang`]

### Rule 3.9 `[S]` — Sensitive Data & Secret Evidence Prohibition
- **NEVER** persist or request account passwords, PINs, unlock patterns, recovery codes, or OTPs as ownership verification evidence.
- Verification details (e.g. hidden marks, serial fragments) must be marked `STAFF_ONLY` / `CLAIMANT_ONLY` and hidden from public APIs, views, request dumps, exceptions, and logs.

### Rule 3.10 `[D]` — Claim vs Handover Separation
Claim Approval is an administrative authorization decision. Physical Handover is a distinct physical receipt event (`LostFoundHandover`). An approved claim is required before handover, but claim approval does NOT equal handover. Handed-over items cannot undergo repeat handovers.

### Rule 3.11 `[D]` — Competing Claims & Concurrency Protection
Approving a claim for a physical found item must automatically invalidate competing open claims. Multi-record irreversible operations (claim approval, handover, custody transfer) MUST execute inside atomic database transactions (`DB::transaction`) with pessimistic row locking (`lockForUpdate()`).

### Rule 3.12 `[P]` — Policy Hardcoding Prohibition
Do NOT hardcode arbitrary speculative business policy values (e.g. 90-day expiration, 70% match threshold, 5 reports/hour rate limit, automatic auction/destruction) unless explicitly authorized by domain specifications. Prefer configurable policy boundaries.

### Rule 3.13 `[A]` — No Unapproved Packages
Third-party Composer or NPM packages must NOT be added silently. Any new dependency requires explicit architectural justification and authorization.

### Rule 3.14 `[A]` — Prohibited Layer Leakage
Steps must strictly adhere to their assigned layer scope. Scaffolding steps must NOT add database tables, Eloquent models, HTTP routes, controllers, or Blade views before their designated step.

---

## 4. Future-Step Verification Checklist

Before making any modifications to `Webkul\LostAndFound`:

- [ ] Read repository-wide rules.
- [ ] Read `LOST_AND_FOUND_PACKAGE_RULES.md`.
- [ ] Inspect current `Webkul\LostAndFound` package state.
- [ ] Inspect relevant CampusHub reference implementation.
- [ ] Confirm current step scope and prohibited layers.
- [ ] Record baseline (`git status --short`, route list, tests).
- [ ] Preserve existing user work.
- [ ] Implement only approved layer code.
- [ ] Run focused domain tests and full regression test suite.
- [ ] Inspect final diff (`git diff --stat`, `git diff`).
- [ ] Report executed verification results.
