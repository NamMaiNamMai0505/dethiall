<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Subject\Support\SpecialSubjectCatalog;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('subjects')) {
            return;
        }

        if (! Schema::hasColumn('subjects', 'is_special_activity')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->boolean('is_special_activity')
                    ->default(false)
                    ->after('is_required')
                    ->index()
                    ->comment('Hoạt động lịch dùng chung, không thuộc riêng ngành nào');
            });
        }

        try {
            Schema::table('subjects', function (Blueprint $table) {
                $table->unsignedBigInteger('specialization_id')->nullable()->change();
            });
        } catch (Throwable) {
            // Một số DB cũ cần doctrine/dbal; migration vẫn giữ catalog nếu cột đã nullable.
        }

        foreach (SpecialSubjectCatalog::definitions() as $code => $name) {
            $exists = DB::table('subjects')
                ->where('code', $code)
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                DB::table('subjects')
                    ->where('code', $code)
                    ->whereNull('deleted_at')
                    ->update([
                        'abbreviation' => $code,
                        'is_special_activity' => true,
                        'is_active' => true,
                        'updated_at' => now(),
                    ]);

                continue;
            }

            DB::table('subjects')->insert([
                'name' => $name,
                'code' => $code,
                'abbreviation' => $code,
                'description' => SpecialSubjectCatalog::DESCRIPTION_MARKER,
                'specialization_id' => null,
                'credits' => 0,
                'theory_hours' => 10000,
                'practice_hours' => 0,
                'self_study_hours' => 0,
                'exam_hours' => 0,
                'level' => 'basic',
                'prerequisites' => null,
                'assessment_method' => 'combined',
                'is_required' => false,
                'is_special_activity' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('subjects')) {
            return;
        }

        // Không xóa catalog tại đây: các môn đặc biệt có thể đã được lịch học,
        // LMS hoặc bảng điểm tham chiếu. Các bảng/record sẽ được migration gốc
        // dọn khi rollback toàn bộ, còn rollback riêng phải giữ toàn vẹn FK.

        if (Schema::hasColumn('subjects', 'is_special_activity')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->dropColumn('is_special_activity');
            });
        }
    }
};
