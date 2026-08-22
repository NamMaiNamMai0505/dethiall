# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Application Overview

This is a **Training Management System (Qu�n l� �o T�o)** built with Laravel 12 for managing students, instructors, classes, schedules, and training programs. The application uses a modular architecture with role-based access control (RBAC) via Spatie Laravel Permission.

## Quick Start Commands

### Development

```bash
# Start development environment (recommended - runs all services concurrently)
composer run dev

# Or start services individually
php artisan serve              # Start Laravel dev server at http://localhost:8000
npm run dev                    # Start Vite with hot reload

# Development utilities
php artisan pail               # Tail application logs
php artisan queue:listen       # Process queue jobs
```

### Database Management

```bash
# Reset database with seeders (recommended for fresh start)
php artisan db:refresh-with-seeders
npm run fresh                  # Reset DB + build assets

# View database statistics
php artisan db:stats
npm run stats

# Standard Laravel commands
php artisan migrate
php artisan db:seed
php artisan migrate:status
```

### Permissions & Roles

```bash
# Sync all permissions and roles (creates/updates based on module definitions)
php artisan permissions:sync

# Reset and rebuild all permissions/roles from scratch
php artisan permissions:sync --reset
npm run sync-permission
```

### Testing & Code Quality

```bash
# Run tests
composer test                  # Clears config and runs PHPUnit
php artisan test               # Run all tests

# Code formatting (MUST run before finalizing changes)
vendor/bin/pint --dirty        # Format modified files only
vendor/bin/pint                # Format all files
```

### Building Assets

```bash
npm run build                  # Production build
npm run dev                    # Development with hot reload
```

### Security & Production

```bash
# Security testing
composer require --dev enlightn/security-checker
php artisan security:check

# Production deployment (see SECURITY.md for full checklist)
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**IMPORTANT:** See [SECURITY.md](SECURITY.md) for:
- Cookie security & HTTP headers configuration
- Production deployment checklist
- Secrets management best practices
- Security testing procedures

## Architecture

### Modular Structure

The application uses a **custom module-based architecture** where each feature is encapsulated in its own module under `modules/`. This is NOT nanobox/laravel-modules - it's a custom implementation.

**Module Structure:**
```
modules/{ModuleName}/
├── Controllers/          # HTTP controllers
├── Models/              # Eloquent models (optional, may not exist)
├── Requests/            # Form request validation
├── Providers/           # Service provider (registers routes, views)
├── Routes/
│   ├── web.php         # Web routes
│   ├── api.php         # API routes (optional)
├── Views/              # Blade templates
```

**Available Modules:**
- Authentication
- Building
- Class
- Classroom
- Dashboard
- Home
- Instructor
- InstructorSchedule
- ScheduleDetail
- Specialization
- Student
- StudentSchedule
- Subject
- TeachingAssignment
- TrainingSchedule
- Unit
- User

**Module Registration:**
- All module service providers are registered in `bootstrap/providers.php`
- Each module's provider extends `Modules\ModuleServiceProvider` abstract class
- Routes, views, and configs are loaded automatically by the provider

### Creating New Modules

```bash
php artisan module:scaffold {ModuleName}
```

This command creates the complete module structure with:
- Controllers, Models, Requests, Routes, Views, Providers
- Automatically registers the ServiceProvider in `bootstrap/providers.php`

**After creating a module:**
1. Add it to `bootstrap/providers.php` (if not auto-added)
2. Add permissions to `app/Console/Commands/SyncPermissionsAndRoles.php`
3. Run `php artisan permissions:sync` to create permissions

### Models Location

Models can be in two places:
1. `modules/{ModuleName}/Models/{ModelName}.php` - Module-specific models
2. `app/Models/{ModelName}.php` - Shared/core models (like User)

All models are autoloaded via the `Modules\` namespace in `composer.json`.

### Role-Based Access Control (RBAC)

**System Roles (3 roles only):**
1. `super-admin` - Full system access (admin@example.com only)
2. `student` - Can view student-schedule and dashboards
3. `instructor` - Can view instructor-schedule and dashboards

**Permission Naming Convention:**
- Format: `{module}.{action}` (e.g., `users.index`, `classes.create`, `student-schedule.show`)
- **Standard Actions**: `index`, `show`, `create`, `edit`, `delete`
- **Example**: `users.index`, `classes.create`, `instructor-schedule.show`

**IMPORTANT:** The system uses `module.action` format, NOT `action.module`!

**Permission Assignment:**
- `super-admin`: ALL 70 permissions
- `student`: Only `student-schedule.index`, `student-schedule.show`, `dashboards.index`
- `instructor`: Only `instructor-schedule.index`, `instructor-schedule.show`, `dashboards.index`

**Custom Middleware:**
- `permission` - Single permission check
- `permission.multiple` - Multiple permissions (any)
- `role` - Role-based access

**Usage in routes:**
```php
// Correct format: module.action
Route::middleware(['auth', 'permission:users.index'])->group(function() {
    // Routes requiring users.index permission
});

Route::middleware(['auth', 'permission:student-schedule.index'])->group(function() {
    // Routes for student schedule
});
```

**Single Source of Truth:**
All permissions and roles are managed in `app/Console/Commands/SyncPermissionsAndRoles.php`. This is the ONLY place to define permissions and role assignments.

### Laravel 12 Specifics

This project follows Laravel 12's streamlined structure:
- **No `app/Http/Kernel.php`** - middleware registered in `bootstrap/app.php`
- **No `app/Console/Kernel.php`** - schedule defined in `bootstrap/app.php`
- Commands auto-register from `app/Console/Commands/`
- Service providers registered in `bootstrap/providers.php`

### Scheduled Tasks

Automated backup schedule (defined in `bootstrap/app.php`):
- Daily DB backup to S3 at 2 AM
- Weekly full backup (DB + files) on Sundays at 3 AM
- Daily backup cleanup at 4 AM
- Daily backup health monitoring at 5 AM

### File Storage

**Configured Disks:**
- `public` - Local public storage (`storage/app/public`)
- `s3` - Viettel Cloud S3 (via `kaibatech/viettel-cloud-s3` package)
  - Requires: `VIETTEL_S3_KEY`, `VIETTEL_S3_SECRET`, `VIETTEL_S3_BUCKET`, `VIETTEL_S3_ENDPOINT`

## Default Credentials

**Super Admin (created by permissions:sync command):**
- Email: `admin@example.com`
- Password: `password`
- Role: `super-admin` (only user with this role)

**Sample Users (from seeders):**
- `nguyenvana@example.com` / `it@Cdhc2`
- `tranthib@example.com` / `it@Cdhc2`
- `levanc@example.com` / `it@Cdhc2`
- These users need to be manually assigned `student` or `instructor` role

**Note:** The `permissions:sync` command automatically creates and assigns the super-admin role to `admin@example.com`.

## Important Conventions

### Views

**Layouts:**
- `resources/views/layouts/admin.blade.php` - Admin panel layout
- `resources/views/layouts/app.blade.php` - Public/general layout

**Module Views:**
- Accessed via namespace: `{modulename}::viewname`
- Example: `return view('student::index');`

### Form Validation

Always create Form Request classes in `modules/{Module}/Requests/`:
- `Create{Module}Request.php`
- `Update{Module}Request.php`

### Database Conventions

- Use Eloquent relationships with explicit return types
- Prefer eager loading to avoid N+1 queries
- When modifying columns, include ALL previous attributes or they'll be lost

### Frontend

**Stack:**
- Vite for asset bundling
- Tailwind CSS v4 (uses `@import "tailwindcss"` NOT `@tailwind` directives)
- Alpine.js (if present in modules)

**If frontend changes don't appear:**
Run `npm run build` or ask user to run `npm run dev`

## Testing

Tests are located in `tests/` directory using PHPUnit.

**Key Points:**
- Use factories for model creation in tests
- Check for existing factory states before manual setup
- Use `fake()` or `$this->faker` for fake data (follow existing conventions)
- Most tests should be feature tests (`php artisan make:test {Name}`)
- For unit tests, add `--unit` flag

## Common Workflows

### Adding a New Feature Module

1. Create module: `php artisan module:scaffold FeatureName`
2. Define model and migrations
3. Create controllers and routes
4. Add permissions to `SyncPermissionsAndRoles.php`
5. Run `php artisan permissions:sync`
6. Add seeder to `DatabaseSeeder.php`
7. Test with `php artisan db:refresh-with-seeders`

### Modifying Permissions

**IMPORTANT: Single Source of Truth**
All permission management is centralized in `app/Console/Commands/SyncPermissionsAndRoles.php`. Do NOT:
- ❌ Create separate seeders for permissions
- ❌ Manually modify database tables
- ❌ Use PermissionService (deprecated and removed)

**To add/modify permissions:**

1. Edit `app/Console/Commands/SyncPermissionsAndRoles.php`:
   ```php
   private function createPermissions()
   {
       $modules = [
           // Add your module here with actions
           'your-module' => ['index', 'show', 'create', 'edit', 'delete'],
       ];
   }
   ```

2. Update role assignments in `assignPermissionsToRoles()`:
   ```php
   private function assignPermissionsToRoles()
   {
       // Assign permissions to specific roles
       $student->syncPermissions([
           'student-schedule.index',
           'student-schedule.show',
           'dashboards.index',
       ]);
   }
   ```

3. Run sync command:
   ```bash
   php artisan permissions:sync          # Update (keeps existing)
   php artisan permissions:sync --reset  # Clean rebuild
   ```

**Convention Rules:**
- Format: `{module}.{action}` (e.g., `users.index`, NOT `index.users`)
- Module names: lowercase with hyphens (e.g., `student-schedule`, NOT `Student schedule`)
- Standard actions: `index`, `show`, `create`, `edit`, `delete`

### Database Changes

1. Create migration: `php artisan make:migration description`
2. Run migration: `php artisan migrate`
3. Update seeders if needed
4. Test with: `php artisan db:refresh-with-seeders`

## Key Files

- `bootstrap/app.php` - Application configuration, middleware, scheduling
- `bootstrap/providers.php` - Service provider registration
- `modules/ModuleServiceProvider.php` - Base class for all module providers
- `app/Console/Commands/SyncPermissionsAndRoles.php` - RBAC configuration
- `app/Console/Commands/MakeModule.php` - Module scaffolding logic
- `config/permission.php` - Spatie permission package configuration
- `config/backup.php` - Backup configuration for Spatie Laravel Backup

## Environment Variables

**Required:**
- Standard Laravel variables (APP_*, DB_*)
- `VIETTEL_S3_KEY`, `VIETTEL_S3_SECRET`, `VIETTEL_S3_BUCKET`, `VIETTEL_S3_ENDPOINT` - For S3 backups
- `BACKUP_NOTIFICATIONS_MAIL` - Email for backup failure notifications

## Package-Specific Notes

**Spatie Laravel Permission:**
- Cache expiration: 24 hours
- Permissions/roles auto-sync on updates
- Use `permissions:sync` to rebuild from code definitions

**Spatie Laravel Backup:**
- Configured to backup to both local and S3
- Excludes: vendor, node_modules, storage/framework, storage/logs
- Monitor with: `php artisan backup:monitor`

**Viettel Cloud S3:**
- Custom S3-compatible driver for Viettel Cloud storage
- Service provider: `Kaibatech\ViettelCloudS3\ViettelCloudS3ServiceProvider`

## Skills & Extended Documentation

For detailed guidance on specific tasks, refer to the specialized skills and documentation in `.claude/`:

### Skills (Task-Specific Guides)

- **`.claude/skills/module-generator.md`** - Complete workflow for creating new modules with CRUD operations
- **`.claude/skills/permission-manager.md`** - Managing RBAC permissions, roles, and access control
- **`.claude/skills/testing-guide.md`** - Creating and running PHPUnit tests with best practices
- **`.claude/skills/laravel-12-expert.md`** - Laravel 12 specific patterns, breaking changes, and new features

### Documentation (Reference Guides)

- **`.claude/docs/architecture.md`** - System architecture, design patterns, and data flow
- **`.claude/docs/database-conventions.md`** - Database design, migrations, Eloquent relationships, and query optimization
- **`.claude/docs/frontend-guide.md`** - Tailwind CSS v4, Alpine.js, Blade components, and Vite integration

### Quick Reference

**See `.claude/README.md`** for:
- Complete skills and documentation index
- Common workflows and decision matrices
- Troubleshooting quick links
- Code style standards
- Learning path for new contributors

**When to use:**
- Creating a new module → Reference `module-generator.md`
- Adding/modifying permissions → Reference `permission-manager.md`
- Writing tests → Reference `testing-guide.md`
- Working with Laravel 12 specifics → Reference `laravel-12-expert.md`
- Understanding system design → Reference `architecture.md`
- Database work → Reference `database-conventions.md`
- Frontend development → Reference `frontend-guide.md`

## Troubleshooting

**Vite manifest error:**
Run `npm run build` or ask user to run `npm run dev`

**Permission denied errors:**
Run `php artisan permissions:sync` to rebuild permissions cache

**Routes not found:**
Check that module's ServiceProvider is registered in `bootstrap/providers.php`
