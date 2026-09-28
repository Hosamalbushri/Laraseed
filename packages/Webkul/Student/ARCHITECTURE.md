# Webkul\Student — Architecture Manifest

## Package Classification
- **Domain**: Optional domain package (University authentication & Student identity entity).
- **Presentation Ownership**: Owns its own Admin UI (DataGrid, Controller, Views, FormRequests, ACL, Menu, MegaSearch & Quick Creation contributions).

## Owned Routes & Endpoints
- `admin.students.index` (GET `admin/students`)
- `admin.students.search` (GET `admin/students/search`)
- `admin.students.create` (GET `admin/students/create`)
- `admin.students.store` (POST `admin/students/create`)
- `admin.students.view` (GET `admin/students/view/{id}`)
- `admin.students.edit` (GET `admin/students/edit/{id}`)
- `admin.students.update` (PUT `admin/students/edit/{id}`)
- `admin.students.delete` (DELETE `admin/students/{id}`)
- `admin.students.mass_delete` (POST `admin/students/mass-delete`)
- `student.login`, `student.login.store`, `student.logout`

## ACL Contribution
- `students`
- `students.create`
- `students.edit`
- `students.view`
- `students.delete`

## Navigation Contribution
- `students` (route: `admin.students.index`, icon: `icon-contact`)

## Configuration Contribution
- `general.store.student_login`
- `general.university_api`
- `general.university_api.endpoint_settings`
- `general.settings.menu` field `'students'`

## Extension Points Dispatched
- Lifecycle hook `admin.students.datagrid.query.after`
- Lifecycle hook `admin.students.datagrid.columns.after`
- View render hook `admin.students.view.details.after`

## Dependency Invariants
- `Event -> Student` (One-way).
- `Student` contains **0** references or imports to Event packages or `event::` namespaces.
- Foundation packages (`Admin`, `Core`, `User`, `DataGrid`, `Installer`) contain **0** references to Student feature ownership.
