# Webkul Event Package Architecture

```text
PACKAGE:
Webkul\Event

TYPE:
OPTIONAL_FEATURE

FOUNDATION_DEPENDENCIES:
Webkul\Core
Webkul\Admin infrastructure
Webkul\User authentication and authorization infrastructure
Webkul\DataGrid

OPTIONAL_DEPENDENCIES:
Webkul\Student (Composer package: webkul/student)

MAIN_PROVIDER:
Webkul\Event\Providers\EventServiceProvider

MODULE_PROVIDER:
Webkul\Event\Providers\ModuleServiceProvider

EVENT_PROVIDER:
N/A

PUBLIC_CONTRACTS:
Webkul\Event\Contracts\Event
Webkul\Event\Contracts\EventCategory
Webkul\Event\Contracts\EventField

PUBLIC_APPLICATION_SERVICES:
Webkul\Event\Services\EventSubscriptionService

MODEL_REPLACEMENTS:
N/A

RUNTIME_RELATION_EXTENSIONS:
Webkul\Student\Models\Student::subscribedEvents

PUBLISHED_EVENTS:
N/A

LISTENED_EVENTS:
admin.students.view.details.after
admin.students.datagrid.query.after
admin.students.datagrid.columns.after
admin.dashboard.index.content.left
admin.dashboard.index.content.right
admin.components.layouts.header.desktop.mega_search.results
admin.components.layouts.header.mobile.mega_search.results
admin.components.layouts.header.quick_creation
Webkul\Student\Models\Student deleting lifecycle callback

UI_EXTENSION_POINTS:
Admin view render events for dashboard, Student subscriptions, Mega Search, and Quick Creation

QUERY_CONTRIBUTIONS:
DashboardStatsRegistry Event metrics
Student DataGrid subscription count query and column
MegaSearch Event registration

ROUTES:
src/Routes/admin-routes.php
src/Routes/breadcrumbs.php

ACL:
src/Config/acl.php

MENU:
src/Config/menu.php

CONFIG:
src/Config/core_config.php

MIGRATIONS:
src/Database/Migrations

ADMIN_PRESENTATION:
src/Http/Controllers/Admin
src/Http/Requests/Admin
src/DataGrids/Admin
src/Resources/views/admin

FRONTEND_PRESENTATION:
Provided by the dependent Webkul\Shop integration; remediation is deferred to the Shop architecture wave.

DATA_INSTALL/UNINSTALL POLICY:
Install Event migrations after Student because event_student references students. Uninstall or retain Event data only through an explicitly approved deployment procedure; historical migrations are not rewritten.

FOUNDATION_SOURCE_EDITS_REQUIRED_TO_REMOVE:
0
```

The public surface is the listed model contracts and `EventSubscriptionService`. Repositories, controllers, FormRequests, listeners, views, configuration files, migrations, and `EventWriteService` are package internals. Shop's current repository imports are legacy consumers to be replaced by a deliberate Event query API during the Shop architecture wave.

## Primary image projection contract

```text
COMPATIBILITY_CONTRACT:
events.image mirrors the first canonical event_images row for existing storefront consumers. Event never rebuilds the gallery from this column.

CALLERS:
Webkul\Shop event-card and event-detail presentation

DEPRECATION_STATUS:
Supported projection pending the Shop architecture wave

REMOVAL_CONDITION:
Shop consumes a deliberate Event presentation/query API backed by event_images, followed by a separately approved schema migration.
```
