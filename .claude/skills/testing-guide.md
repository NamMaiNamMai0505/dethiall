# Testing Guide Skill

## Purpose
Guide creation and execution of tests for the Training Management System using PHPUnit and Laravel testing features.

## When to Use
- User requests to create tests
- User wants to test a feature or module
- User asks about test coverage
- Debugging test failures
- Creating new modules (tests should be included)

## Test Structure

### Test Locations
```
tests/
├── Feature/          # Feature tests (most tests go here)
│   ├── Auth/
│   ├── Student/
│   ├── Class/
│   └── ...
├── Unit/            # Unit tests (for isolated logic)
└── TestCase.php     # Base test class
```

### Creating Tests

**Feature Test (default):**
```bash
php artisan make:test StudentModuleTest
# Creates: tests/Feature/StudentModuleTest.php
```

**Unit Test:**
```bash
php artisan make:test StudentModelTest --unit
# Creates: tests/Unit/StudentModelTest.php
```

**Organize by Module:**
```bash
php artisan make:test Student/StudentCrudTest
# Creates: tests/Feature/Student/StudentCrudTest.php
```

## Test Conventions

### Basic Test Structure

```php
<?php

namespace Tests\Feature\Student;

use Tests\TestCase;
use App\Models\User;
use Modules\Student\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StudentCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create authenticated user with permissions
        $this->user = User::factory()->create();
        $this->user->givePermissionTo(['view.student', 'create.student']);
    }

    /** @test */
    public function it_can_list_students(): void
    {
        $students = Student::factory()->count(5)->create();

        $response = $this->actingAs($this->user)
            ->get(route('students.index'));

        $response->assertOk();
        $response->assertViewHas('students');
    }

    /** @test */
    public function it_can_create_a_student(): void
    {
        $data = Student::factory()->make()->toArray();

        $response = $this->actingAs($this->user)
            ->post(route('students.store'), $data);

        $response->assertRedirect(route('students.index'));
        $this->assertDatabaseHas('students', ['email' => $data['email']]);
    }

    /** @test */
    public function it_requires_permission_to_view_students(): void
    {
        $user = User::factory()->create(); // No permissions

        $response = $this->actingAs($user)
            ->get(route('students.index'));

        $response->assertForbidden(); // 403
    }
}
```

### Key Testing Patterns

#### 1. Use RefreshDatabase
```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class MyTest extends TestCase
{
    use RefreshDatabase; // Migrates DB before each test
}
```

#### 2. Factories for Test Data
```php
// Use factories instead of manual creation
$student = Student::factory()->create();
$students = Student::factory()->count(10)->create();

// Create without saving (for form data)
$data = Student::factory()->make()->toArray();
```

**Check Existing Factory States Before Creating:**
```php
// Read the factory first
$factory = Student::factory();

// Use existing states if available
$activeStudent = Student::factory()->active()->create();
$inactiveStudent = Student::factory()->inactive()->create();
```

#### 3. Authentication in Tests
```php
// Acting as authenticated user
$this->actingAs($user)->get('/students');

// With specific permissions
$user = User::factory()->create();
$user->givePermissionTo('view.student');
$this->actingAs($user)->get('/students');

// With role
$admin = User::factory()->create();
$admin->assignRole('admin');
$this->actingAs($admin)->get('/students');
```

#### 4. Assertions

**HTTP Response:**
```php
$response->assertOk();                    // 200
$response->assertCreated();               // 201
$response->assertRedirect('/students');   // Redirect
$response->assertForbidden();             // 403
$response->assertUnauthorized();          // 401
$response->assertNotFound();              // 404
$response->assertSessionHas('success');   // Flash message
```

**Database:**
```php
$this->assertDatabaseHas('students', [
    'email' => 'test@example.com',
]);

$this->assertDatabaseMissing('students', [
    'email' => 'deleted@example.com',
]);

$this->assertDatabaseCount('students', 5);

$this->assertSoftDeleted('students', [
    'id' => $student->id,
]);
```

**View:**
```php
$response->assertViewIs('student::index');
$response->assertViewHas('students');
$response->assertViewHas('students', function ($students) {
    return $students->count() === 5;
});
```

**JSON:**
```php
$response->assertJson([
    'success' => true,
    'data' => [...]
]);

$response->assertJsonStructure([
    'data' => ['id', 'name', 'email'],
]);
```

## Testing CRUD Operations

### Standard CRUD Test Template

```php
class ModuleCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->user->givePermissionTo([
            'view.module',
            'create.module',
            'edit.module',
            'delete.module',
        ]);
    }

    /** @test */
    public function it_can_display_list(): void
    {
        Model::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->get(route('module.index'));

        $response->assertOk();
        $response->assertViewIs('module::index');
    }

    /** @test */
    public function it_can_display_create_form(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('module.create'));

        $response->assertOk();
        $response->assertViewIs('module::create');
    }

    /** @test */
    public function it_can_store_new_record(): void
    {
        $data = Model::factory()->make()->toArray();

        $response = $this->actingAs($this->user)
            ->post(route('module.store'), $data);

        $response->assertRedirect(route('module.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('table_name', [
            'field' => $data['field']
        ]);
    }

    /** @test */
    public function it_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('module.store'), []);

        $response->assertSessionHasErrors(['field1', 'field2']);
    }

    /** @test */
    public function it_can_display_edit_form(): void
    {
        $model = Model::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('module.edit', $model));

        $response->assertOk();
        $response->assertViewIs('module::edit');
        $response->assertViewHas('model', $model);
    }

    /** @test */
    public function it_can_update_record(): void
    {
        $model = Model::factory()->create();
        $newData = Model::factory()->make()->toArray();

        $response = $this->actingAs($this->user)
            ->put(route('module.update', $model), $newData);

        $response->assertRedirect(route('module.index'));
        $this->assertDatabaseHas('table_name', [
            'id' => $model->id,
            'field' => $newData['field'],
        ]);
    }

    /** @test */
    public function it_can_delete_record(): void
    {
        $model = Model::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('module.destroy', $model));

        $response->assertRedirect(route('module.index'));
        $this->assertDatabaseMissing('table_name', [
            'id' => $model->id,
        ]);
        // Or for soft deletes:
        // $this->assertSoftDeleted('table_name', ['id' => $model->id]);
    }
}
```

## Testing Permissions

```php
/** @test */
public function it_requires_view_permission(): void
{
    $user = User::factory()->create(); // No permissions

    $response = $this->actingAs($user)
        ->get(route('students.index'));

    $response->assertForbidden();
}

/** @test */
public function it_allows_user_with_permission(): void
{
    $user = User::factory()->create();
    $user->givePermissionTo('view.student');

    $response = $this->actingAs($user)
        ->get(route('students.index'));

    $response->assertOk();
}

/** @test */
public function super_admin_bypasses_permissions(): void
{
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super-admin');

    $response = $this->actingAs($superAdmin)
        ->get(route('students.index'));

    $response->assertOk();
}
```

## Testing Relationships

```php
/** @test */
public function student_belongs_to_class(): void
{
    $class = ClassModel::factory()->create();
    $student = Student::factory()->create(['class_id' => $class->id]);

    $this->assertInstanceOf(ClassModel::class, $student->class);
    $this->assertEquals($class->id, $student->class->id);
}

/** @test */
public function class_has_many_students(): void
{
    $class = ClassModel::factory()->create();
    $students = Student::factory()->count(3)->create(['class_id' => $class->id]);

    $this->assertEquals(3, $class->students->count());
    $this->assertInstanceOf(Student::class, $class->students->first());
}
```

## Testing Validation

```php
/** @test */
public function it_validates_email_format(): void
{
    $data = Student::factory()->make(['email' => 'invalid-email'])->toArray();

    $response = $this->actingAs($this->user)
        ->post(route('students.store'), $data);

    $response->assertSessionHasErrors('email');
}

/** @test */
public function it_validates_unique_email(): void
{
    $existing = Student::factory()->create(['email' => 'test@example.com']);
    $data = Student::factory()->make(['email' => 'test@example.com'])->toArray();

    $response = $this->actingAs($this->user)
        ->post(route('students.store'), $data);

    $response->assertSessionHasErrors('email');
}

/** @test */
public function it_allows_same_email_on_update(): void
{
    $student = Student::factory()->create(['email' => 'test@example.com']);
    $data = array_merge($student->toArray(), ['name' => 'Updated Name']);

    $response = $this->actingAs($this->user)
        ->put(route('students.update', $student), $data);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();
}
```

## Running Tests

### Run All Tests
```bash
composer test           # Clears config + runs tests
php artisan test        # Standard Laravel test command
vendor/bin/phpunit      # Direct PHPUnit
```

### Run Specific Tests
```bash
# Run specific file
php artisan test tests/Feature/StudentCrudTest.php

# Run specific method
php artisan test --filter it_can_create_a_student

# Run tests in directory
php artisan test tests/Feature/Student/
```

### Test Output Options
```bash
# Verbose output
php artisan test --verbose

# Stop on first failure
php artisan test --stop-on-failure

# Parallel testing (faster)
php artisan test --parallel

# Coverage report (requires Xdebug)
php artisan test --coverage
```

## Best Practices

### 1. Test Naming
✅ **Good:**
```php
/** @test */
public function it_can_create_a_student(): void
public function test_user_can_create_student(): void
```

❌ **Avoid:**
```php
public function testStudent(): void  // Too vague
```

### 2. One Assertion Per Test (when possible)
```php
/** @test */
public function it_creates_student_in_database(): void
{
    $data = Student::factory()->make()->toArray();

    $this->actingAs($this->user)
        ->post(route('students.store'), $data);

    $this->assertDatabaseHas('students', ['email' => $data['email']]);
}

/** @test */
public function it_redirects_after_creating_student(): void
{
    $data = Student::factory()->make()->toArray();

    $response = $this->actingAs($this->user)
        ->post(route('students.store'), $data);

    $response->assertRedirect(route('students.index'));
}
```

### 3. Use setUp for Common Setup
```php
protected function setUp(): void
{
    parent::setUp();
    $this->user = User::factory()->create();
    $this->user->givePermissionTo('view.student');
}
```

### 4. Test Edge Cases
```php
/** @test */
public function it_handles_invalid_student_id(): void
{
    $response = $this->actingAs($this->user)
        ->get(route('students.edit', 99999));

    $response->assertNotFound();
}

/** @test */
public function it_handles_empty_list(): void
{
    $response = $this->actingAs($this->user)
        ->get(route('students.index'));

    $response->assertOk();
    $response->assertViewHas('students', function ($students) {
        return $students->count() === 0;
    });
}
```

### 5. Fake External Services
```php
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

/** @test */
public function it_uploads_student_photo(): void
{
    Storage::fake('public');

    $file = UploadedFile::fake()->image('photo.jpg');

    $response = $this->actingAs($this->user)
        ->post(route('students.upload-photo'), [
            'photo' => $file,
        ]);

    Storage::disk('public')->assertExists('photos/' . $file->hashName());
}
```

## Testing Checklist

For each module, create tests for:
- [ ] List/index page loads
- [ ] Create form displays
- [ ] Store creates record in database
- [ ] Validation errors are shown
- [ ] Edit form displays with existing data
- [ ] Update modifies record
- [ ] Delete removes record
- [ ] Permissions are enforced (view, create, edit, delete)
- [ ] Relationships work correctly
- [ ] Edge cases handled (invalid IDs, empty data)

## Common Issues

### Factory Not Found
```bash
# Create factory if missing
php artisan make:factory StudentFactory --model=Student
```

### Permission Cache Issues
```php
// In setUp()
protected function setUp(): void
{
    parent::setUp();
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
}
```

### Database Not Refreshing
```php
// Ensure trait is used
use RefreshDatabase;

// Or manually in test
$this->artisan('migrate:fresh');
```

## References
- Laravel Testing Docs: https://laravel.com/docs/testing
- PHPUnit Assertions: https://phpunit.de/assertions.html
- Factory patterns: `database/factories/`
- Example tests: `tests/Feature/`
