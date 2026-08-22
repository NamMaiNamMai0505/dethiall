<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\TrainingDept;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Instructor\Models\Instructor;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TeachingAssignmentIndexRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_subject_scope_accepts_the_belongs_to_many_relation_used_by_eager_loading(): void
    {
        $user = User::factory()->create();
        $instructor = Instructor::factory()->create();

        $scopedRelation = TrainingDept::applySubjectFacultyScope($instructor->subjects(), $user);

        $this->assertInstanceOf(BelongsToMany::class, $scopedRelation);
        $this->assertCount(0, $scopedRelation->get());
    }

    public function test_teaching_assignment_index_renders_without_a_relation_type_error(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('teaching-assignments.index', 'web'));
        Instructor::factory()->create();

        $this->actingAs($user)
            ->get(route('teaching-assignments.index'))
            ->assertOk();
    }
}
