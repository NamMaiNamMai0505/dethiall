<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, array<string, array{0:mixed,1:string}>> */
    private array $defaults = [
        'shared' => [
            'parent_organization_name' => ['TỔNG CỤC HẬU CẦN - KỸ THUẬT', 'string'],
            'national_heading' => ['CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM', 'string'],
            'national_motto' => ['Độc lập - Tự do - Hạnh phúc', 'string'],
            'document_location' => ['Thành phố Hồ Chí Minh', 'string'],
            'default_export_format' => ['excel', 'string'],
            'default_page_size' => ['A4', 'string'],
            'default_orientation' => ['landscape', 'string'],
        ],
        'lms' => [
            'default_assignment_max_score' => [10, 'number'],
            'submission_max_file_mb' => [50, 'number'],
            'allow_late_by_default' => [false, 'boolean'],
            'default_exam_duration_minutes' => [45, 'number'],
            'default_exam_attempts' => [1, 'number'],
            'default_exam_pass_score' => [5, 'number'],
            'shuffle_questions_by_default' => [true, 'boolean'],
            'notify_assignment_graded' => [true, 'boolean'],
        ],
        'grades' => [
            'excellent_score' => [8, 'number'],
            'rounding_mode' => ['half_up', 'string'],
            'weight_oral_15' => [10, 'number'],
            'weight_period_1' => [20, 'number'],
            'weight_midterm' => [30, 'number'],
            'weight_final' => [40, 'number'],
        ],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('system_settings')) {
            return;
        }

        $now = now();
        foreach ($this->defaults as $portal => $settings) {
            foreach ($settings as $key => [$value, $type]) {
                DB::table('system_settings')->updateOrInsert(
                    ['portal' => $portal, 'key' => $key],
                    [
                        'value' => json_encode($value, JSON_UNESCAPED_UNICODE),
                        'type' => $type,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('system_settings')) {
            return;
        }

        foreach ($this->defaults as $portal => $settings) {
            DB::table('system_settings')
                ->where('portal', $portal)
                ->whereIn('key', array_keys($settings))
                ->delete();
        }
    }
};
