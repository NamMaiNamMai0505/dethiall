# Module Generator Skill

## Purpose
Assist with creating new modules following the custom modular architecture of this Training Management System.

## When to Use
- User requests to create a new feature/module
- User wants to add a new resource (e.g., "add course management", "create faculty module")
- User mentions scaffolding or generating a module

## Module Architecture

### Standard Module Structure
```
modules/{ModuleName}/
├── Controllers/
│   └── {ModuleName}Controller.php
├── Models/
│   └── {ModelName}.php
├── Requests/
│   ├── Create{ModuleName}Request.php
│   └── Update{ModuleName}Request.php
├── Providers/
│   └── {ModuleName}ServiceProvider.php
├── Routes/
│   ├── web.php
│   └── api.php (optional)
└── Views/
    ├── index.blade.php
    ├── create.blade.php
    ├── edit.blade.php
    └── show.blade.php
```

### Key Conventions

**Naming:**
- Module names: PascalCase (e.g., `Student`, `TrainingSchedule`, `InstructorSchedule`)
- Controller methods: CRUD standard (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`)
- Routes: plural kebab-case (e.g., `/students`, `/training-schedules`)

**Models:**
- Can be in `modules/{Module}/Models/` OR `app/Models/`
- Use explicit return types for relationships
- Example:
  ```php
  public function students(): HasMany
  {
      return $this->hasMany(Student::class);
  }
  ```

**Controllers:**
- Extend `App\Http\Controllers\Controller`
- Use Form Requests for validation
- Return views with module namespace: `return view('modulename::index')`

**Form Requests:**
- Always create separate requests for Create and Update
- Place in `modules/{Module}/Requests/`
- Use authorization() method to check permissions

**Service Provider:**
- Extend `Modules\ModuleServiceProvider`
- Auto-registers routes and views
- Must be registered in `bootstrap/providers.php`

## Workflow

### 1. Generate Module Structure
```bash
php artisan module:scaffold {ModuleName}
```

### 2. Create Migration
```bash
php artisan make:migration create_{table_name}_table
```

**Migration conventions:**
- Use `$table->id()` for primary key
- Add timestamps with `$table->timestamps()`
- Add soft deletes if needed: `$table->softDeletes()`
- Use proper foreign keys:
  ```php
  $table->foreignId('user_id')->constrained()->onDelete('cascade');
  ```

### 3. Define Model Relationships
```php
// In Model class
protected $fillable = ['field1', 'field2'];

public function relatedModel(): BelongsTo
{
    return $this->belongsTo(RelatedModel::class);
}
```

### 4. Add Permissions
Edit `app/Console/Commands/SyncPermissionsAndRoles.php`:
```php
$modules = [
    // ... existing modules
    'your-module' => [
        'view.your-module',
        'create.your-module',
        'edit.your-module',
        'delete.your-module',
    ],
];
```

Then run:
```bash
php artisan permissions:sync
```

### 5. Create Controller Logic

**Standard Controller Pattern:**
```php
public function index()
{
    $items = YourModel::with('relations')->paginate(15);
    return view('yourmodule::index', compact('items'));
}

public function store(CreateYourModuleRequest $request)
{
    YourModel::create($request->validated());
    return redirect()->route('your-module.index')
        ->with('success', 'Created successfully');
}
```

### 6. Create Views
- Use layout: `@extends('layouts.admin')`
- Follow existing view patterns from other modules
- Include CSRF token in forms: `@csrf`
- Use route names: `route('module.action')`

### 7. Define Routes
In `modules/{Module}/Routes/web.php`:
```php
Route::middleware(['auth', 'permission:view.your-module'])
    ->group(function () {
        Route::resource('your-module', YourModuleController::class);
    });
```

### 8. Create Factory & Seeder
```bash
php artisan make:factory YourModelFactory
php artisan make:seeder YourModuleSeeder
```

Add to `database/seeders/DatabaseSeeder.php`

### 9. Test the Module
```bash
# Reset database with new module
php artisan db:refresh-with-seeders

# Run tests
composer test
```

## Checklist

Before completing a module, verify:
- [ ] Migration created and run successfully
- [ ] Model with relationships defined
- [ ] Form requests for validation created
- [ ] Controller with CRUD methods
- [ ] Routes registered with proper middleware
- [ ] Views created (index, create, edit, show)
- [ ] Permissions added and synced
- [ ] Service provider registered in bootstrap/providers.php
- [ ] Seeder created and added to DatabaseSeeder
- [ ] Code formatted with Laravel Pint: `vendor/bin/pint --dirty`

## Common Patterns

### Eager Loading (Prevent N+1)
```php
$students = Student::with(['class', 'specialization'])->get();
```

### Scopes
```php
// In Model
public function scopeActive($query)
{
    return $query->where('status', 'active');
}

// Usage
Student::active()->get();
```

### Custom Validation Rules
```php
// In Form Request
public function rules(): array
{
    return [
        'email' => ['required', 'email', Rule::unique('students')->ignore($this->student)],
    ];
}
```

## References
- Main documentation: `/CLAUDE.md`
- Module base class: `modules/ModuleServiceProvider.php`
- Example module: `modules/Student/` or `modules/Class/`
- Permission system: `.claude/docs/rbac-guide.md`
