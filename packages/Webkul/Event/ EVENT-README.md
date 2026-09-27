# Module: Events

## 1. Database Layer
Main table:
- `events`
  - `id`
  - `title`
  - `event_date`
  - `event_end_date`
  - `organizer`
  - `available_seats`
  - `availability_use_seats`
  - `availability_use_end_date`
  - `image`
  - `description`
  - `status`
  - Purpose: stores event records and availability state.

Related tables:
- `event_categories`
  - `id`, `parent_id`, `name`, `description`, `sort_order`, `status`
- `event_event_category`
  - `id`, `event_id`, `event_category_id`
  - Purpose: many-to-many pivot between events and categories.
- `event_fields`
  - `id`, `event_id`, `name`, `type`, `value`
- `event_student`
  - `id`, `event_id`, `student_id`
  - Purpose: student subscriptions.
- `event_images`
  - `id`, `event_id`, `path`, `position`

## 2. Model
- Model class: `Webkul\Event\Models\Event`
- Path: `packages/Webkul/Event/src/Models/Event.php`
- Relationships:
  - `categories()`
  - `fields()`
  - `images()`
  - `subscribers()`

- Model class: `Webkul\Event\Models\EventCategory`
- Path: `packages/Webkul/Event/src/Models/EventCategory.php`
- Relationships:
  - `parent()`
  - `children()`
  - `events()`

## 3. Repository Layer
- Repository class: `Webkul\Event\Repositories\EventRepository`
  - Path: `packages/Webkul/Event/src/Repositories/EventRepository.php`
- Contract interface:
  - No dedicated repository contract interface in module.
  - Model contract used by repository: `Webkul\Event\Contracts\Event` (`packages/Webkul/Event/src/Contracts/Event.php`)
- Purpose: handles event CRUD, category sync, and custom-fields persistence.
- Key methods:
  - `model()`
  - `create(array $data)`
  - `update(array $data, $id, $attribute = 'id')`

## 4. Business Flow (Important)
- Admin Panel -> `EventController` -> `EventRepository` -> `Event` model -> Database -> Admin views
- Front Panel -> `EventController` / `EventSubscriptionController` -> `EventRepository` / `EventSubscriptionService` -> `Event` model -> Database -> Front views

## 5. Controllers

### Admin Controller
- Path: `packages/Webkul/Admin/src/Http/Controllers/Events/EventController.php`
- Main methods:
  - `index` -> loads datagrid JSON (AJAX) or events index page.
  - `create` -> returns create form page.
  - `store` -> validates request and creates event via repository.
  - `update` -> validates request and updates event via repository.
  - `destroy` -> deletes event and returns JSON result.
  - `edit` -> loads event with relations for edit form.
  - `search` -> returns event list for AJAX search.

### Front Controller (if exists)
- Path: `packages/Webkul/Shop/src/Http/Controllers/EventController.php`
- Methods:
  - `index`
  - `show`

- Path: `packages/Webkul/Shop/src/Http/Controllers/EventSubscriptionController.php`
- Methods:
  - `store`
  - `destroy`

## 6. Views

### Admin Views
- Path: `packages/Webkul/Admin/src/Resources/views/events`
- Pages:
  - `index.blade.php` -> listing (datagrid)
  - `create.blade.php` -> form
  - `edit.blade.php` -> update
- Category pages:
  - `categories/index.blade.php` -> listing
  - `categories/create.blade.php` -> form
  - `categories/edit.blade.php` -> update
- Components:
  - Uses `x-admin::*` Blade components.
  - Uses Vue component partials in `partials/form-vue.blade.php`.

### Front Views
- Path: `packages/Webkul/Shop/src/Resources/views/events`
- Pages:
  - `index.blade.php` -> events listing and filters
  - `show.blade.php` -> single event details
  - `partials/event-card.blade.php` -> event card UI
  - `partials/event-subscribe-dialog.blade.php` -> subscribe modal
  - `partials/event-unsubscribe-dialog.blade.php` -> unsubscribe modal

## 7. Routes

### Admin Routes
- File path: `packages/Webkul/Admin/src/Routes/Admin/events-routes.php`
- Example routes:
  - `GET /admin/events/events` -> `admin.events.index`
  - `POST /admin/events/events/create` -> `admin.events.store`
  - `GET /admin/events/categories/tree` -> `admin.events.categories.tree`

### Front Routes
- File path: `packages/Webkul/Shop/src/Routes/portal-routes.php`
- File path: `packages/Webkul/Shop/src/Routes/student-routes.php`
- Included from: `packages/Webkul/Shop/src/Routes/web.php`
- Example routes:
  - `GET /events` -> `shop.events.index`
  - `GET /events/{id}` -> `shop.events.show`
  - `POST /events/{id}/subscribe` -> `shop.events.subscribe`
  - `POST /events/{id}/unsubscribe` -> `shop.events.unsubscribe`

## 8. How Data is Loaded in Admin Panel
- Controller: `EventController@index`
- Repository/model source:
  - index grid data is loaded through `EventDataGrid` from events datasource.
  - edit page loads entity via `EventRepository->with(...)->findOrFail($id)`.
- View renderer:
  - list page: `admin::events.index`
  - edit page: `admin::events.edit`

## 9. Key Notes
- Important configs:
  - ACL permissions in `packages/Webkul/Admin/src/Config/acl.php`
  - Admin menu wiring in `packages/Webkul/Admin/src/Config/menu.php`
- Special logic:
  - `EventSubscriptionService` uses DB transaction and `lockForUpdate()` for seat-safe subscribe/unsubscribe.
  - `Event::isCurrentlyAvailable()` and `scopePublished()` control visibility/availability checks.
- Permissions:
  - `events`, `events.create`, `events.edit`, `events.delete`
  - `events.categories`, `events.categories.create`, `events.categories.edit`, `events.categories.delete`
