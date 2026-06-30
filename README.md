# TaskFlow 🚀

A premium, multi-tenant Personal Task Management Application built with **Laravel 11**, **Vue 3**, **Inertia.js**, and **Tailwind CSS**.

## ✨ Features

### Core Features
- **Strict Multi-Tenant Security**: Users can only see, edit, and delete their own categories and tasks. Built with explicit Laravel Policies to prevent BOLA/IDOR vulnerabilities.
- **Dynamic Categories**: Create personalized categories with a built-in hex color picker for visual organization.
- **Advanced Task Board**: Multi-parameter filtering (by status, priority, category, and due date) with debounced text search.
- **Soft Deletes & Trash System**: Deleted tasks are moved to a dedicated Trash view where they can be restored or permanently destroyed.
- **Task Activity Log**: Complete audit trail tracking all task changes (creation, updates, status changes, deletions, restorations).

### UI/UX Features
- **Premium UI/UX**: Designed with glassmorphism (backdrop-blur), micro-animations, and dynamic color-coded badges using Tailwind CSS.
- **Dark Mode**: Manual toggle with system preference detection and persistent storage.
- **Toast Notifications**: Non-intrusive success, error, warning, and info messages with auto-dismiss.
- **Keyboard Shortcuts**: Productivity shortcuts including `Ctrl+N` (new task), `/` (search), `?` (help), `Shift+B` (dark mode toggle).
- **Confirmation Modals**: Safety prompts for destructive actions with keyboard support (ESC to cancel).
- **Empty State Illustrations**: Beautiful, themed illustrations for empty lists and search results.
- **Responsive Design**: Fully responsive across desktop, tablet, and mobile devices.

### Testing
- **Robust Testing**: Comprehensive Pest PHP feature testing suite covering all CRUD operations and security assertions.

## 🛠 Tech Stack

- **Backend:** PHP 8.3, Laravel 11
- **Frontend:** Vue 3 (Composition API), Inertia.js, Tailwind CSS
- **Database:** SQLite (default) / PostgreSQL
- **Testing:** Pest PHP
- **Deployment:** Docker (PHP-FPM + Nginx on Alpine)

## ⌨️ Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| `?` | Toggle keyboard shortcuts help |
| `Ctrl + N` | Create new task |
| `/` | Focus search (on Tasks page) |
| `Escape` | Clear filters / Close modals |
| `Shift + B` | Toggle dark mode |
| `D` | Go to Dashboard |
| `T` | Go to Tasks |
| `C` | Go to Categories |

## 🚀 Getting Started

There are two easy ways to run this project locally: **Laravel Herd** (Easiest for Windows/Mac) or **Docker**.

### Option 1: Using Laravel Herd (Recommended)
This approach requires zero database or server configuration.

1. Install [Laravel Herd](https://herd.laravel.com/).
2. Clone this repository and navigate into it:
   ```bash
   git clone https://github.com/izzunmustaqim/taskflow.git
   cd taskflow
   ```
3. Install PHP and Node dependencies:
   ```bash
   composer install
   npm install
   ```
4. Copy the `.env.example` to `.env` and generate an app key:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
5. Run the database migrations (creates the SQLite database automatically):
   ```bash
   php artisan migrate
   ```
6. Build the frontend assets and start the local server:
   ```bash
   npm run dev
   php artisan serve
   ```
7. Visit `http://localhost:8000` in your browser.

### Option 2: Using Docker
We provide a production-ready `docker-compose.yml` and `Dockerfile`.

1. Clone the repository and navigate into it.
2. Build and start the Docker container:
   ```bash
   docker-compose up -d --build
   ```
3. Generate the application key inside the container:
   ```bash
   docker-compose exec app php artisan key:generate
   ```
4. Run the database migrations:
   ```bash
   docker-compose exec app php artisan migrate
   ```
5. Visit `http://localhost:8080` in your browser.

## 🧪 Testing

This application uses [Pest](https://pestphp.com/) for elegant feature testing. To run the test suite:

```bash
php artisan test
```

## 🔒 Security

If you discover any security related issues, please open an issue or submit a pull request. The application relies heavily on Laravel's Gate authorization (`app/Policies`) to strictly scope Eloquent models to the authenticated `$user->id`.

## 📜 License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT). This application's custom code is also provided under the MIT license.
