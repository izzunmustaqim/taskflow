# Contributing to TaskFlow

Thank you for your interest in contributing to TaskFlow! This guide will help you get started.

## 🚀 Development Setup

### Prerequisites
- PHP 8.2+
- Node.js 18+
- Composer
- npm/yarn

### Local Development

```bash
# Clone the repository
git clone https://github.com/izzunmustaqim/taskflow.git
cd taskflow

# Install dependencies
composer install
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run migrations and seed
php artisan migrate --seed

# Start development servers
npm run dev
php artisan serve
```

### Docker Development

```bash
docker compose up -d
docker compose exec app php artisan migrate --seed
```

## 🔧 Development Workflow

### 1. Create a Feature Branch
```bash
git checkout -b feature/your-feature-name
```

### 2. Make Your Changes
- Write clean, readable code
- Follow PSR-12 coding standards
- Add tests for new features
- Update documentation if needed

### 3. Run Tests
```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage
```

### 4. Code Quality Checks
```bash
# Laravel Pint (code formatting)
./vendor/bin/pint

# PHPStan (static analysis)
./vendor/bin/phpstan analyse
```

### 5. Commit Your Changes
```bash
git add .
git commit -m "feat: add your feature description"
```

Use [Conventional Commits](https://www.conventionalcommits.org/) format:
- `feat:` - New feature
- `fix:` - Bug fix
- `docs:` - Documentation changes
- `style:` - Code style changes
- `refactor:` - Code refactoring
- `test:` - Adding tests
- `chore:` - Maintenance tasks

### 6. Push and Create PR
```bash
git push origin feature/your-feature-name
```

Then create a Pull Request on GitHub.

## 📁 Project Structure

```
taskflow/
├── app/
│   ├── Http/
│   │   ├── Controllers/    # Handle HTTP requests
│   │   ├── Middleware/      # Request/response middleware
│   │   └── Requests/       # Form request validation
│   ├── Models/             # Eloquent models
│   ├── Services/           # Business logic
│   └── Policies/           # Authorization policies
├── database/
│   ├── migrations/         # Database migrations
│   └── seeders/            # Database seeders
├── resources/
│   └── js/
│       ├── Components/     # Reusable Vue components
│       ├── Layouts/        # Page layouts
│       └── Pages/          # Inertia page components
├── routes/
│   └── web.php             # Web routes
└── tests/
    ├── Feature/            # Feature tests
    └── Unit/               # Unit tests
```

## 🎨 Frontend Guidelines

- Use Vue 3 Composition API
- Follow Tailwind CSS conventions
- Use Inertia.js for page transitions
- Keep components small and reusable

## 🧪 Testing Guidelines

- Write tests for all new features
- Maintain test coverage above 80%
- Use factories for test data
- Test both success and error scenarios

## 📝 Documentation

- Update README.md for setup changes
- Add inline comments for complex logic
- Update API documentation if adding endpoints

## 🐛 Reporting Issues

1. Check existing issues first
2. Use the issue template
3. Provide reproduction steps
4. Include environment details

## ❓ Questions?

Feel free to open a discussion or reach out to the maintainers.

---

Thank you for contributing! 🎉
