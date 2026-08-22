<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * yearly_standard_results đồng thời là bảng kê khai giờ chuẩn hằng năm.
     * Chi tiết lịch được chụp dạng JSON để vẫn giữ đúng ba bảng nghiệp vụ.
     */
    public function up(): void
    {
        if (! Schema::hasTable('yearly_standard_results')) {
            return;
        }

        Schema::table('yearly_standard_results', function (Blueprint $table) {
            $table->date('declaration_from_date')->nullable();
            $table->date('declaration_to_date')->nullable();
            $table->decimal('schedule_teaching_hours', 10, 2)->default(0);
            $table->decimal('other_teaching_hours', 10, 2)->default(0);
            $table->text('other_teaching_notes')->nullable();
            $table->json('schedule_teaching_details')->nullable();
            $table->timestamp('schedule_retrieved_at')->nullable();
            $table->foreignId('declared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('declared_at')->nullable();
        });

        DB::table('yearly_standard_results')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $year = max(2000, min(2200, (int) $row->year));

                    DB::table('yearly_standard_results')
                        ->where('id', $row->id)
                        ->update([
                            'declaration_from_date' => Carbon::create($year, 1, 1)->toDateString(),
                            'declaration_to_date' => Carbon::create($year, 12, 31)->toDateString(),
                            'schedule_teaching_hours' => (float) $row->teaching_hours,
                            'other_teaching_hours' => 0,
                        ]);
                }
            });

        // TT 06/2026/BQP nội bộ: Đối tượng 02 (380 giờ) ×
        // Chủ nhiệm khoa (60%) = 228 giờ.
        if (Schema::hasTable('standard_positions')) {
            DB::table('standard_positions')
                ->where('name', 'Chủ nhiệm khoa')
                ->update([
                    'ratio_percent' => 60,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('yearly_standard_results')) {
            return;
        }

        Schema::table('yearly_standard_results', function (Blueprint $table) {
            $table->dropConstrainedForeignId('declared_by');
            $table->dropColumn([
                'declaration_from_date',
                'declaration_to_date',
                'schedule_teaching_hours',
                'other_teaching_hours',
                'other_teaching_notes',
                'schedule_teaching_details',
                'schedule_retrieved_at',
                'declared_at',
            ]);
        });
    }
};
