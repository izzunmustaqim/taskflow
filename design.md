# TaskFlow - System Design

Architectural decisions, database schemas, and API contracts for the Task Management Application.

---

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                        CLIENT                               │
│  ┌─────────────────────────────────────────────────────┐   │
│  │              Vue 3 (Composition API)                │   │
│  │  ┌─────────┐  ┌─────────┐  ┌─────────┐            │   │
│  │  │  Pages  │  │Components│  │ Composables│          │   │
│  │  └─────────┘  └─────────┘  └─────────┘            │   │
│  └─────────────────────────────────────────────────────┘   │
│                           │                                 │
│                    Inertia.js                               │
└───────────────────────────┼─────────────────────────────────┘
                            │
┌───────────────────────────┼─────────────────────────────────┐
│                        SERVER                               │
│  ┌─────────────────────────────────────────────────────┐   │
│  │                   Laravel 11                        │   │
│  │  ┌─────────┐  ┌─────────┐  ┌─────────┐            │   │
│  │  │Controllers│ │ Services │  │ Policies │            │   │
│  │  └─────────┘  └─────────┘  └─────────┘            │   │
│  │  ┌─────────┐  ┌─────────┐  ┌─────────┐            │   │
│  │  │ Requests │  │  Models  │  │  Enums   │            │   │
│  │  └─────────┘  └─────────┘  └─────────┘            │   │
│  └─────────────────────────────────────────────────────┘   │
│                           │                                 │
└───────────────────────────┼─────────────────────────────────┘
                            │
┌───────────────────────────┼─────────────────────────────────┐
│                     DATABASE                                │
│  ┌─────────────────────────────────────────────────────┐   │
│  │              SQLite / PostgreSQL                    │   │
│  │  ┌─────────┐  ┌─────────┐  ┌─────────┐            │   │
│  │  │  users  │  │categories│  │  tasks   │            │   │
│  │  └─────────┘  └─────────┘  └─────────┘            │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

---

## Tech Stack

| Layer | Technology | Version | Purpose |
|-------|-----------|---------|---------|
| Frontend | Vue.js | 3.x | Reactive UI framework |
| Frontend | Inertia.js | Latest | Server-driven SPA experience |
| Frontend | Tailwind CSS | 3.x | Utility-first CSS framework |
| Frontend | Vite | Latest | Build tool and dev server |
| Backend | Laravel | 11.x | PHP framework |
| Backend | PHP | 8.3+ | Server-side language |
| Database | SQLite | Latest | Default database (dev) |
| Database | PostgreSQL | Latest | Production database option |
| Testing | Pest PHP | Latest | Testing framework |
| Deployment | Docker | Latest | Containerization |

---

## Database Schema

### Entity Relationship Diagram

```
┌──────────────────┐       ┌──────────────────┐       ┌──────────────────┐
│      users       │       │    categories    │       │      tasks       │
├──────────────────┤       ├──────────────────┤       ├──────────────────┤
│ id (PK)          │◄──┐   │ id (PK)          │◄──┐   │ id (PK)          │
│ name             │   │   │ user_id (FK)     │───┘   │ user_id (FK)     │───┐
│ email            │   │   │ name             │       │ category_id (FK) │───┘
│ email_verified_at│   │   │ color            │       │ title            │
│ password         │   │   │ created_at       │       │ description      │
│ remember_token   │   │   │ updated_at       │       │ status           │
│ created_at       │   │   └──────────────────┘       │ priority         │
│ updated_at       │   │                              │ due_at           │
└──────────────────┘   │                              │ deleted_at       │
                       │                              │ created_at       │
                       │                              │ updated_at       │
                       │                              └──────────────────┘
                       │                                     │
                       └─────────────────────────────────────┘
                              One-to-Many Relationship
```

### Table Definitions

#### users Table

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

#### categories Table

```sql
CREATE TABLE categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    color VARCHAR(7) NOT NULL,  -- Hex color code (#RRGGBB)
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_categories_user_id ON categories(user_id);
```

#### tasks Table

```sql
CREATE TABLE tasks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    status ENUM('pending', 'in_progress', 'completed') NOT NULL DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high', 'urgent') NOT NULL DEFAULT 'medium',
    due_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,  -- Soft delete
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE INDEX idx_tasks_user_id ON tasks(user_id);
CREATE INDEX idx_tasks_category_id ON tasks(category_id);
CREATE INDEX idx_tasks_status ON tasks(status);
CREATE INDEX idx_tasks_priority ON tasks(priority);
CREATE INDEX idx_tasks_due_at ON tasks(due_at);
CREATE INDEX idx_tasks_deleted_at ON tasks(deleted_at);
```

---

## Enums

### TaskStatus

```php
<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    public function label(): string
    {
        return match($this) {
            self::Pending => 'Pending',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
        };
    }

    public static function options(): array
    {
        return [
            self::Pending->value => self::Pending->label(),
            self::InProgress->value => self::InProgress->label(),
            self::Completed->value => self::Completed->label(),
        ];
    }
}
```

### TaskPriority

```php
<?php

namespace App\Enums;

enum TaskPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match($this) {
            self::Low => 'Low',
            self::Medium => 'Medium',
            self::High => 'High',
            self::Urgent => 'Urgent',
        };
    }

    public static function options(): array
    {
        return [
            self::Low->value => self::Low->label(),
            self::Medium->value => self::Medium->label(),
            self::High->value => self::High->label(),
            self::Urgent->value => self::Urgent->label(),
        ];
    }
}
```

---

## Models

### User Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }
}
```

### Task Model

```php
<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'description',
        'status',
        'priority',
        'due_at',
    ];

    protected $casts = [
        'status' => TaskStatus::class,
        'priority' => TaskPriority::class,
        'due_at' => 'datetime',
    ];

    // Scopes
    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeFilter($query, array $filters)
    {
        // Multi-parameter filtering logic
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
```

### Category Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'color',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
```

---

## Services

### TaskService

```php
<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TaskService
{
    public function list(User $user, array $filters = []): LengthAwarePaginator
    {
        return Task::forUser($user)
            ->with('category')
            ->filter($filters)
            ->latest()
            ->paginate(12);
    }

    public function listTrashed(User $user): LengthAwarePaginator
    {
        return Task::forUser($user)
            ->onlyTrashed()
            ->with('category')
            ->latest()
            ->paginate(12);
    }

    public function create(User $user, array $data): Task
    {
        return Task::create([
            ...$data,
            'user_id' => $user->id,
        ]);
    }

    public function update(Task $task, array $data): bool
    {
        return $task->update($data);
    }

    public function delete(Task $task): bool
    {
        return $task->delete(); // Soft delete
    }

    public function restore(Task $task): bool
    {
        return $task->restore();
    }

    public function forceDelete(Task $task): bool
    {
        return $task->forceDelete();
    }

    public function getStats(User $user): array
    {
        return [
            'total' => Task::forUser($user)->count(),
            'pending' => Task::forUser($user)->where('status', 'pending')->count(),
            'in_progress' => Task::forUser($user)->where('status', 'in_progress')->count(),
            'completed' => Task::forUser($user)->where('status', 'completed')->count(),
        ];
    }
}
```

### CategoryService

```php
<?php

namespace App\Services;

use App\Models\Category;
use App\Models\User;

class CategoryService
{
    public function list(User $user): Collection
    {
        return Category::forUser($user)
            ->orderBy('name')
            ->get();
    }

    public function create(User $user, array $data): Category
    {
        return Category::create([
            ...$data,
            'user_id' => $user->id,
        ]);
    }

    public function update(Category $category, array $data): bool
    {
        return $category->update($data);
    }

    public function delete(Category $category): bool
    {
        return $category->delete();
    }
}
```

---

## Policies

### TaskPolicy

```php
<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Scoped in query
    }

    public function view(User $user, Task $task): bool
    {
        return $task->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Task $task): bool
    {
        return $task->user_id === $user->id;
    }

    public function delete(User $user, Task $task): bool
    {
        return $task->user_id === $user->id;
    }

    public function restore(User $user, Task $task): bool
    {
        return $task->user_id === $user->id;
    }

    public function forceDelete(User $user, Task $task): bool
    {
        return $task->user_id === $user->id;
    }
}
```

### CategoryPolicy

```php
<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Category $category): bool
    {
        return $category->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Category $category): bool
    {
        return $category->user_id === $user->id;
    }

    public function delete(User $user, Category $category): bool
    {
        return $category->user_id === $user->id;
    }
}
```

---

## Form Requests

### StoreTaskRequest

```php
<?php

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::enum(TaskStatus::class)],
            'priority' => ['required', Rule::enum(TaskPriority::class)],
            'category_id' => [
                'nullable',
                'exists:categories,id',
                Rule::in(
                    Category::where('user_id', $userId)->pluck('id')
                ),
            ],
            'due_at' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }
}
```

### StoreCategoryRequest

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }
}
```

---

## Routes

### Web Routes

```php
<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::get('/', function () {
    return Inertia::render('Welcome');
});

// Protected Routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Tasks (including soft delete routes)
    Route::get('/tasks/trash', [TaskController::class, 'trash'])->name('tasks.trash');
    Route::post('/tasks/{task}/restore', [TaskController::class, 'restore'])
        ->name('tasks.restore')
        ->withTrashed();
    Route::delete('/tasks/{task}/force', [TaskController::class, 'forceDestroy'])
        ->name('tasks.force-destroy')
        ->withTrashed();
    Route::resource('tasks', TaskController::class);

    // Categories
    Route::resource('categories', CategoryController::class)->except(['show']);

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
```

### Route Summary

| Method | URI | Name | Controller | Purpose |
|--------|-----|------|------------|---------|
| GET | / | - | Closure | Landing page |
| GET | /dashboard | dashboard | DashboardController | Dashboard view |
| GET | /tasks | tasks.index | TaskController@index | Task list |
| GET | /tasks/create | tasks.create | TaskController@create | Create task form |
| POST | /tasks | tasks.store | TaskController@store | Store task |
| GET | /tasks/{task} | tasks.show | TaskController@show | View task |
| GET | /tasks/{task}/edit | tasks.edit | TaskController@edit | Edit task form |
| PUT | /tasks/{task} | tasks.update | TaskController@update | Update task |
| DELETE | /tasks/{task} | tasks.destroy | TaskController@destroy | Soft delete task |
| GET | /tasks/trash | tasks.trash | TaskController@trash | Trash view |
| POST | /tasks/{task}/restore | tasks.restore | TaskController@restore | Restore task |
| DELETE | /tasks/{task}/force | tasks.force-destroy | TaskController@forceDestroy | Force delete |
| GET | /categories | categories.index | CategoryController@index | Category list |
| GET | /categories/create | categories.create | CategoryController@create | Create category form |
| POST | /categories | categories.store | CategoryController@store | Store category |
| GET | /categories/{category}/edit | categories.edit | CategoryController@edit | Edit category form |
| PUT | /categories/{category} | categories.update | CategoryController@update | Update category |
| DELETE | /categories/{category} | categories.destroy | CategoryController@destroy | Delete category |
| GET | /profile | profile.edit | ProfileController@edit | Profile form |
| PATCH | /profile | profile.update | ProfileController@update | Update profile |
| DELETE | /profile | profile.destroy | ProfileController@destroy | Delete account |

---

## Frontend Architecture

### Component Structure

```
resources/js/
├── Components/
│   ├── Pagination.vue
│   ├── TaskCard.vue
│   └── ...
├── Layouts/
│   ├── AuthenticatedLayout.vue
│   └── GuestLayout.vue
├── Pages/
│   ├── Auth/
│   │   ├── Login.vue
│   │   ├── Register.vue
│   │   └── ...
│   ├── Categories/
│   │   ├── Index.vue
│   │   ├── Create.vue
│   │   └── Edit.vue
│   ├── Tasks/
│   │   ├── Index.vue
│   │   ├── Create.vue
│   │   ├── Edit.vue
│   │   └── Trash.vue
│   ├── Profile/
│   │   └── Edit.vue
│   ├── Dashboard.vue
│   └── Welcome.vue
├── composables/
│   └── useFilters.ts
└── types/
    └── index.d.ts
```

### Inertia.js Data Flow

```
┌─────────────────────────────────────────────────────────────┐
│                      CONTROLLER                             │
│  1. Validate request                                        │
│  2. Execute business logic (Service)                        │
│  3. Pass data to view                                       │
└───────────────────────────┬─────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                    INERTIA.JS                               │
│  Inertia::render('PageComponent', [                         │
│      'prop1' => $data1,                                     │
│      'prop2' => $data2,                                     │
│  ]);                                                        │
└───────────────────────────┬─────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                     VUE COMPONENT                           │
│  const props = defineProps<{                                │
│      prop1: Type1,                                          │
│      prop2: Type2,                                          │
│  }>();                                                      │
│                                                             │
│  // Use props in template                                   │
└─────────────────────────────────────────────────────────────┘
```

---

## Security Architecture

### Multi-Tenant Isolation Strategy

```
┌─────────────────────────────────────────────────────────────┐
│                    REQUEST FLOW                             │
├─────────────────────────────────────────────────────────────┤
│  1. User Login                                             │
│     └─► Session created with user_id                       │
│                                                             │
│  2. Authenticated Request                                  │
│     └─► Middleware: auth, verified                          │
│                                                             │
│  3. Controller Action                                       │
│     └─► Gate::authorize('view', $task)                      │
│         └─► TaskPolicy: $task->user_id === $user->id       │
│                                                             │
│  4. Database Query                                          │
│     └─► Task::forUser($user)->where(...)                    │
│         └─► WHERE user_id = $user->id                       │
│                                                             │
│  5. Response                                                │
│     └─► Only user's data returned                          │
└─────────────────────────────────────────────────────────────┘
```

### Authorization Layers

1. **Route Middleware**: `auth`, `verified`
2. **Controller Gates**: `Gate::authorize('view', $task)`
3. **Policy Methods**: `$task->user_id === $user->id`
4. **Query Scopes**: `Task::forUser($user)`
5. **Form Request Validation**: Category ownership check

---

## Testing Strategy

### Test Structure

```
tests/
├── Feature/
│   ├── Auth/
│   │   ├── AuthenticationTest.php
│   │   ├── RegistrationTest.php
│   │   ├── PasswordResetTest.php
│   │   └── ...
│   ├── TaskTest.php
│   ├── CategoryTest.php
│   ├── ProfileTest.php
│   └── ExampleTest.php
├── Unit/
│   └── ExampleTest.php
└── TestCase.php
```

### Test Categories

| Category | Coverage |
|----------|----------|
| Authentication | Login, Register, Password Reset, Email Verification |
| Task CRUD | Create, Read, Update, Delete, Soft Delete, Restore |
| Category CRUD | Create, Read, Update, Delete |
| Security | Multi-tenant isolation, BOLA/IDOR prevention |
| Validation | Form request validation rules |

---

## Deployment Architecture

### Docker Setup

```yaml
# docker-compose.yml
services:
  app:
    build: .
    ports:
      - "8080:80"
    volumes:
      - .:/var/www/html
    environment:
      - APP_ENV=local
      - DB_CONNECTION=sqlite
      - DB_DATABASE=/var/www/html/database/database.sqlite
```

### Production Considerations

- Use PostgreSQL instead of SQLite
- Enable OPcache for PHP
- Use Redis for caching and sessions
- Configure proper logging
- Set up CI/CD pipeline
- Enable HTTPS
- Configure backup strategy
