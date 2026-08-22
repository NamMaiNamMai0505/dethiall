<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\ExportTemplates\Enums\OutputFormat;
use Modules\ExportTemplates\Enums\TemplateStatus;
use Modules\ExportTemplates\Models\ExportTemplate;
use Modules\ExportTemplates\Services\BuilderTemplateSchema;

class BuilderTemplateDemoSeeder extends Seeder
{
    public function run(): void
    {
        $template = ExportTemplate::query()->updateOrCreate(
            ['code' => 'demo_lhl_builder_excel'],
            [
                'name' => 'Demo LHL Builder — Lịch tuần',
                'scope' => ExportTemplate::SCOPE_DASHBOARD,
                'module_key' => ExportTemplate::SCOPE_DASHBOARD,
                'feature_key' => 'lhl.training_plan',
                'output_format' => OutputFormat::EXCEL,
                'description' => 'Mẫu demo tạo bằng Template Builder cho Lịch Huấn Luyện.',
                'status' => TemplateStatus::DRAFT,
                'is_active' => false,
                'created_by' => 1,
                'updated_by' => 1,
            ]
        );

        $version = $template->versions()->firstOrCreate(
            ['version_number' => 1],
            [
                'disk' => 'local',
                'file_path' => null,
                'original_name' => 'demo-lhl-builder.xlsx',
                'file_extension' => 'xlsx',
                'status' => TemplateStatus::DRAFT,
                'created_by' => 1,
            ]
        );

        $version->builderDocument()->updateOrCreate([], [
            'schema_version' => BuilderTemplateSchema::VERSION,
            'created_by' => 1,
            'schema' => [
                'schema_version' => BuilderTemplateSchema::VERSION,
                'format' => 'excel',
                'page' => ['size' => 'A4', 'orientation' => 'landscape'],
                'blocks' => [
                    [
                        'id' => 'demo-title',
                        'type' => 'heading',
                        'props' => [
                            'text' => '{{organization.title}}',
                            'font_size' => 16,
                            'bold' => true,
                            'align' => 'center',
                        ],
                    ],
                    [
                        'id' => 'demo-class',
                        'type' => 'text',
                        'props' => [
                            'text' => 'Lớp: {{class.name}}   |   Đơn vị: {{class.management_unit}}   |   Phòng: {{class.room}}',
                            'font_size' => 12,
                            'bold' => true,
                        ],
                    ],
                    [
                        'id' => 'demo-week',
                        'type' => 'text',
                        'props' => [
                            'text' => 'Tuần {{time.week}} ({{time.start_date}} — {{time.end_date}})',
                            'align' => 'center',
                        ],
                    ],
                    [
                        'id' => 'demo-schedule',
                        'type' => 'table',
                        'props' => [
                            'rows' => 2,
                            'columns' => 5,
                            'row_height' => 28,
                            'column_width' => 140,
                            'padding' => 4,
                            'collection_key' => 'schedule_groups[]',
                            'header_0' => 'Tiết',
                            'header_1' => 'Môn học',
                            'header_2' => 'Giảng viên',
                            'header_3' => 'Địa điểm',
                            'header_4' => 'Nội dung',
                            'column_bindings' => [
                                'schedule_groups[].period_label',
                                'schedule_groups[].subject_short_name',
                                'schedule_groups[].teacher_name',
                                'schedule_groups[].location',
                                'schedule_groups[].content',
                            ],
                        ],
                    ],
                    [
                        'id' => 'demo-signatures',
                        'type' => 'signature',
                        'props' => [
                            'text' => 'Người làm lịch: {{signature.schedule_maker.name}}    Trưởng phòng: {{signature.training_manager.name}}',
                            'align' => 'right',
                        ],
                    ],
                ],
            ],
        ]);

        $this->command?->info("Đã tạo demo Builder Template #{$template->id}, version #{$version->id} (Draft).");
    }
}
