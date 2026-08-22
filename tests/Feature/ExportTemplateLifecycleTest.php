<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\ExportTemplates\Enums\OutputFormat;
use Modules\ExportTemplates\Enums\TemplateStatus;
use Modules\ExportTemplates\Exceptions\InvalidTemplateException;
use Modules\ExportTemplates\Models\ExportTemplate;
use Modules\ExportTemplates\Models\ExportTemplateAuditLog;
use Modules\ExportTemplates\Services\TemplateLifecycleService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ExportTemplateLifecycleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var list<string>
     */
    private array $temporaryFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        parent::tearDown();
    }

    public function test_upload_creates_draft_template_and_immutable_version_one(): void
    {
        $template = $this->service()->createTemplate(
            [
                'name' => 'LHL 2026 Excel',
                'feature_key' => 'lhl.training_plan',
                'notes' => 'Mẫu chuẩn',
            ],
            $this->spreadsheetUpload('lhl-2026.xlsx'),
            ExportTemplate::SCOPE_LMS
        );

        $version = $template->versions()->firstOrFail();

        $this->assertSame(TemplateStatus::DRAFT, $template->status);
        $this->assertSame(OutputFormat::EXCEL, $template->output_format);
        $this->assertFalse($template->is_active);
        $this->assertSame(1, $version->version_number);
        $this->assertNotEmpty($version->checksum_sha256);
        $this->assertSame(['class.name'], $version->manifest['placeholders']);
        Storage::disk('local')->assertExists($version->file_path);
        $this->assertDatabaseMissing('export_template_activations', [
            'feature_key' => 'lhl.training_plan',
        ]);
        $this->assertDatabaseHas('export_template_audit_logs', [
            'template_id' => $template->id,
            'action' => ExportTemplateAuditLog::ACTION_TEMPLATE_CREATED,
        ]);
    }

    public function test_new_version_stays_draft_until_explicit_activation(): void
    {
        $template = $this->createTemplate();
        $versionOne = $template->versions()->firstOrFail();
        $versionTwo = $this->service()->createVersion(
            $template,
            $this->spreadsheetUpload('lhl-v2.xlsx')
        );

        $this->assertSame(2, $versionTwo->version_number);
        $this->assertFalse($template->fresh()->is_active);

        $this->service()->activateVersion($template, $versionOne);
        $this->service()->activateVersion($template, $versionTwo);

        $template->refresh();
        $this->assertTrue($template->is_active);
        $this->assertSame($versionTwo->file_path, $template->file_path);
        $this->assertDatabaseCount('export_template_activations', 1);
        $this->assertDatabaseHas('export_template_activations', [
            'template_version_id' => $versionTwo->id,
        ]);
    }

    public function test_clone_copies_latest_file_and_bindings_as_independent_draft(): void
    {
        $source = $this->createTemplate();
        $sourceVersion = $source->versions()->firstOrFail();
        $sourceVersion->bindings()->create([
            'target_ref' => 'excel:Sheet1!B2',
            'target_type' => 'cell',
            'data_key' => 'class.name',
            'binding_type' => 'scalar',
        ]);

        $clone = $this->service()->cloneTemplate($source, 'LHL Clone');
        $cloneVersion = $clone->versions()->firstOrFail();

        $this->assertNotSame($source->id, $clone->id);
        $this->assertNotSame($source->code, $clone->code);
        $this->assertSame('LHL Clone', $clone->name);
        $this->assertSame(TemplateStatus::DRAFT, $clone->status);
        $this->assertFalse($clone->is_active);
        $this->assertSame(1, $cloneVersion->bindings()->count());
        $this->assertSame($sourceVersion->checksum_sha256, $cloneVersion->checksum_sha256);
        Storage::disk('local')->assertExists($cloneVersion->file_path);
        $this->assertNotSame($sourceVersion->file_path, $cloneVersion->file_path);
    }

    public function test_active_template_cannot_be_archived_and_inactive_template_is_recoverable(): void
    {
        $source = $this->createTemplate();
        $sourceVersion = $source->versions()->firstOrFail();
        $this->service()->activateVersion($source, $sourceVersion);

        try {
            $this->service()->archiveTemplate($source);
            $this->fail('Active template phải bị chặn khi lưu trữ.');
        } catch (\DomainException $exception) {
            $this->assertStringContainsString('đang Active', $exception->getMessage());
        }

        $clone = $this->service()->cloneTemplate($source);
        $this->service()->activateVersion($clone, $clone->versions()->firstOrFail());
        $retainedPath = $sourceVersion->file_path;

        $this->service()->archiveTemplate($source->fresh());

        $this->assertSoftDeleted('export_templates', ['id' => $source->id]);
        $this->assertSoftDeleted('export_template_versions', ['id' => $sourceVersion->id]);
        Storage::disk('local')->assertExists($retainedPath);
        $this->assertDatabaseHas('export_template_audit_logs', [
            'template_id' => $source->id,
            'action' => ExportTemplateAuditLog::ACTION_ARCHIVED,
        ]);
    }

    public function test_new_version_must_match_template_format(): void
    {
        $template = $this->createTemplate();

        $this->expectException(\DomainException::class);

        $this->service()->createVersion(
            $template,
            UploadedFile::fake()->create(
                'wrong.docx',
                10,
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            )
        );
    }

    public function test_corrupted_template_is_rejected_without_database_or_file_residue(): void
    {
        try {
            $this->service()->createTemplate(
                [
                    'name' => 'File lỗi',
                    'feature_key' => 'lhl.training_plan',
                ],
                UploadedFile::fake()->create(
                    'broken.xlsx',
                    10,
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                ),
                ExportTemplate::SCOPE_LMS
            );
            $this->fail('File Excel hỏng phải bị từ chối.');
        } catch (InvalidTemplateException) {
            $this->assertDatabaseCount('export_templates', 0);
            $this->assertSame([], Storage::disk('local')->allFiles());
        }
    }

    public function test_legacy_version_can_be_reanalyzed_without_replacing_file(): void
    {
        $template = $this->createTemplate();
        $version = $template->versions()->firstOrFail();
        $originalPath = $version->file_path;
        $version->update([
            'manifest' => ['legacy_placeholders' => ['class.name']],
            'checksum_sha256' => null,
        ]);

        $analyzed = $this->service()->analyzeVersion($template, $version->fresh());

        $this->assertSame($originalPath, $analyzed->file_path);
        $this->assertSame('excel-v1', $analyzed->manifest['parser']);
        $this->assertSame(1, $analyzed->manifest['schema_version']);
        $this->assertGreaterThan(0, $analyzed->manifest['summary']['target_count']);
        $this->assertNotEmpty($analyzed->checksum_sha256);
        $this->assertDatabaseHas('export_template_audit_logs', [
            'template_id' => $template->id,
            'template_version_id' => $version->id,
            'action' => ExportTemplateAuditLog::ACTION_ANALYZED,
        ]);
    }

    public function test_management_routes_require_export_template_permissions(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('export-templates.portal.index', ['portal' => 'lms']))
            ->assertForbidden();

        Permission::findOrCreate('export-templates.index', 'web');
        $user->givePermissionTo('export-templates.index');

        $this->actingAs($user)
            ->get(route('export-templates.portal.index', ['portal' => 'lms']))
            ->assertOk()
            ->assertSee('Mẫu xuất · LMS');
    }

    public function test_version_upload_route_requires_edit_permission_before_validation(): void
    {
        $template = $this->createTemplate();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('export-templates.portal.versions.store', [
                'portal' => 'lms',
                'exportTemplate' => $template,
            ]))
            ->assertForbidden();
    }

    private function service(): TemplateLifecycleService
    {
        return app(TemplateLifecycleService::class);
    }

    private function createTemplate(): ExportTemplate
    {
        return $this->service()->createTemplate(
            [
                'name' => 'LHL Excel',
                'feature_key' => 'lhl.training_plan',
            ],
            $this->spreadsheetUpload('lhl.xlsx'),
            ExportTemplate::SCOPE_LMS
        );
    }

    private function spreadsheetUpload(string $originalName): UploadedFile
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.Str::uuid().'.xlsx';
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->setCellValue('A1', '{{class.name}}');
        $spreadsheet->getActiveSheet()->setCellValue('A2', 'Lớp');
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();
        $this->temporaryFiles[] = $path;

        return new UploadedFile(
            $path,
            $originalName,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }
}
