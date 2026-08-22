<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ExportTemplates\Enums\OutputFormat;
use Modules\ExportTemplates\Enums\TemplateStatus;
use Modules\ExportTemplates\Models\ExportTemplate;
use Modules\ExportTemplates\Models\ExportTemplateVersion;
use Modules\ExportTemplates\Services\ActiveTemplateResolver;
use Modules\ExportTemplates\Services\TemplateActivationService;
use Tests\TestCase;

class ExportTemplateFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_one_version_is_active_per_feature_and_format(): void
    {
        [$firstTemplate, $firstVersion] = $this->makeTemplate('lhl-word-a');
        [$secondTemplate, $secondVersion] = $this->makeTemplate('lhl-word-b');

        $service = app(TemplateActivationService::class);
        $service->activate($firstVersion);
        $service->activate($secondVersion);

        $this->assertDatabaseCount('export_template_activations', 1);
        $this->assertDatabaseHas('export_template_activations', [
            'feature_key' => 'lhl.training_plan',
            'output_format' => OutputFormat::WORD->value,
            'template_version_id' => $secondVersion->id,
        ]);
        $this->assertFalse($firstTemplate->fresh()->is_active);
        $this->assertTrue($secondTemplate->fresh()->is_active);
        $this->assertDatabaseCount('export_template_audit_logs', 2);
    }

    public function test_resolver_returns_the_active_version_with_bindings(): void
    {
        [, $version] = $this->makeTemplate('lhl-word');
        $version->bindings()->create([
            'target_ref' => 'word:bookmark:class_name',
            'target_type' => 'bookmark',
            'data_key' => 'class.name',
            'binding_type' => 'scalar',
        ]);

        app(TemplateActivationService::class)->activate($version);

        $resolved = app(ActiveTemplateResolver::class)->resolve(
            'lhl.training_plan',
            OutputFormat::WORD
        );

        $this->assertTrue($resolved->is($version));
        $this->assertTrue($resolved->relationLoaded('bindings'));
        $this->assertSame('class.name', $resolved->bindings->first()->data_key);
    }

    public function test_word_and_excel_have_independent_active_slots(): void
    {
        [, $wordVersion] = $this->makeTemplate('lhl-word', OutputFormat::WORD);
        [, $excelVersion] = $this->makeTemplate('lhl-excel', OutputFormat::EXCEL);

        $service = app(TemplateActivationService::class);
        $service->activate($wordVersion);
        $service->activate($excelVersion);

        $this->assertDatabaseCount('export_template_activations', 2);
        $this->assertTrue(
            app(ActiveTemplateResolver::class)
                ->resolve('lhl.training_plan', OutputFormat::WORD)
                ->is($wordVersion)
        );
        $this->assertTrue(
            app(ActiveTemplateResolver::class)
                ->resolve('lhl.training_plan', OutputFormat::EXCEL)
                ->is($excelVersion)
        );
    }

    public function test_archived_version_cannot_be_activated(): void
    {
        [, $version] = $this->makeTemplate('lhl-archived');
        $version->update(['status' => TemplateStatus::ARCHIVED]);

        $this->expectException(\DomainException::class);

        app(TemplateActivationService::class)->activate($version->fresh());
    }

    /**
     * @return array{ExportTemplate, ExportTemplateVersion}
     */
    private function makeTemplate(
        string $code,
        OutputFormat $format = OutputFormat::WORD
    ): array {
        $extension = $format === OutputFormat::WORD ? 'docx' : 'xlsx';
        $template = ExportTemplate::query()->create([
            'code' => $code,
            'name' => 'LHL 2026',
            'scope' => ExportTemplate::SCOPE_LMS,
            'module_key' => ExportTemplate::SCOPE_LMS,
            'feature_key' => 'lhl.training_plan',
            'output_format' => $format,
            'file_path' => "export-templates/lms/{$code}.{$extension}",
            'disk' => 'local',
            'mime' => $format === OutputFormat::WORD
                ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'original_name' => "{$code}.{$extension}",
            'description' => 'Template test',
            'status' => TemplateStatus::DRAFT,
            'is_active' => false,
        ]);

        $version = $template->versions()->create([
            'version_number' => 1,
            'disk' => 'local',
            'file_path' => $template->file_path,
            'original_name' => $template->original_name,
            'mime' => $template->mime,
            'file_extension' => $extension,
            'status' => TemplateStatus::DRAFT,
        ]);

        return [$template, $version];
    }
}
