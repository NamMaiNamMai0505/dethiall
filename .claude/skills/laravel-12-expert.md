# Laravel 12 Expert Skill

## Purpose
Provide expertise on Laravel 12 specific features, breaking changes, and new patterns that differ from previous Laravel versions.

## When to Use
- User mentions Laravel 12 specifically
- Working with middleware, scheduling, or service providers
- Dealing with configuration or bootstrapping
- User encounters Laravel 12 specific errors
- Modernizing code to Laravel 12 standards

## Major Changes from Laravel 11

### 1. No More Kernel Files

**Laravel 11 and Earlier:**
```
app/
├── Http/
│   └── Kernel.php          # ❌ REMOVED in Laravel 12
└── Console/
    └── Kernel.php          # ❌ REMOVED in Laravel 12
```

**Laravel 12:**
All middleware and scheduling configuration moved to `bootstrap/app.php`:

```php
// bootstrap/app.php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register middleware here
        $middleware->alias([
            'permission' => \App\Http\Middleware\PermissionMiddleware::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);

        $middleware->group('web', [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // ... more middleware
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        // Define scheduled tasks here
        $schedule->command('backup:run')->daily()->at('02:00');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Exception handling
    })
    ->create();
```

### 2. Middleware Registration

**Old Way (Laravel 11):**
```php
// app/Http/Kernel.php
protected $middlewareAliases = [
    'auth' => \App\Http\Middleware\Authenticate::class,
];
```

**Laravel 12 Way:**
```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'auth' => \App\Http\Middleware\Authenticate::class,
        'permission' => \App\Http\Middleware\PermissionMiddleware::class,
    ]);
})
```

**Custom Middleware in Project:**
```php
// bootstrap/app.php
$middleware->alias([
    'permission' => \App\Http\Middleware\PermissionMiddleware::class,
    'permission.multiple' => \App\Http\Middleware\MultiplePermissionsMiddleware::class,
    'role' => \App\Http\Middleware\RoleMiddleware::class,
]);
```

### 3. Scheduled Tasks

**Old Way (Laravel 11):**
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('backup:run')->daily();
}
```

**Laravel 12 Way:**
```php
// bootstrap/app.php
->withSchedule(function (Schedule $schedule) {
    // Daily database backup to S3
    $schedule->command('backup:run --only-db')
        ->daily()
        ->at('02:00')
        ->onFailure(function () {
            Log::error('Database backup failed');
        });

    // Weekly full backup
    $schedule->command('backup:run')
        ->weekly()
        ->sundays()
        ->at('03:00');

    // Cleanup old backups
    $schedule->command('backup:clean')
        ->daily()
        ->at('04:00');
})
```

### 4. Service Provider Registration

**Laravel 12:**
Service providers registered in `bootstrap/providers.php`:

```php
<?php

return [
    App\Providers\AppServiceProvider::class,
    Modules\Authentication\Providers\AuthenticationServiceProvider::class,
    Modules\Student\Providers\StudentServiceProvider::class,
    // ... all module providers
];
```

**Auto-Discovery:**
- Commands auto-register from `app/Console/Commands/`
- No need to manually register commands in Kernel

### 5. Route Registration

**Streamlined Routing:**
```php
// bootstrap/app.php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',  // Health check endpoint
)
```

**Module Routes:**
Module service providers register their own routes:
```php
// modules/Student/Providers/StudentServiceProvider.php
public function boot(): void
{
    $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
    $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
}
```

## Project-Specific Patterns

### Module Service Providers

**Base Class Pattern:**
```php
// modules/ModuleServiceProvider.php
abstract class ModuleServiceProvider extends ServiceProvider
{
    protected string $moduleName;
    protected string $moduleNameLower;

    public function boot(): void
    {
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
    }

    protected function registerViews(): void
    {
        $viewPath = module_path($this->moduleName, 'Views');
        $this->loadViewsFrom($viewPath, $this->moduleNameLower);
    }
}
```

**Concrete Provider:**
```php
// modules/Student/Providers/StudentServiceProvider.php
class StudentServiceProvider extends ModuleServiceProvider
{
    protected string $moduleName = 'Student';
    protected string $moduleNameLower = 'student';

    public function boot(): void
    {
        parent::boot();
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
    }
}
```

### Bootstrap Configuration

**Current Project Structure:**
```
bootstrap/
├── app.php           # Main application configuration
├── providers.php     # Service provider registration
└── cache/           # Cached config and routes
```

**Key Configuration in app.php:**
- Middleware aliases and groups
- Schedule definitions
- Exception handling
- Route configuration

## Common Patterns in Laravel 12

### 1. Middleware Usage

**Applying Middleware in Routes:**
```php
Route::middleware(['auth', 'permission:view.student'])->group(function () {
    Route::get('/students', [StudentController::class, 'index']);
});
```

**Multiple Middleware:**
```php
Route::middleware(['auth', 'verified', 'permission:admin'])
    ->group(function () {
        // Admin routes
    });
```

### 2. Command Auto-Registration

**Old Way:**
```php
// app/Console/Kernel.php
protected $commands = [
    \App\Console\Commands\MyCommand::class,
];
```

**Laravel 12:**
Just create command in `app/Console/Commands/` - it auto-registers!

```bash
php artisan make:command MyCommand
# Command is immediately available, no registration needed
```

### 3. Exception Handling

**Custom Exception Handler:**
```php
// bootstrap/app.php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->report(function (Throwable $e) {
        // Custom reporting logic
    });

    $exceptions->render(function (NotFoundHttpException $e, Request $request) {
        if ($request->is('api/*')) {
            return response()->json(['error' => 'Not found'], 404);
        }
    });
})
```

### 4. Environment-Based Configuration

**Config Values:**
```php
// Use config() helper, not env() directly in code
$s3Bucket = config('filesystems.disks.s3.bucket');  // ✅ Correct
$s3Bucket = env('VIETTEL_S3_BUCKET');                // ❌ Avoid
```

**Why?**
- Config is cached in production
- `env()` returns `null` when config is cached
- Always use `env()` only in config files

### 5. Health Checks

**Built-in Health Route:**
```php
// bootstrap/app.php
->withRouting(
    health: '/up',  // GET /up returns 200 if app is healthy
)
```

**Custom Health Checks:**
```php
Route::get('/health', function () {
    return response()->json([
        'database' => DB::connection()->getPdo() ? 'ok' : 'error',
        'cache' => Cache::store()->getStore() ? 'ok' : 'error',
    ]);
});
```

## Migration Checklist (if upgrading from Laravel 11)

- [ ] Move middleware aliases from `Kernel.php` to `bootstrap/app.php`
- [ ] Move schedule from `Kernel.php` to `bootstrap/app.php`
- [ ] Delete `app/Http/Kernel.php`
- [ ] Delete `app/Console/Kernel.php`
- [ ] Update service provider registration to `bootstrap/providers.php`
- [ ] Update exception handling to `bootstrap/app.php`
- [ ] Test all scheduled tasks
- [ ] Test all middleware
- [ ] Clear config cache: `php artisan config:clear`
- [ ] Clear route cache: `php artisan route:clear`

## Common Issues

### 1. Middleware Not Working

**Problem:** Custom middleware not being applied

**Solution:**
```php
// Check bootstrap/app.php has middleware registered
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'your-middleware' => \App\Http\Middleware\YourMiddleware::class,
    ]);
})
```

### 2. Schedule Not Running

**Problem:** Scheduled tasks not executing

**Check:**
1. Cron is configured: `* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1`
2. Schedule defined in `bootstrap/app.php` (not Kernel.php)
3. Run manually: `php artisan schedule:run`
4. Check logs: `php artisan schedule:list`

### 3. Config Cached

**Problem:** Environment changes not reflecting

**Solution:**
```bash
php artisan config:clear
php artisan config:cache  # In production
```

### 4. Route Not Found

**Problem:** Module routes not loading

**Check:**
1. Service provider registered in `bootstrap/providers.php`
2. Routes loaded in provider's `boot()` method
3. Clear route cache: `php artisan route:clear`

## Best Practices

### 1. Keep bootstrap/app.php Clean

**Good:**
```php
->withSchedule(function (Schedule $schedule) {
    $schedule->command('backup:run')->daily()->at('02:00');
    $schedule->command('backup:clean')->daily()->at('04:00');
})
```

**Better (for complex schedules):**
```php
->withSchedule(function (Schedule $schedule) {
    (new App\Console\Scheduling\BackupSchedule())->schedule($schedule);
})
```

### 2. Organize Middleware

```php
->withMiddleware(function (Middleware $middleware) {
    // Core middleware aliases
    $middleware->alias([
        'auth' => \App\Http\Middleware\Authenticate::class,
    ]);

    // Permission middleware (grouped logically)
    $middleware->alias([
        'permission' => \App\Http\Middleware\PermissionMiddleware::class,
        'permission.multiple' => \App\Http\Middleware\MultiplePermissionsMiddleware::class,
        'role' => \App\Http\Middleware\RoleMiddleware::class,
    ]);
})
```

### 3. Use Type Hints

```php
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

->withMiddleware(function (Middleware $middleware) {
    // IDE autocomplete works!
})
->withSchedule(function (Schedule $schedule) {
    // Type-safe!
})
```

## Performance Tips

### 1. Cache Configuration in Production

```bash
php artisan config:cache    # Cache all config files
php artisan route:cache     # Cache routes
php artisan view:cache      # Cache views
php artisan event:cache     # Cache events
```

### 2. Optimize Autoloader

```bash
composer install --optimize-autoloader --no-dev
php artisan optimize
```

### 3. Use OPcache

```ini
; php.ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0  # In production
```

## Debugging

### View Registered Middleware
```bash
php artisan route:list --columns=uri,middleware
```

### View Scheduled Tasks
```bash
php artisan schedule:list
php artisan schedule:run --verbose  # Run with output
```

### Clear All Caches
```bash
php artisan optimize:clear  # Clears config, route, view, event caches
```

### Test Schedule
```bash
php artisan schedule:test   # Interactive testing
php artisan schedule:work   # Run scheduler in foreground
```

## References

- Laravel 12 Release Notes: https://laravel.com/docs/releases
- Laravel 12 Upgrade Guide: https://laravel.com/docs/upgrade
- Project bootstrap config: `bootstrap/app.php`
- Project providers: `bootstrap/providers.php`
- Module service provider base: `modules/ModuleServiceProvider.php`
