# Database Conventions

## Purpose
Standardized database design patterns, migration practices, and Eloquent conventions for the Training Management System.

## Table Naming Conventions

### Standard Rules

1. **Plural, lowercase, snake_case**
   - ✅ `students`, `training_schedules`, `schedule_details`
   - ❌ `Student`, `TrainingSchedule`, `scheduleDetail`

2. **Descriptive names**
   - ✅ `teaching_assignments` (clear purpose)
   - ❌ `ta` (too abbreviated)

3. **Pivot tables: alphabetical order**
   - ✅ `instructor_subject` (i before s)
   - ✅ `class_student`
   - ❌ `student_class` (should be alphabetical)

### Common Table Patterns

```
users                    # Core user accounts
students                 # Student records
instructors              # Instructor profiles
classes                  # Class information
subjects                 # Subjects/courses
specializations          # Academic programs
training_schedules       # Training schedules
schedule_details         # Schedule entries
teaching_assignments     # Instructor assignments
buildings                # Physical buildings
classrooms               # Classroom resources
units                    # Organizational units
```

## Column Naming Conventions

### Primary Keys

```php
$table->id();  // Creates unsigned BIGINT auto-increment 'id'
```

### Foreign Keys

**Naming Pattern:** `{related_model}_id`

```php
$table->foreignId('class_id')
    ->constrained('classes')  // References 'id' on 'classes' table
    ->onDelete('cascade');    // Delete children when parent deleted

$table->foreignId('user_id')
    ->constrained()           // Auto-detects 'users' table
    ->onDelete('restrict');   // Prevent deletion if children exist
```

**Options:**
- `cascade` - Delete children when parent deleted
- `restrict` - Prevent parent deletion if children exist
- `set null` - Set foreign key to NULL (requires nullable)
- `no action` - Do nothing (may cause integrity errors)

### Timestamps

```php
$table->timestamps();  // Creates 'created_at' and 'updated_at'
```

### Soft Deletes

```php
$table->softDeletes();  // Creates 'deleted_at'
```

### Common Columns

```php
// Text fields
$table->string('name', 255);           // VARCHAR(255)
$table->string('email')->unique();     // UNIQUE VARCHAR
$table->text('description')->nullable(); // TEXT, optional

// Numbers
$table->integer('age');                // INT
$table->decimal('price', 8, 2);        // DECIMAL(8,2) - 8 digits, 2 decimals
$table->boolean('is_active')->default(true);

// Dates
$table->date('birth_date');            // DATE
$table->dateTime('start_datetime');    // DATETIME
$table->timestamp('verified_at')->nullable();

// Enums (Laravel 12)
$table->enum('status', ['active', 'inactive', 'suspended']);

// JSON
$table->json('metadata')->nullable();
```

## Migration Best Practices

### Creating Migrations

```bash
# Create table migration
php artisan make:migration create_students_table

# Modify table migration
php artisan make:migration add_phone_to_students_table

# Multiple tables
php artisan make:migration create_students_and_classes_tables
```

### Migration Structure

**Create Table:**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_code', 20)->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();

            $table->foreignId('class_id')->nullable()
                ->constrained('classes')
                ->onDelete('set null');

            $table->foreignId('specialization_id')->nullable()
                ->constrained('specializations')
                ->onDelete('set null');

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('student_code');
            $table->index(['class_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
```

**Add Column:**
```php
public function up(): void
{
    Schema::table('students', function (Blueprint $table) {
        $table->string('phone', 20)->nullable()->after('email');
    });
}

public function down(): void
{
    Schema::table('students', function (Blueprint $table) {
        $table->dropColumn('phone');
    });
}
```

**Modify Column:**
```php
public function up(): void
{
    Schema::table('students', function (Blueprint $table) {
        $table->string('name', 100)->change();  // Change length
        $table->string('email')->nullable()->change();  // Make nullable
    });
}

public function down(): void
{
    Schema::table('students', function (Blueprint $table) {
        $table->string('name', 255)->change();
        $table->string('email')->nullable(false)->change();
    });
}
```

**IMPORTANT: When modifying columns, specify ALL attributes:**
```php
// ❌ WRONG - This will lose other attributes
$table->string('name')->nullable()->change();

// ✅ CORRECT - Specify everything
$table->string('name', 255)->nullable()->default('')->change();
```

### Migration Order

**Dependencies Matter:**

```bash
# Run in order:
2024_01_01_create_users_table.php
2024_01_02_create_classes_table.php
2024_01_03_create_students_table.php  # Depends on classes
2024_01_04_create_teaching_assignments_table.php  # Depends on multiple
```

**Use timestamps wisely:**
- Earlier dependencies have earlier timestamps
- Or run `migrate:fresh` to rebuild from scratch

## Eloquent Model Conventions

### Model Location

**Module Models:**
```php
// modules/Student/Models/Student.php
namespace Modules\Student\Models;
```

**Shared Models:**
```php
// app/Models/User.php
namespace App\Models;
```

### Basic Model Setup

```php
<?php

namespace Modules\Student\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    // Table name (optional if follows convention)
    protected $table = 'students';

    // Mass assignable fields
    protected $fillable = [
        'student_code',
        'name',
        'email',
        'birth_date',
        'gender',
        'class_id',
        'specialization_id',
        'is_active',
    ];

    // Hidden from JSON
    protected $hidden = [
        'deleted_at',
    ];

    // Cast attributes to types
    protected $casts = [
        'birth_date' => 'date',
        'is_active' => 'boolean',
        'metadata' => 'array',  // JSON column
    ];

    // Relationships
    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function specialization(): BelongsTo
    {
        return $this->belongsTo(Specialization::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    // Mutators
    public function setEmailAttribute($value): void
    {
        $this->attributes['email'] = strtolower($value);
    }
}
```

### Relationship Patterns

#### BelongsTo (Many-to-One)

```php
// Student belongs to a Class
public function class(): BelongsTo
{
    return $this->belongsTo(ClassModel::class, 'class_id');
}

// Usage
$student->class;  // Get related class
```

#### HasMany (One-to-Many)

```php
// Class has many Students
public function students(): HasMany
{
    return $this->hasMany(Student::class, 'class_id');
}

// Usage
$class->students;  // Collection of students
$class->students()->where('is_active', true)->get();
```

#### BelongsToMany (Many-to-Many)

```php
// Student has many Subjects through pivot table
public function subjects(): BelongsToMany
{
    return $this->belongsToMany(
        Subject::class,           // Related model
        'student_subject',        // Pivot table
        'student_id',             // Foreign key on pivot (this model)
        'subject_id'              // Related key on pivot
    )
    ->withPivot('grade', 'semester')  // Extra pivot columns
    ->withTimestamps();                // created_at, updated_at on pivot
}

// Usage
$student->subjects;
$student->subjects()->wherePivot('semester', '2024-1')->get();

// Attach/Detach
$student->subjects()->attach($subjectId, ['grade' => 'A', 'semester' => '2024-1']);
$student->subjects()->detach($subjectId);
$student->subjects()->sync([$subject1Id, $subject2Id]);
```

#### HasManyThrough

```php
// Specialization has many ScheduleDetails through TrainingSchedule
public function scheduleDetails(): HasManyThrough
{
    return $this->hasManyThrough(
        ScheduleDetail::class,      // Final model
        TrainingSchedule::class,    // Intermediate model
        'specialization_id',        // Foreign key on intermediate
        'training_schedule_id',     // Foreign key on final
        'id',                       // Local key
        'id'                        // Local key on intermediate
    );
}
```

#### Polymorphic Relationships

```php
// Comment can belong to Student OR Instructor
class Comment extends Model
{
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }
}

class Student extends Model
{
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}

// Usage
$comment->commentable;  // Could be Student or Instructor
$student->comments;     // All comments for this student
```

### Query Scopes

**Local Scopes:**
```php
// In Model
public function scopeActive($query)
{
    return $query->where('is_active', true);
}

public function scopeInClass($query, $classId)
{
    return $query->where('class_id', $classId);
}

// Usage
Student::active()->get();
Student::active()->inClass(5)->get();
```

**Global Scopes:**
```php
// In Model
protected static function booted()
{
    static::addGlobalScope('active', function ($query) {
        $query->where('is_active', true);
    });
}

// Usage
Student::all();  // Automatically filters active
Student::withoutGlobalScope('active')->get();  // Include inactive
```

### Accessors & Mutators (Laravel 12)

**Accessors (Get attributes):**
```php
use Illuminate\Database\Eloquent\Casts\Attribute;

protected function fullName(): Attribute
{
    return Attribute::make(
        get: fn () => "{$this->first_name} {$this->last_name}",
    );
}

// Usage
$student->full_name;  // Calls accessor
```

**Mutators (Set attributes):**
```php
protected function email(): Attribute
{
    return Attribute::make(
        get: fn ($value) => strtolower($value),
        set: fn ($value) => strtolower($value),
    );
}

// Usage
$student->email = 'TEST@EXAMPLE.COM';  // Stored as 'test@example.com'
```

## Indexing Strategy

### When to Add Indexes

**Always Index:**
- Primary keys (auto-indexed)
- Foreign keys
- Columns in WHERE clauses frequently
- Columns in ORDER BY frequently
- Unique constraints

**Example:**
```php
Schema::create('students', function (Blueprint $table) {
    $table->id();  // Auto-indexed

    $table->string('student_code')->unique();  // Unique index
    $table->string('email')->unique();         // Unique index

    $table->foreignId('class_id')
        ->constrained()
        ->onDelete('cascade');  // Auto-indexed

    // Regular indexes
    $table->index('name');  // Frequently searched
    $table->index(['class_id', 'is_active']);  // Composite index
});
```

### Index Types

```php
// Regular index
$table->index('column_name');
$table->index(['col1', 'col2']);  // Composite

// Unique index
$table->unique('email');
$table->unique(['user_id', 'subject_id']);

// Fulltext index (for text search)
$table->fulltext('description');

// Custom index name
$table->index('column_name', 'custom_index_name');
```

## Database Seeding

### Creating Seeders

```bash
php artisan make:seeder StudentSeeder
```

### Seeder Structure

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Student\Models\Student;
use Modules\Class\Models\ClassModel;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        // Get existing classes
        $classes = ClassModel::all();

        // Create specific records
        Student::create([
            'student_code' => 'ST001',
            'name' => 'Nguyen Van A',
            'email' => 'nguyenvana@example.com',
            'class_id' => $classes->random()->id,
            'is_active' => true,
        ]);

        // Use factories for bulk data
        Student::factory()
            ->count(50)
            ->create();

        // Create with relationships
        Student::factory()
            ->count(20)
            ->for(ClassModel::factory())  // Create class for each
            ->create();
    }
}
```

### DatabaseSeeder

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Order matters - dependencies first
        $this->call([
            UserSeeder::class,
            SpecializationSeeder::class,
            ClassSeeder::class,
            StudentSeeder::class,
            SubjectSeeder::class,
            InstructorSeeder::class,
            TeachingAssignmentSeeder::class,
        ]);
    }
}
```

## Factories

### Creating Factories

```bash
php artisan make:factory StudentFactory --model=Student
```

### Factory Structure

```php
<?php

namespace Database\Factories;

use Modules\Student\Models\Student;
use Modules\Class\Models\ClassModel;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'student_code' => 'ST' . $this->faker->unique()->numberBetween(1000, 9999),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'birth_date' => $this->faker->date('Y-m-d', '-18 years'),
            'gender' => $this->faker->randomElement(['male', 'female', 'other']),
            'class_id' => ClassModel::factory(),  // Create related class
            'is_active' => true,
        ];
    }

    // States
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function male(): static
    {
        return $this->state(fn (array $attributes) => [
            'gender' => 'male',
        ]);
    }
}

// Usage
Student::factory()->create();  // Single student
Student::factory()->count(10)->create();  // 10 students
Student::factory()->inactive()->create();  // Inactive student
Student::factory()->male()->count(5)->create();  // 5 male students
```

## Query Optimization

### N+1 Query Prevention

**Problem:**
```php
// 1 query for students + N queries for classes (1 per student)
$students = Student::all();
foreach ($students as $student) {
    echo $student->class->name;  // N queries!
}
```

**Solution:**
```php
// 2 queries total (1 for students, 1 for all classes)
$students = Student::with('class')->get();
foreach ($students as $student) {
    echo $student->class->name;  // No extra queries
}

// Multiple relationships
$students = Student::with(['class', 'specialization', 'subjects'])->get();

// Nested eager loading
$classes = ClassModel::with('students.specialization')->get();
```

### Lazy Eager Loading

```php
$students = Student::all();

// Later, load relationships
$students->load('class');
```

### Select Specific Columns

```php
// Only select needed columns
Student::select('id', 'name', 'email')->get();

// With relationships
Student::with('class:id,name')->select('id', 'name', 'class_id')->get();
```

### Chunking Large Datasets

```php
// Process in batches of 100
Student::chunk(100, function ($students) {
    foreach ($students as $student) {
        // Process student
    }
});

// Or use lazy collections (Laravel 12)
Student::lazy()->each(function ($student) {
    // Process student
});
```

## Transactions

### Basic Transaction

```php
use Illuminate\Support\Facades\DB;

DB::transaction(function () {
    $student = Student::create([...]);
    $student->subjects()->attach([1, 2, 3]);
    // If any fails, all roll back
});
```

### Manual Transaction Control

```php
DB::beginTransaction();

try {
    $student = Student::create([...]);
    $student->subjects()->attach([1, 2, 3]);

    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    throw $e;
}
```

## Common Patterns

### Soft Delete Handling

```php
// Query only non-deleted
Student::all();

// Include soft deleted
Student::withTrashed()->get();

// Only soft deleted
Student::onlyTrashed()->get();

// Restore soft deleted
$student->restore();

// Force delete (permanent)
$student->forceDelete();
```

### UUID Primary Keys

```php
// Migration
$table->uuid('id')->primary();

// Model
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Student extends Model
{
    use HasUuids;
}
```

### Composite Keys (Avoid if possible)

```php
// Models don't natively support composite keys
// Use packages or custom implementation
// Better: Add surrogate key (id) instead
```

## Testing Database

### RefreshDatabase

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class StudentTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_student()
    {
        $student = Student::factory()->create();

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
        ]);
    }
}
```

### Database Assertions

```php
$this->assertDatabaseHas('students', ['email' => 'test@example.com']);
$this->assertDatabaseMissing('students', ['email' => 'deleted@example.com']);
$this->assertDatabaseCount('students', 10);
$this->assertSoftDeleted('students', ['id' => 1]);
```

## References

- Laravel Migrations: https://laravel.com/docs/migrations
- Eloquent ORM: https://laravel.com/docs/eloquent
- Eloquent Relationships: https://laravel.com/docs/eloquent-relationships
- Database Seeding: https://laravel.com/docs/seeding
- Example migrations: `database/migrations/`
- Example models: `modules/*/Models/`
