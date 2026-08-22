<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ExportTemplates\Enums\OutputFormat;
use Modules\ExportTemplates\Enums\TemplateStatus;
use Modules\ExportTemplates\Models\ExportTemplate;
use Modules\ExportTemplates\Services\BuilderTemplateService;
use Tests\TestCase;

class BuilderTemplateVersioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_builder_versioning_copies_schema_and_keeps_source_unchanged(): void
    {
        $user = User::factory()->create();
        $version = app(BuilderTemplateService::class)->create(
            'LHL Builder', ExportTemplate::SCOPE_LMS, 'lhl.training_plan', 'excel', $user->id
        );
        $original = $version->builderDocument->schema;
        $next = app(BuilderTemplateService::class)->createVersion($version, $user->id);

        $this->assertSame(1, $version->version_number);
        $this->assertSame(2, $next->version_number);
        $this->assertSame($original, $next->builderDocument->schema);
        $this->assertSame(TemplateStatus::DRAFT, $next->status);
        $this->assertNull($next->file_path);
    }
}
