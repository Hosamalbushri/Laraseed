# LARASEED — CONTACTS PACKAGE DOMAIN & ARCHITECTURE AUDIT
## STEP 01 — ARCHITECTURAL AUDIT & DOMAIN DESIGN REPORT

**Date:** 2026-10-01  
**Project:** Laraseed Modular Application Foundation  
**Package:** `Laraseed/Contacts` (`laraseed/contacts`)  
**Status:** Audit & Design Complete — Implementation Pending Approval  
**Audit Scope:** Forensic Foundation Inspection, Domain Model Design, Boundary Analysis, and Generator Coverage Assessment  

---

## 1. Executive Summary & Architectural Mandate

This report establishes the complete architectural specification and domain design for the first real business package built on top of **Laraseed V2**: `Laraseed/Contacts`.

Following the mandatory dependency-boundary standard and Laraseed Isolation Rules (Rules 06, 07, 08, 09), the primary objective is to test Laraseed in a real business scenario under strict isolation constraints:
1. **Foundation Source Purity:** Foundation (`Webkul/Core`, `Webkul/User`, `Webkul/Admin`, `Webkul/DataGrid`, `Webkul/Installer`) maintains **ZERO** dependencies on `Contacts`. No optional namespaces, routes, tables, or classes exist within Foundation.
2. **Strict Unidirectional Consumption:** `Contacts` consumes Foundation public contracts and services only.
3. **Declarative Admin Capability:** `Contacts Admin` integration is fully self-contained under `src/Admin/` and loaded declaratively via the Laraseed V2 capability provider mechanism (`OptionalPackageComposition::capabilityProviders('admin')`).
4. **Complete Removability:** Physical removal or disabling of `packages/Laraseed/Contacts` leaves 0 orphaned references, requires 0 Foundation code modifications, and executes with 0 runtime database corruption.

---

## 2. Foundation Reuse Audit

A forensic inspection of the existing Foundation packages was conducted to identify reusable infrastructure and prevent duplicate mechanisms.

| Foundation Layer | Inspected Path | Reusable Infrastructure | Must NOT Duplicate / Must NOT Violate |
| :--- | :--- | :--- | :--- |
| **`Webkul/Core`** | `packages/Webkul/Core/src` | - `Webkul\Core\Eloquent\Repository` (caching, query scopes, pagination)<br>- `Webkul\Core\Eloquent\TranslatableModel` (if multilingual data needed)<br>- `Webkul\Core\Packages\OptionalPackageComposition`<br>- `Webkul\Core\Contracts\Country` & `CountryState`<br>- `Webkul\Core\Traits\Sanitizer` | - Do NOT create custom base repositories or bypass Prettus/Concord bindings.<br>- Do NOT query runtime database during provider boot. |
| **`Webkul/User`** | `packages/Webkul/User/src` | - `Webkul\User\Models\User` & `UserProxy`<br>- `Webkul\User\Contracts\User`<br>- `view_permission` scopes (`global`, `group`, `individual`) | - Do NOT hardcode user lookup queries; use `UserProxy` / `UserRepository`.<br>- Do NOT alter `users` table for contact attributes. |
| **`Webkul/Admin`** | `packages/Webkul/Admin/src` | - `Webkul\Admin\Bouncer` & `bouncer()` helper for ACL<br>- Blade layout `<x-admin::layouts>`<br>- Blade components (`<x-admin::datagrid>`, `<x-admin::modal>`, `<x-admin::form>`, `<x-admin::button>`, `<x-admin::breadcrumbs>`)<br>- Middleware `user`, `admin_locale`<br>- Global route prefix `config('app.admin_path')` | - Do NOT place Contact views, controllers, or routes inside `Webkul/Admin`.<br>- Do NOT introduce Contact-specific methods to `Bouncer` or `AdminServiceProvider`. |
| **`Webkul/DataGrid`** | `packages/Webkul/DataGrid/src` | - `Webkul\DataGrid\DataGrid` base class<br>- Column types (`Text`, `Boolean`, `Date`, `Datetime`, `Integer`, `Decimal`)<br>- Mass actions & record actions engine<br>- `DataGridExport` engine (CSV, XLS, XLSX) | - Do NOT write manual table rendering when DataGrid handles sorting, filtering, and export.<br>- Do NOT use DataGrid as a public business API (it is presentation-only). |
| **`Webkul/Installer`** | `packages/Webkul/Installer/src` | - Seeded country/state reference data (`countries.json`, `states.json`) | - Do NOT re-seed duplicate country master tables. |
| **`Laraseed/PackageGenerator`** | `packages/Laraseed/PackageGenerator/src` | - Artisan generators for package scaffolding, models, contracts, repositories, migrations, requests, controllers, routes, seeders, datagrids, admin capabilities, events, listeners | - Package generator commands produce standard skeletons; domain logic is filled manually without mutating the generator itself. |

---

## 3. Contacts Domain Definition

### 3.1 What is a "Contact"?
In Laraseed, a `Contact` represents a **Party Master Entity** (an individual Person or an Organization/Company) with whom the organization interacts or maintains business relations.

`Contacts` is an autonomous directory and master-data package. It is **NOT** a CRM, NOT an ERP, NOT a Sales package, and NOT an Accounting module.

### 3.2 Downstream Business Consumers (Future Packages)
The `Contacts` package serves as a foundational party directory for future independent business packages:
- **CRM / Leads / Deals:** A lead or opportunity references a `contact_id` for personal and organizational communication.
- **Sales & Invoicing:** Customers in the Sales domain reference a `contact_id` for billing, commercial terms, and shipping addresses.
- **Purchasing & Suppliers:** Suppliers and vendors reference a `contact_id` for purchase orders and vendor master records.
- **Accounting / Ledger:** Accounts Payable / Accounts Receivable sub-ledger accounts link to a `contact_id`.
- **Support & Helpdesk:** Support tickets link to a `contact_id` as the requester or organization.
- **Projects & Collaboration:** Client stakeholders and external project contributors link to a `contact_id`.
- **HR & Staff:** An employee profile may link to a `contact_id` for party identity while HR retains employee-specific payroll and contract data.

---

## 4. Entity & Data Model Decisions

### 4.1 Detailed Field Classification
Each field candidate has been rigorously evaluated against domain necessity, storage efficiency, and architectural purity:

| Field Name | Type / Constraints | Classification | Domain Justification & Decision |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED AUTO_INCREMENT PRIMARY` | **REQUIRED** | Immutable unique surrogate identity. |
| `type` | `VARCHAR(20)` / Enum (`person`, `organization`) | **REQUIRED** | Discriminator defining party entity kind. Default: `person`. |
| `name` | `VARCHAR(255)` | **REQUIRED** | Universal display/search name. For person: generated from `first_name` + `last_name` (or explicit name). For organization: `organization_name`. Indexed for search and DataGrid. |
| `first_name` | `VARCHAR(100)` NULLABLE | **OPTIONAL** | Given name for individuals (`type = person`). |
| `middle_name` | `VARCHAR(100)` NULLABLE | **OPTIONAL** | Middle name / patronymic for individuals. |
| `last_name` | `VARCHAR(100)` NULLABLE | **OPTIONAL** | Family/surname for individuals. |
| `organization_name`| `VARCHAR(255)` NULLABLE | **OPTIONAL** | Legal or trading name for organizations (`type = organization`), or employer/affiliation for individuals. |
| `job_title` | `VARCHAR(100)` NULLABLE | **OPTIONAL** | Position or functional title of an individual within their organization. |
| `email` | `VARCHAR(255)` NULLABLE | **OPTIONAL** | Primary email address. Lowercased, trimmed. Indexed for fast lookup. |
| `phone` | `VARCHAR(50)` NULLABLE | **OPTIONAL** | Primary telephone number (E.164 compatible string). Indexed for fast search. |
| `mobile` | `VARCHAR(50)` NULLABLE | **OPTIONAL** | Secondary / cellular telephone number. |
| `tax_number` | `VARCHAR(50)` NULLABLE | **OPTIONAL** | VAT/Tax registration / CR number. Essential for B2B transactions across GCC/EU/US. |
| `website` | `VARCHAR(255)` NULLABLE | **OPTIONAL** | Web URL / Corporate portal. |
| `address_line_1` | `VARCHAR(255)` NULLABLE | **OPTIONAL** | Primary street address line 1. |
| `address_line_2` | `VARCHAR(255)` NULLABLE | **OPTIONAL** | Suite, unit, apartment, or secondary address information. |
| `city` | `VARCHAR(100)` NULLABLE | **OPTIONAL** | City / Municipality. |
| `state` | `VARCHAR(100)` NULLABLE | **OPTIONAL** | State, province, or administrative region. |
| `postal_code` | `VARCHAR(20)` NULLABLE | **OPTIONAL** | Postal / ZIP code. |
| `country_code` | `CHAR(2)` NULLABLE | **OPTIONAL** | ISO 3166-1 alpha-2 country code (e.g. `SA`, `AE`, `EG`, `US`, `GB`). Matches `Webkul\Core\Models\Country::code`. |
| `notes` | `TEXT` NULLABLE | **OPTIONAL** | Free-text staff/internal notes regarding the contact. Sanitized upon storage. |
| `is_active` | `BOOLEAN` DEFAULT `1` | **REQUIRED** | Operational status flag. Indexed for active/inactive filtering. |
| `created_at` | `TIMESTAMP` NULLABLE | **REQUIRED** | Creation timestamp. |
| `updated_at` | `TIMESTAMP` NULLABLE | **REQUIRED** | Last update timestamp. |

### 4.2 Rejected / Deferred Field Candidates
- `is_customer`, `is_supplier`, `is_lead`, `is_employee`: **UNNECESSARY / FORBIDDEN IN CONTACTS**. (These violate role boundaries; see Section 6).
- `avatar` / `image`: **FUTURE / OPTIONAL EXTENSION**. V1 will use standard SVG initials avatar generated from `name` via `<x-admin::avatar>`.
- `social_profiles` / `custom_fields`: **DEFERRED TO EXTENSION**. Keep V1 lean and cohesive.

---

## 5. Person vs. Organization Design

### 5.1 Architectural Comparison

```text
Option A: Single Table with Type Discriminator (Chosen)
+-------------------------------------------------------------+
| contacts                                                    |
| id | type | name | first_name | last_name | org_name | ...  |
+-------------------------------------------------------------+

Option B: Multi-Table Joined / Class Table Inheritance (Rejected)
+------------------------------------+
| contacts                           |
| id | type | display_name | email   |
+-----------------+------------------+
                  |
        +---------+---------+
        |                   |
+-------v---------+   +-----v-------------+
| contact_people  |   | contact_orgs      |
| first | last    |   | legal_name | tax  |
+-----------------+   +-------------------+
```

### 5.2 Rationale for Single Table (`contacts`)
1. **Query Performance & Simplicity:** DataGrids, full-text searches, and API index queries execute on a single physical table with zero multi-table JOIN overhead.
2. **Unified Party Interface:** 90% of contact operations (listing, filtering by status/country, lookup by phone/email, linking to invoices or tickets) treat Persons and Organizations identically.
3. **No Schema Fragility:** Avoids cascading deletes, foreign key synchronization issues, and polymorphic query complexity.
4. **Clean Model Representation:** The `Contact` model encapsulates helper methods:
   - `$contact->isPerson(): bool`
   - `$contact->isOrganization(): bool`
   - `$contact->displayName(): string`

---

## 6. Contact Roles Boundary

### 6.1 Strict Anti-Pattern Avoidance
Adding role flags (`is_customer`, `is_supplier`, `is_employee`, `is_donor`) directly into `contacts` table is a severe architectural flaw:
- It creates tight coupling between the party master and domain packages that might never be installed.
- It requires migrations on `contacts` whenever a new downstream domain is introduced.

### 6.2 Recommended Downstream Integration Pattern
Downstream packages own their domain models and reference `contacts.id` explicitly:

```text
[Contacts Package]
    Contact (Party Master: ID, Name, Email, Phone, Address, Type)
         ^                                   ^
         | (contact_id)                      | (contact_id)
[Sales Package]                     [Purchasing Package]
    Customer (Credit, Terms)            Supplier (Payment terms, Catalog)
```

- When `Sales` creates a customer, it associates it with a `contact_id`.
- If an application only installs `Contacts` and `Purchasing`, `Contacts` remains 100% functional with zero residual knowledge of `Sales` or `Customer`.

---

## 7. Cross-Package Identity & Public Integration Surface

Downstream packages requiring party information interact exclusively through declared public surfaces:

```text
Laraseed\Contacts\Contracts\Contact           <- Public Model Interface
Laraseed\Contacts\Contracts\ContactRepository <- Public Repository Interface
Laraseed\Contacts\Models\ContactProxy         <- Concord Dynamic Model Proxy
Laraseed\Contacts\Events\ContactCreated       <- Stable Domain Event
Laraseed\Contacts\Events\ContactUpdated       <- Stable Domain Event
Laraseed\Contacts\Events\ContactDeleted       <- Stable Domain Event
```

Downstream packages MUST NOT:
- Directly query internal unexported helpers or assume specific migration timestamps.
- Import internal controller requests or blade templates.

---

## 8. Public Contracts vs. Over-Engineering

To prevent ceremonial abstraction bloat, only the strictly necessary contracts are established:
1. `Laraseed\Contacts\Contracts\Contact`: Defines the interface for the Eloquent model (getters, property contracts, type checks).
2. `Laraseed\Contacts\Contracts\ContactRepository`: Defines standard persistence contracts extending `Webkul\Core\Eloquent\Repository`.
3. Model registration in Concord via `Laraseed\Contacts\Providers\ModuleServiceProvider`, allowing downstream packages to substitute or extend the model via `ContactProxy`.

---

## 9. Persistence Boundary & Schema Ownership

`Laraseed/Contacts` possesses **100% exclusive ownership** of its schema:
- **Table Name:** `contacts`
- **Migration Location:** `packages/Laraseed/Contacts/src/Database/Migrations/`
- **Model Location:** `packages/Laraseed/Contacts/src/Models/Contact.php`
- **Repository Location:** `packages/Laraseed/Contacts/src/Repositories/ContactRepository.php`
- **Seeder Location:** `packages/Laraseed/Contacts/src/Database/Seeders/ContactSeeder.php`

No other package may create, alter, or drop columns on `contacts` directly.

---

## 10. Database Design & Integrity Specification

### 10.1 Schema Blueprint
```php
Schema::create('contacts', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->string('type', 20)->default('person'); // person | organization
    $table->string('name')->index();
    
    // Person specific
    $table->string('first_name', 100)->nullable();
    $table->string('middle_name', 100)->nullable();
    $table->string('last_name', 100)->nullable();
    $table->string('job_title', 100)->nullable();
    
    // Organization specific / affiliation
    $table->string('organization_name', 255)->nullable();
    $table->string('tax_number', 50)->nullable()->index();
    
    // Communication & Web
    $table->string('email')->nullable()->index();
    $table->string('phone', 50)->nullable()->index();
    $table->string('mobile', 50)->nullable();
    $table->string('website', 255)->nullable();
    
    // Address
    $table->string('address_line_1', 255)->nullable();
    $table->string('address_line_2', 255)->nullable();
    $table->string('city', 100)->nullable();
    $table->string('state', 100)->nullable();
    $table->string('postal_code', 20)->nullable();
    $table->char('country_code', 2)->nullable()->index();
    
    // Metadata
    $table->text('notes')->nullable();
    $table->boolean('is_active')->default(true)->index();
    
    $table->timestamps();
    
    // Composite indexes for fast search & filtering
    $table->index(['type', 'is_active']);
});
```

### 10.2 Normalization & Soft Deletes Decision
- **Email Normalization:** Trimming and conversion to lowercase before persistence.
- **Phone Normalization:** Trimming and preservation of standard dialing characters (`+`, digits, spaces).
- **Country Code:** 2-character uppercase ISO code matching `Webkul\Core\Models\Country::code`.
- **Soft Deletes Evaluation:**
  - Soft deletes are **REJECTED** for `contacts` V1.
  - *Reasoning:* Soft deletes in master directory entities lead to foreign key integrity confusion, duplicate key collisions during re-creation, and subtle bugs in downstream relational queries.
  - *Lifecycle Enforcement:* Master records are deactivated via `is_active = false`. Hard deletion is allowed only if no dependent business transactions exist (enforced by DB foreign keys or repository protection).

---

## 11. Multi-Address & Cardinality Evaluation

### 11.1 Address Cardinality (Single vs. Child Table)
- **Decision for V1:** Store primary address directly on `contacts`.
- **Justification:** Avoids unnecessary `contact_addresses` table join queries for standard master records. Future multi-location / branch requirements can be added via an extension table (`contact_addresses`) without breaking the primary address fields.

### 11.2 Email / Phone Cardinality (Single vs. Child Table)
- **Decision for V1:** Direct columns (`email`, `phone`, `mobile`) on `contacts`.
- **Justification:** Covers 98% of party communication workflows while allowing direct indexed filtering in DataGrid.

---

## 12. Admin Capability & UI Integration Boundary

### 12.1 Decoupled Admin Architecture
The base package `Laraseed/Contacts` is presentation-neutral and functions independently of Admin UI or DataGrid.

When the Admin capability is activated, it registers `Laraseed\Contacts\Admin\Providers\AdminServiceProvider` via `extra.laraseed.capabilities.admin`.

```text
packages/Laraseed/Contacts/
├── composer.json (Declares capabilities.admin)
├── src/
│   ├── Providers/ContactsServiceProvider.php (Base package provider)
│   ├── Admin/
│   │   ├── Providers/AdminServiceProvider.php (Admin capability provider)
│   │   ├── Config/
│   │   │   ├── menu.php (Contributes to 'menu.admin')
│   │   │   └── acl.php  (Contributes to 'acl')
│   │   ├── Http/Controllers/ContactController.php
│   │   ├── DataGrids/ContactDataGrid.php
│   │   ├── Routes/web.php (Prefixed with admin_path)
│   │   └── Resources/
│   │       ├── views/ (Namespace: 'contacts_admin')
│   │       └── lang/  (Namespace: 'contacts_admin')
```

### 12.2 Admin UX Scope
1. **Contacts List (`admin.contacts.index`):**
   - Renders header with Title, Breadcrumbs, Quick Filter tabs (All, People, Organizations), and "Create Contact" button.
   - Interactive DataGrid showing ID, Name + Subtitle, Type badge, Email, Phone, Country, Status, and Actions.
2. **Create / Edit Contact Modal or Page (`admin.contacts.create`, `admin.contacts.edit`):**
   - Type selector (`Person` vs `Organization`).
   - Conditional fields: Person shows First/Middle/Last Name + Job Title; Organization shows Company Name + Tax Number.
   - Shared fields: Email, Phone, Mobile, Website, Address (Line 1, Line 2, City, State, Postal Code, Country), Notes, Status toggle.
   - AJAX form submission with immediate DataGrid refresh and flash notifications.
3. **Delete Contact Action (`admin.contacts.delete`):**
   - Individual DELETE action with confirmation dialog.
   - Mass delete & mass status update actions.

### 12.3 ACL Permissions Hierarchy
```text
contacts                     -> Root menu & section access
├── contacts.view            -> View listing and detail
├── contacts.create          -> Create new contact
├── contacts.edit            -> Update existing contact
└── contacts.delete          -> Delete contact(s)
```

---

## 13. DataGrid Specification

**Class:** `Laraseed\Contacts\Admin\DataGrids\ContactDataGrid` extending `Webkul\DataGrid\DataGrid`

### 13.1 Columns
- `id`: Integer, sortable, filterable.
- `name`: Text, sortable, searchable, filterable. Custom closure rendering name, type indicator, and job title / organization.
- `type`: Text / Badge, filterable (`person`, `organization`), sortable.
- `email`: Text, searchable, filterable, sortable.
- `phone`: Text, searchable, filterable.
- `country_code`: Text, filterable, sortable.
- `is_active`: Boolean (Active / Inactive badge), filterable, sortable.
- `created_at`: Date / Date range, sortable, filterable (`date_range`).

### 13.2 Actions & Mass Actions
- **Row Actions:** `edit` (GET/modal), `delete` (DELETE).
- **Mass Actions:** `delete` (POST to mass_delete), `update_status` (POST to mass_update with active/inactive options).

---

## 14. API Boundary Decision

### 14.1 REST API Decision for V1
- **Included in Base Package:** Yes, `Laraseed/Contacts` will provide a standard headless REST API under `src/Http/Controllers/Api/ContactController.php` and `src/Routes/api.php`.
- **Auth Guard:** `auth:sanctum`.
- **Endpoints:**
  - `GET /api/contacts` — Paginated list with search, sorting, and type filters.
  - `POST /api/contacts` — Create contact with validation.
  - `GET /api/contacts/{id}` — Retrieve contact details.
  - `PUT/PATCH /api/contacts/{id}` — Update contact details.
  - `DELETE /api/contacts/{id}` — Delete contact.

The API is owned completely by the base package and requires zero Admin dependencies.

---

## 15. Localization (Arabic & English Parity)

In strict adherence to Rule 06:
- **Base Package Static Namespace:** `contacts::app.*`
  - `src/Resources/lang/en/app.php`
  - `src/Resources/lang/ar/app.php`
- **Admin Capability Static Namespace:** `contacts_admin::app.*`
  - `src/Admin/Resources/lang/en/app.php`
  - `src/Admin/Resources/lang/ar/app.php`
- **Parity Requirement:** 100% key parity between `en` and `ar`.
- **RTL Presentation:** UI elements utilize direction-neutral utility classes (`gap-x`, `start-`, `end-`, `ms-`, `me-`).
- **Data Localization:** Contact names, addresses, and notes are user-authored invariant records (classified as `NON_TRANSLATABLE` master data per Rule 06).

---

## 16. Domain Events Specification

The package will publish 3 core domain events with minimal, stable payloads:
1. `Laraseed\Contacts\Events\ContactCreated` (Payload: `ContactContract $contact`)
2. `Laraseed\Contacts\Events\ContactUpdated` (Payload: `ContactContract $contact`, `array $dirtyAttributes`)
3. `Laraseed\Contacts\Events\ContactDeleted` (Payload: `int $contactId`)

**Consumers & Justification:**
- Activity logging and audit trails.
- Future downstream packages (e.g. Sales, CRM) synchronizing party metadata or updating denormalized search caches.
- Webhook dispatchers.

---

## 17. Security Architecture

1. **Mass Assignment Protection:** Explicit `$fillable` whitelist on `Contact` model.
2. **Input Validation:** Strict FormRequests (`StoreContactRequest`, `UpdateContactRequest`) enforcing:
   - `type`: `required|in:person,organization`
   - `first_name` / `last_name`: `required_if:type,person|max:100`
   - `organization_name`: `required_if:type,organization|max:255`
   - `email`: `nullable|email|max:255`
   - `phone`, `mobile`: `nullable|string|max:50`
   - `country_code`: `nullable|string|size:2`
   - `is_active`: `boolean`
3. **Authorization & Defense in Depth:**
   - Admin controllers enforce Bouncer permissions (`Bouncer::allow('contacts.create')`, etc.).
   - API endpoints enforce Sanctum token abilities.
4. **XSS & Output Sanitization:** Blade auto-escaping `{{ }}`, text sanitization on notes, and safe attributes rendering.

---

## 18. Package Removal & Dependency Verification

### 18.1 Removal Contract
1. Disable package in deployment composition (`LARASEED_OPTIONAL_PACKAGES` without `contacts`).
2. Remove directory `packages/Laraseed/Contacts/`.
3. Run `composer dump-autoload` and `php artisan config:clear`.
4. Result:
   - `FOUNDATION_SOURCE_EDITS_REQUIRED_TO_REMOVE_PACKAGE: 0`
   - Zero broken routes, zero orphaned providers, zero missing view hints.

### 18.2 Dependency Failure Fast Protection
If a future package `Acme/Sales` requires `contacts` in its `composer.json` (`extra.laraseed.requires = ["contacts"]`), enabling `sales` while `contacts` is disabled triggers `Webkul\Core\Exceptions\InvalidPackageComposition` at boot time with an explicit diagnostic message.

---

## 19. Package Generator Coverage & Gaps Analysis

### 19.1 Commands Applicable for Contacts Package Construction
The Laraseed V2 Package Generator provides complete CLI coverage to scaffold every component needed:

| Component | Artisan Command to Execute |
| :--- | :--- |
| Package Root | `php artisan laraseed:make-package Laraseed/Contacts` |
| Contact Model | `php artisan laraseed:make-model Laraseed/Contacts Contact` |
| Contract Interface | `php artisan laraseed:make-contract Laraseed/Contacts ContactContract` |
| Migration | `php artisan laraseed:make-migration Laraseed/Contacts create_contacts_table` |
| Repository | `php artisan laraseed:make-repository Laraseed/Contacts ContactRepository --model=Contact` |
| Requests | `php artisan laraseed:make-request Laraseed/Contacts StoreContactRequest`<br>`php artisan laraseed:make-request Laraseed/Contacts UpdateContactRequest` |
| API Controller | `php artisan laraseed:make-controller Laraseed/Contacts ContactController --api` |
| Events | `php artisan laraseed:make-event Laraseed/Contacts ContactCreated`<br>`php artisan laraseed:make-event Laraseed/Contacts ContactUpdated`<br>`php artisan laraseed:make-event Laraseed/Contacts ContactDeleted` |
| Database Seeder | `php artisan laraseed:make-seeder Laraseed/Contacts ContactSeeder` |
| Admin Capability | `php artisan laraseed:make-admin Laraseed/Contacts` |
| Admin DataGrid | `php artisan laraseed:make-datagrid Laraseed/Contacts ContactDataGrid --model=Contact` |

### 19.2 Generator Gaps Evaluation (`GENERATOR_GAP`)
- **Evaluation:** The Package Generator V2 commands generate syntactically valid, isolated skeletons adhering to the exact contracts of Laraseed.
- **Result:** **0 BLOCKING GENERATOR GAPS**.
- Adding custom validation rules, building Vue modal templates, specifying schema columns in migration, and adding translation strings constitute **standard business-specific implementation**, not missing generator tooling.

---

## 20. Proposed Package Directory Structure

```text
packages/Laraseed/Contacts/
├── composer.json
├── src/
│   ├── Config/
│   ├── Contracts/
│   │   ├── Contact.php
│   │   └── ContactRepository.php
│   ├── Database/
│   │   ├── Migrations/
│   │   │   └── 2026_10_01_000001_create_contacts_table.php
│   │   └── Seeders/
│   │       └── ContactSeeder.php
│   ├── Events/
│   │   ├── ContactCreated.php
│   │   ├── ContactUpdated.php
│   │   └── ContactDeleted.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       └── ContactController.php
│   │   └── Requests/
│   │       ├── StoreContactRequest.php
│   │       └── UpdateContactRequest.php
│   ├── Models/
│   │   ├── Contact.php
│   │   └── ContactProxy.php
│   ├── Repositories/
│   │   └── ContactRepository.php
│   ├── Providers/
│   │   ├── ContactsServiceProvider.php
│   │   └── ModuleServiceProvider.php
│   ├── Resources/
│   │   └── lang/
│   │       ├── en/
│   │       │   └── app.php
│   │       └── ar/
│   │           └── app.php
│   ├── Routes/
│   │   └── api.php
│   └── Admin/
│       ├── Config/
│       │   ├── acl.php
│       │   └── menu.php
│       ├── DataGrids/
│       │   └── ContactDataGrid.php
│       ├── Http/
│       │   └── Controllers/
│       │       └── ContactController.php
│       ├── Providers/
│       │   └── AdminServiceProvider.php
│       ├── Resources/
│       │   ├── lang/
│       │   │   ├── en/
│       │   │   │   └── app.php
│       │   │   └── ar/
│       │   │       └── app.php
│       │   └── views/
│       │       └── index.blade.php
│       └── Routes/
│           └── web.php
└── tests/
    ├── Feature/
    │   ├── ContactApiTest.php
    │   └── Admin/
    │       └── ContactAdminTest.php
    └── Unit/
        └── ContactModelTest.php
```

---

## 21. Dependency Graph & Boundary Verification

### 21.1 Dependency Direction Map

```text
+-------------------------------------------------------------+
|                      FOUNDATION                             |
|  (Webkul/Core, Webkul/User, Webkul/Admin, Webkul/DataGrid)   |
+-------------------------------------------------------------+
                              ^
                              | (Unidirectional Consumption)
+-----------------------------+-------------------------------+
|                 Laraseed/Contacts Package                   |
|  - Base Business Layer (Models, Repositories, API, Events)  |
|  - Admin Capability Layer (DataGrid, ACL, Menu, Admin UI)   |
+-------------------------------------------------------------+
                              ^
                              | (Optional Future Dependency)
+-----------------------------+-------------------------------+
|         Future Business Packages (Sales, CRM, etc.)          |
+-------------------------------------------------------------+
```

### 21.2 Forbidden Boundaries & Invariants
1. `Foundation -> Laraseed/Contacts`: **FORBIDDEN (0 references)**.
2. `Laraseed/Contacts (Base) -> Webkul/Admin`: **FORBIDDEN (0 dependencies in base package)**.
3. `Laraseed/Contacts (Base) -> Webkul/DataGrid`: **FORBIDDEN (DataGrid used only in Admin capability)**.
4. `Laraseed/Contacts -> Future Packages (Sales/CRM)`: **FORBIDDEN (Contacts is self-contained)**.

---

## 22. Phased Implementation Plan

The full implementation of the Contacts package will proceed through structured, verifiable micro-steps:

- **Step 02 — Base Package Scaffolding:** Generate `Laraseed/Contacts` via `laraseed:make-package` and configure composer metadata.
- **Step 03 — Persistence & Domain Models:** Create migration, `Contact` model, contract, proxy, and register with Concord `ModuleServiceProvider`.
- **Step 04 — Repository & Domain Operations:** Implement `ContactRepository`, FormRequests, Seeders, and Events.
- **Step 05 — Headless REST API:** Implement API Controller, routes (`src/Routes/api.php`), and feature tests.
- **Step 06 — Admin Capability Scaffolding:** Generate Admin layer via `laraseed:make-admin` and register Admin capability in `composer.json`.
- **Step 07 — DataGrid & Admin Management UI:** Implement `ContactDataGrid`, Admin Controller, Blade view with Vue modals, Menu & ACL configurations.
- **Step 08 — Static Localization Parity:** Complete `en` and `ar` translation files for base package and Admin capability.
- **Step 09 — End-to-End Certification & Removability Test:** Execute comprehensive test suite, verify multi-composition matrix, and certify zero-residue package removability.

---

## 23. Risks & Deferred Features

1. **Risk: Downstream Model Collision**
   - *Mitigation:* `ContactProxy` and Concord contract registration ensure clean resolution without monkey-patching.
2. **Deferred: Multi-Address & Branch Directory**
   - *Reason:* Unnecessary complexity for V1; easily extendable via a dedicated `contact_addresses` relation in future iterations.
3. **Deferred: Custom Fields / EAV**
   - *Reason:* Violates simplicity of V1. Standard JSON/metadata column or dedicated attributes extension can be introduced when needed.

---

## 24. Audit Conclusion & Gate Readiness

The domain architecture and isolation design for `Laraseed/Contacts` have been comprehensively audited and fully defined. The architecture is 100% compliant with Laraseed V2 standards, introduces zero coupling into Foundation, and is ready for phased implementation upon approval.
