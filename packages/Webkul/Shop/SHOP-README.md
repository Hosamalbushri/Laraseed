# Module: Shop

## 1. Database Layer
Main table:
- No dedicated Shop-owned table for events/student-account flows.
  - Uses `events`, `event_categories`, `event_student`, `students` from other packages.
  - Purpose: Shop layer orchestrates front routes/controllers/views.

Related tables (read/write integration):
- `events`
- `event_categories`
- `event_student`
- `students`

## 2. Model
- Model class name:
  - No primary model inside Shop for this flow.
  - Uses `Webkul\Event\Models\Event` and `Webkul\Student\Models\Student`.
- Location:
  - `packages/Webkul/Event/src/Models/Event.php`
  - `packages/Webkul/Student/src/Models/Student.php`
- Relationships (only list):
  - `Event::categories()`
  - `Event::images()`
  - `Event::fields()`
  - `Event::subscribers()`
  - `Student::subscribedEvents()`

## 3. Repository Layer
- Repository class:
  - `Webkul\Event\Repositories\EventRepository`
  - `Webkul\Event\Repositories\EventCategoryRepository`
- Contract interface:
  - No Shop-local repository contract for this flow.
- Purpose (1 line):
  - Shop controllers read events/categories through Event package repositories.
- Key methods (list only):
  - `EventRepository::getModel()`
  - `EventCategoryRepository::getModel()`
  - `EventSubscriptionService::subscribe()`
  - `EventSubscriptionService::unsubscribe()`

## 4. Business Flow (Important)
- Front Events Page -> `EventController@index` -> Event repositories -> `Event` model -> Database -> Shop views
- Subscribe/Unsubscribe -> `EventSubscriptionController` -> `EventSubscriptionService` -> `Event` + `event_student` -> Database -> JSON/UI update
- Student Account -> `StudentAccountController` -> `Student` model -> Database -> account views

## 5. Controllers

### Admin Controller
- Path: Not applicable in Shop package (admin controllers are in `packages/Webkul/Admin`).

### Front Controller (if exists)
- Path: `packages/Webkul/Shop/src/Http/Controllers/EventController.php`
- Methods:
  - `index`
  - `show`

- Path: `packages/Webkul/Shop/src/Http/Controllers/EventSubscriptionController.php`
- Methods:
  - `store`
  - `destroy`

- Path: `packages/Webkul/Shop/src/Http/Controllers/Student/StudentAccountController.php`
- Methods:
  - `edit`
  - `update`

- Path: `packages/Webkul/Shop/src/Http/Controllers/Student/StudentEventsController.php`
- Methods:
  - `index`

## 6. Views

### Admin Views
- Path: Not applicable in Shop package.

### Front Views
- Path: `packages/Webkul/Shop/src/Resources/views/events`
- Pages:
  - `index.blade.php` -> listing
  - `show.blade.php` -> details
  - `partials/event-card.blade.php` -> event card
  - `partials/event-subscribe-dialog.blade.php` -> subscribe modal
  - `partials/event-unsubscribe-dialog.blade.php` -> unsubscribe modal

- Path: `packages/Webkul/Shop/src/Resources/views/student`
- Pages:
  - `events/index.blade.php` -> student subscribed events
  - `account/edit.blade.php` -> student account form

## 7. Routes

### Admin Routes
- File path: Not in Shop package.
- Example routes: handled in Admin package.

### Front Routes
- File path: `packages/Webkul/Shop/src/Routes/web.php`
- File path: `packages/Webkul/Shop/src/Routes/portal-routes.php`
- File path: `packages/Webkul/Shop/src/Routes/student-routes.php`
- Example routes:
  - `GET /events` -> `shop.events.index`
  - `GET /events/{id}` -> `shop.events.show`
  - `POST /events/{id}/subscribe` -> `shop.events.subscribe`
  - `POST /events/{id}/unsubscribe` -> `shop.events.unsubscribe`
  - `GET /student/events` -> `shop.student.events.index`
  - `GET /student/account/edit` -> `shop.student.account.edit`

## 8. How Data is Loaded in Admin Panel
- Not handled by Shop package.
- Admin panel loads events/students via Admin controllers and datagrids.

## 9. Key Notes
- Important configs:
  - `packages/Webkul/Shop/src/Config/shop.php` (student login URL, events page settings)
- Special logic:
  - `EventSubscriptionService` enforces seat-safe transactions and availability checks.
  - Subscription endpoints require `auth:student` and throttle middleware.
  - `ResolvesStudentSubscribedEventIds` trait provides current student subscription IDs to front views.
- Permissions (if used):
  - Shop flow is guard/middleware-based, no ACL matrix in Shop package for these routes.
