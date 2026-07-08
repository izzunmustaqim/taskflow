# CLAUDE.md

Project-specific instructions for Claude Code.

## Project Overview

**TaskFlow** — a multi-tenant personal task management app with categories, soft deletes, activity logging, and a premium glassmorphism UI.

## Tech Stack

- **Backend:** PHP 8.3, Laravel 11
- **Frontend:** Vue 3 (Composition API `<script setup>`), Inertia.js, Tailwind CSS
- **Database:** SQLite (default) / PostgreSQL
- **Testing:** Pest PHP
- **Build:** Vite

## Architecture

### Layered Structure

```
Controllers  →  Services  →  Models
     ↓              ↓
  Inertia      Eloquent Queries
```

- **Controllers** (`app/Http/Controllers/`) — Handle HTTP, authorization via `Gate::authorize()`, delegate to services, return Inertia responses.
- **Services** (`app/Services/`) — Business logic layer. `TaskService`, `CategoryService`.
- **Models** (`app/Models/`) — Eloquent with query scopes, casts, relationships.
- **Enums** (`app/Enums/`) — Backed enums for `TaskStatus`, `TaskPriority` with `label()`, `color()`, and `options()` methods.
- **Policies** (`app/Policies/`) — Authorization gates. Enforce multi-tenant scoping.
- **Form Requests** (`app/Http/Requests/`) — Validation for store/update operations.
- **Vue Pages** (`resources/js/Pages/`) — Inertia page components, one per route.
- **Vue Components** (`resources/js/Components/`) — Reusable UI components.
- **Composables** (`resources/js/Composables/`) — Vue composition functions (`useDarkMode`, `useKeyboardShortcuts`).
- **Export/Import** (`app/Http/Controllers/ExportController.php`, `ImportController.php`) — CSV and PDF export, CSV import functionality.

### Multi-Tenant Security (CRITICAL)

This is a **single-database multi-tenant** app. Every query **must** be scoped to the authenticated user:

- **Never** expose or query data across users.
- Controllers use `Gate::authorize('action', Model::class)` before any operation.
- Policies compare `$user->id === $model->user_id`.
- Models provide `scopeForUser($query, $user)` for query scoping.
- Form Requests validate `category_id` belongs to the current user.

When adding new models or endpoints, always:
1. Create a Policy with proper ownership checks.
2. Register it in `AuthServiceProvider` (if not auto-discovered).
3. Gate-check in the controller before accessing data.

## Code Conventions

### PHP

- Use `declare(strict_types=1)` in all PHP files.
- Classes are `final`.
- Constructor injection with `private readonly` properties.
- Return type declarations on all methods.
- Use Enums over string constants.
- Use PHPDoc `@property` annotations on models for IDE support.

### Vue / TypeScript

- Use `<script setup lang="ts">` (Composition API only, no Options API).
- Use `defineProps` with TypeScript interfaces, `defineEmits` for events.
- Prefer composables for shared logic.
- Use Inertia's `router` for navigation, `usePage()` for shared props.
- Tailwind CSS for all styling — no custom CSS files.

### Database

- Migrations use descriptive names with timestamps.
- Use `SoftDeletes` on Task model.
- Foreign keys with proper constraints.
- Use Laravel factories for test data.

## Common Commands

```bash
# Development
npm run dev                  # Start Vite dev server
php artisan serve            # Start Laravel dev server

# Testing
php artisan test             # Run full test suite
php artisan test --filter=TaskTest  # Run specific test file

# Database
php artisan migrate          # Run migrations
php artisan migrate:refresh  # Reset and re-run migrations

# Docker
docker-compose up -d --build
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate
```

## File Structure Reference

```
app/
├── Enums/           # TaskStatus, TaskPriority
├── Http/
│   ├── Controllers/ # TaskController, CategoryController, DashboardController, ExportController, ImportController
│   ├── Middleware/   # HandleInertiaRequests
│   └── Requests/    # StoreTaskRequest, UpdateTaskRequest, etc.
├── Models/          # Task, Category, User, ActivityLog
├── Policies/        # TaskPolicy, CategoryPolicy
├── Providers/       # AppServiceProvider
└── Services/        # TaskService, CategoryService

resources/
├── js/
│   ├── Components/      # Reusable Vue components (badges, modals, buttons)
│   ├── Composables/     # useDarkMode, useKeyboardShortcuts
│   ├── Layouts/         # Authenticated, Guest
│   └── Pages/           # Inertia page components
│       ├── Auth/        # Login, Register, ForgotPassword, etc.
│       ├── Categories/  # Index, Create, Edit
│       ├── Tasks/       # Index, Create, Edit, Trash
│       ├── Dashboard.vue
│       └── Welcome.vue
└── views/
    └── exports/
        └── tasks-pdf.blade.php  # PDF export template

routes/
├── web.php          # Main routes (auth-protected)
└── auth.php         # Authentication routes

tests/Feature/       # Pest PHP feature tests
    ├── ExportTest.php    # CSV & PDF export tests (32 tests)
    └── ImportTest.php    # CSV import tests (20 tests)
```

## Testing Conventions

- Use **Pest PHP** syntax (`test()`, `it()`, `beforeEach()`).
- Authenticate with `actingAs($user)`.
- Assert Inertia responses with `assertInertia(fn ($page) => $page->component(...)->has(...))`.
- Test both positive flows and security (BOLA/IDOR prevention).
- Use `assertDatabaseHas`, `assertDatabaseMissing`, `assertSoftDeleted`.
- Factory methods: `Task::factory()->forUser($user)->create()`.

## Important Notes

- **SQLite** is the default database — avoid PostgreSQL-specific syntax in migrations unless adding a driver check.
- The app uses **Inertia.js** — there are no API routes. All routes return Inertia renders or redirects.
- Flash messages are passed via `->with('success', '...')` and displayed by `FlashMessage.vue`.
- Dark mode state is persisted in localStorage via `useDarkMode` composable.
- Keyboard shortcuts are registered via `useKeyboardShortcuts` composable.

## Export/Import Feature

### PDF Export (barryvdh/laravel-dompdf)
- **Route:** `GET /tasks/export/pdf`
- **Controller:** `ExportController@exportTasksPdf`
- **View:** `resources/views/exports/tasks-pdf.blade.php`
- **Features:**
  - Exports filtered tasks to PDF with styled table
  - Supports all filters (status, priority, category, label, search, due)
  - Color-coded status and priority badges
  - Shows filter description at top of PDF
  - Filename format: `tasks_YYYY-MM-DD_HHMMSS.pdf`

### CSV Export
- **Route:** `GET /tasks/export/csv`
- **Controller:** `ExportController@exportTasksCsv`
- **Features:**
  - Streamed download for large datasets
  - BOM prefix for Excel compatibility
  - Supports all filters

### CSV Import
- **Route:** `POST /tasks/import/csv`
- **Controller:** `ImportController@importTasksCsv`
- **Features:**
  - Validates CSV format and required columns
  - Creates tasks with categories and labels
  - Returns validation errors for invalid rows

### Testing Export Features
```bash
# Run all export tests
php artisan test --filter=ExportTest

# Run specific PDF export tests
php artisan test --filter="pdf export"

# Run specific CSV export tests
php artisan test --filter="csv export"
```
