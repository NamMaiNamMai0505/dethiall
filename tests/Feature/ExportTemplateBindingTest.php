<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ExportTemplates\Enums\OutputFormat;
use Modules\ExportTemplates\Enums\TemplateBindingType;
use Modules\ExportTemplates\Enums\TemplateStatus;
use Modules\ExportTemplates\Exceptions\InvalidTemplateBindingException;
use Modules\ExportTemplates\Models\ExportTemplate;
use Modules\ExportTemplates\Models\ExportTemplateAuditLog;
use Modules\ExportTemplates\Models\ExportTemplateVersion;
use Modules\ExportTemplates\Services\TemplateActivationService;
use Modules\ExportTemplates\Services\TemplateBindingService;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ExportTemplateBindingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_and_updates_an_allowlisted_binding(): void
    {
        [, $version] = $this->makeTemplate();
        $service = app(TemplateBindingService::class);

        $binding = $service->bind($version, 'excel:sheet:LHL:cell:A1', 'class.name');
        $updated = $service->bind($version, 'excel:sheet:LHL:cell:A1', 'class.code');

        $this->assertTrue($binding->is($updated));
        $this->assertSame('class.code', $updated->data_key);
        $this->assertSame(TemplateBindingType::SCALAR, $updated->binding_type);
        $this->assertDatabaseCount('export_template_bindings', 1);
        $this->assertDatabaseCount('export_template_audit_logs', 2);
    }

    public function test_collection_binding_requires_a_table_target(): void
    {
        [, $version] = $this->makeTemplate();

        $this->expectException(InvalidTemplateBindingException::class);

        app(TemplateBindingService::class)->bind(
            $version,
            'excel:sheet:LHL:cell:A1',
            'schedule_groups'
        );
    }

    public function test_collection_can_bind_to_table_and_infers_table_type(): void
    {
        [, $version] = $this->makeTemplate();

        $binding = app(TemplateBindingService::class)->bind(
            $version,
            'excel:sheet:LHL:table:ScheduleRows',
            'schedule_groups'
        );

        $this->assertSame(TemplateBindingType::TABLE, $binding->binding_type);
    }

    public function test_unknown_target_and_sensitive_or_unknown_data_are_rejected(): void
    {
        [, $version] = $this->makeTemplate();
        $service = app(TemplateBindingService::class);

        foreach ([
            ['missing-target', 'class.name'],
            ['excel:sheet:LHL:cell:A1', 'user.password'],
            ['excel:sheet:LHL:cell:A1', 'class.unknown'],
        ] as [$target, $dataKey]) {
            try {
                $service->bind($version, $target, $dataKey);
                $this->fail("Binding {$target} → {$dataKey} phải bị từ chối.");
            } catch (InvalidTemplateBindingException) {
                $this->addToAssertionCount(1);
            }
        }

        $this->assertDatabaseCount('export_template_bindings', 0);
    }

    public function test_active_version_is_immutable_and_draft_binding_can_be_removed(): void
    {
        [, $draft] = $this->makeTemplate('lhl-draft');
        $service = app(TemplateBindingService::class);
        $binding = $service->bind($draft, 'excel:sheet:LHL:cell:A1', 'class.name');
        $service->remove($draft, $binding);

        $this->assertDatabaseCount('export_template_bindings', 0);
        $this->assertDatabaseHas('export_template_audit_logs', [
            'action' => ExportTemplateAuditLog::ACTION_BINDING_DELETED,
        ]);

        [, $active] = $this->makeTemplate('lhl-active');
        app(TemplateActivationService::class)->activate($active);

        $this->expectException(InvalidTemplateBindingException::class);
        $service->bind($active->fresh(), 'excel:sheet:LHL:cell:A1', 'class.name');
    }

    public function test_binding_editor_renders_data_explorer_and_saves_dropdown_selection(): void
    {
        [$template, $version] = $this->makeTemplate('lhl-editor');
        $user = User::factory()->create();
        Permission::findOrCreate('export-templates.index', 'web');
        Permission::findOrCreate('export-templates.edit', 'web');
        $user->givePermissionTo(['export-templates.index', 'export-templates.edit']);

        $url = route('export-templates.portal.versions.bindings.index', [
            'portal' => 'lms',
            'exportTemplate' => $template,
            'version' => $version,
        ]);

        $this->actingAs($user)
            ->get($url)
            ->assertOk()
            ->assertSee('Data Explorer')
            ->assertSee('class.name')
            ->assertSee('Placeholder Binding')
            ->assertSee('Preview trực quan')
            ->assertSee('Merge Cell');

        $this->actingAs($user)
            ->get(route('export-templates.portal.versions.preview', [
                'portal' => 'lms',
                'exportTemplate' => $template,
                'version' => $version,
            ]))
            ->assertOk()
            ->assertSee('Preview trực quan');

        $this->actingAs($user)
            ->post(route('export-templates.portal.versions.bindings.store', [
                'portal' => 'lms',
                'exportTemplate' => $template,
                'version' => $version,
            ]), [
                'target_ref' => 'excel:sheet:LHL:cell:A1',
                'data_key' => 'class.name',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('export_template_bindings', [
            'template_version_id' => $version->id,
            'target_ref' => 'excel:sheet:LHL:cell:A1',
            'data_key' => 'class.name',
        ]);
    }

    public function test_binding_persists_sanitized_presentation_and_cell_options(): void
    {
        [, $version] = $this->makeTemplate('lhl-style');

        $binding = app(TemplateBindingService::class)->bind(
            $version,
            'excel:sheet:LHL:cell:A1',
            'class.name',
            null,
            [
                'row_height' => 32,
                'column_width' => 24,
                'cell_action' => 'merge',
                'merge_range' => 'A1:B1',
            ],
            null,
            [
                'font_name' => 'Times New Roman',
                'font_size' => 14,
                'bold' => true,
                'italic' => false,
                'align' => 'center',
                'border_style' => 'thin',
                'border_color' => '#0F172A',
                'padding' => 4,
            ]
        );

        $this->assertSame('Times New Roman', $binding->style_overrides['font_name']);
        $this->assertSame(14, $binding->style_overrides['font_size']);
        $this->assertTrue($binding->style_overrides['bold']);
        $this->assertSame('A1:B1', $binding->options['merge_range']);
        $this->assertSame('merge', $binding->options['cell_action']);
    }

    /**
     * @return array{ExportTemplate, ExportTemplateVersion}
     */
    private function makeTemplate(string $code = 'lhl-binding'): array
    {
        $template = ExportTemplate::query()->create([
            'code' => $code,
            'name' => 'LHL 2026',
            'scope' => ExportTemplate::SCOPE_LMS,
            'module_key' => ExportTemplate::SCOPE_LMS,
            'feature_key' => 'lhl.training_plan',
            'output_format' => OutputFormat::EXCEL,
            'file_path' => "export-templates/lms/{$code}.xlsx",
            'disk' => 'local',
            'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'original_name' => "{$code}.xlsx",
            'status' => TemplateStatus::DRAFT,
            'is_active' => false,
        ]);
        $version = $template->versions()->create([
            'version_number' => 1,
            'disk' => 'local',
            'file_path' => $template->file_path,
            'original_name' => $template->original_name,
            'mime' => $template->mime,
            'file_extension' => 'xlsx',
            'status' => TemplateStatus::DRAFT,
            'manifest' => [
                'schema_version' => 1,
                'document' => [
                    'format' => 'excel',
                    'sheets' => [[
                        'name' => 'LHL',
                        'dimension' => 'A1:B2',
                        'merged_ranges' => ['A1:B1'],
                        'row_dimensions' => [['row' => 1, 'height' => 30]],
                        'column_dimensions' => [
                            ['column' => 'A', 'width' => 24],
                            ['column' => 'B', 'width' => 12],
                        ],
                        'page_setup' => ['orientation' => 'landscape'],
                    ]],
                ],
                'targets' => [
                    [
                        'ref' => 'excel:sheet:LHL:cell:A1',
                        'kind' => 'cell',
                        'sheet' => 'LHL',
                        'address' => 'A1',
                    ],
                    [
                        'ref' => 'excel:sheet:LHL:table:ScheduleRows',
                        'kind' => 'table',
                        'sheet' => 'LHL',
                        'name' => 'ScheduleRows',
                    ],
                ],
                'elements' => [
                    [
                        'ref' => 'excel:sheet:LHL:cell:A1',
                        'kind' => 'cell',
                        'sheet' => 'LHL',
                        'address' => 'A1',
                        'value' => '{{class.name}}',
                        'style' => [
                            'font' => ['name' => 'Arial', 'size' => 11, 'bold' => false, 'italic' => false],
                            'alignment' => ['horizontal' => 'left'],
                        ],
                    ],
                    [
                        'ref' => 'excel:sheet:LHL:cell:A2',
                        'kind' => 'cell',
                        'sheet' => 'LHL',
                        'address' => 'A2',
                        'value' => 'Lớp',
                        'style' => [],
                    ],
                ],
            ],
        ]);

        return [$template, $version];
    }
}
