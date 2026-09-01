<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('leave_report_templates')) {
            return;
        }

        if (! Schema::hasColumn('leave_report_templates', 'managing_agency')) {
            Schema::table('leave_report_templates', function (Blueprint $table): void {
                $table->string('managing_agency', 40)->default('QUAN_LUC')->after('report_type')->index();
            });
        }

        $templates = [
            ['used', 'CO_QUAN_CAN_BO', 'Mẫu 01 - Báo cáo QN đã nghỉ phép - Diện Cán bộ QL', 'Mau 01_Bao cao QN da nghi phep_Dien Can bo QL.docx'],
            ['used', 'QUAN_LUC', 'Mẫu 01 - Báo cáo QN đã nghỉ phép - Diện Quân lực QL', 'Mau 01_Bao cao QN da nghi phep_Dien Quan luc QL.docx'],
            ['unused', 'CO_QUAN_CAN_BO', 'Mẫu 02 - Báo cáo QN chưa nghỉ phép - Diện Cán bộ QL', 'Mau 02_Bao cao QN chua nghi phep_Dien Can bo QL.docx'],
            ['unused', 'QUAN_LUC', 'Mẫu 02 - Báo cáo QN chưa nghỉ phép - Diện Quân lực QL', 'Mau 02_Bao cao QN chua nghi phep Dien Quan luc QL.docx'],
            ['tracking', 'CO_QUAN_CAN_BO', 'Mẫu 03 - Theo dõi thời gian nghỉ phép - Diện Cán bộ QL', 'Mau 03_Bao cao theo doi thoi gian nghi phep cua QN_Dien Can bo QL.docx'],
            ['tracking', 'QUAN_LUC', 'Mẫu 03 - Theo dõi thời gian nghỉ phép - Diện Quân lực QL', 'Mau 03_Bao cao theo doi thoi gian nghi phep cua QN_Dien Quan luc QL.docx'],
            ['registered', 'CO_QUAN_CAN_BO', 'Mẫu 04 - Báo cáo QN đăng ký nghỉ phép năm - Diện Cán bộ QL', 'Mau 04_Bao cao QN dang ky nghi phep nam_Dien Can bo QL.docx'],
            ['registered', 'QUAN_LUC', 'Mẫu 04 - Báo cáo QN đăng ký nghỉ phép năm - Diện Quân lực QL', 'Mau 04_Bao cao QN dang ky nghi phep nam_Dien Quan luc QL.docx'],
        ];

        foreach ($templates as [$reportType, $agency, $name, $fileName]) {
            $source = base_path('modules/LeaveManagement/Templates/report-templates/'.$fileName);
            if (! is_file($source)) {
                continue;
            }

            $target = 'leave-report-templates/defaults/'.$fileName;
            Storage::disk('local')->put($target, file_get_contents($source));

            DB::table('leave_report_templates')->updateOrInsert(
                ['report_type' => $reportType, 'managing_agency' => $agency, 'name' => $name],
                [
                    'description' => 'Mẫu mặc định theo biểu mẫu báo cáo phép.',
                    'disk' => 'local',
                    'file_path' => $target,
                    'original_name' => $fileName,
                    'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'file_size' => filesize($source),
                    'active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('leave_report_templates') && Schema::hasColumn('leave_report_templates', 'managing_agency')) {
            Schema::table('leave_report_templates', function (Blueprint $table): void {
                $table->dropColumn('managing_agency');
            });
        }
    }
};
