# Module: Student

## 1. Database Layer
Main table:
- `students`
  - `id`
  - `university_card_number` (unique)
  - `password`
  - `name`
  - `registration_number`
  - `major`
  - `academic_level`
  - `profile_image`
  - Purpose: stores student auth credentials and profile data.

Related tables:
- `event_student` (integration with Events package)
  - `id`, `event_id`, `student_id`
  - Purpose: maps student subscriptions to events.

## 2. Model
- Model class: `Webkul\Student\Models\Student`
- Location: `packages/Webkul/Student/src/Models/Student.php`
- Relationships:
  - `subscribedEvents()`

## 3. Repository Layer
- Repository class: `Webkul\Student\Repositories\StudentRepository`
- Contract interface:
  - No dedicated repository contract interface in this package.
  - Service contract used by login flow: `Webkul\Student\Services\Contracts\UniversityStudentApiContract`
- Purpose: data access for student CRUD/search and login-related persistence.
- Key methods (list only):
  - `model()`

## 4. Business Flow (Important)
- Student Login Page -> `StudentSessionController@store` -> Local auth or University API verify -> `Student` model -> Database
- Admin Students -> `StudentController` -> `StudentRepository` -> `Student` model -> Database -> Admin views

## 5. Controllers

### Admin Controller
- Path: `packages/Webkul/Admin/src/Http/Controllers/Students/StudentController.php`
- Main methods:
  - `index` -> returns student list page or datagrid JSON.
  - `create` -> returns create form.
  - `store` -> creates student.
  - `update` -> updates student.
  - `destroy` -> deletes a student.
  - `show` -> loads student details + subscriptions.
  - `search` -> returns student search JSON.
  - `massDestroy` -> deletes selected students.
  - `storeSubscription` -> subscribes student to event.
  - `destroySubscription` -> unsubscribes student from event.

### Front Controller (if exists)
- Path: `packages/Webkul/Student/src/Http/Controllers/StudentSessionController.php`
- Methods:
  - `create`
  - `store`
  - `destroy`

## 6. Views

### Admin Views
- Path: `packages/Webkul/Admin/src/Resources/views/students`
- Pages:
  - `index.blade.php` -> listing
  - `create.blade.php` -> form
  - `edit.blade.php` -> update
  - `view.blade.php` -> student details/subscriptions
- Uses `x-admin::*` components in admin pages.

### Front Views
- Path: `packages/Webkul/Student/src/Resources/views/sessions`
- Pages:
  - `create.blade.php` -> student login form

## 7. Routes

### Admin Routes
- File path: `packages/Webkul/Admin/src/Routes/Admin/students-routes.php`
- Example routes:
  - `GET /admin/students` -> `admin.students.index`
  - `POST /admin/students/create` -> `admin.students.store`
  - `POST /admin/students/{id}/subscriptions` -> `admin.students.subscriptions.store`

### Front Routes
- File path: `packages/Webkul/Student/src/Routes/web.php`
- Example routes:
  - `GET /student/login` -> `student.login`
  - `POST /student/login` -> `student.login.store`
  - `POST /student/logout` -> `student.logout`

## 8. How Data is Loaded in Admin Panel
- Controller: `StudentController@index`
- Repository method used: Data is loaded via `StudentDataGrid` query source; single records via repository/model fetch in controller actions.
- View renders:
  - `admin::students.index` for list
  - `admin::students.view` for details

## 9. Key Notes
- Important configs:
  - `packages/Webkul/Student/src/Config/student.php` (university API and redirects)
  - `packages/Webkul/Admin/src/Config/core_config.php` (`general.store.student_login.*`, API endpoint settings)
- Special logic:
  - `StudentServiceProvider` binds real/fake university API client.
  - Login flow supports local auth first, then API-based first-time provisioning.
  - Login throttling via `student-login` limiter.
- Permissions (if used):
  - `students`, `students.create`, `students.edit`, `students.view`, `students.delete`, `students.manage-subscriptions`
