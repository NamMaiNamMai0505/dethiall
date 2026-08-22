<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tách giờ của sản phẩm, giờ hệ thống gợi ý cho người kê khai và giờ chốt
     * thực tế. Từ phiên bản này mỗi kê khai NCKH chỉ khai cho chính một người.
     */
    public function up(): void
    {
        if (! Schema::hasTable('instructor_research_records')) {
            return;
        }

        Schema::table('instructor_research_records', function (Blueprint $table) {
            $table->decimal('annual_product_hours', 10, 2)->nullable();
            $table->decimal('calculated_hours', 10, 2)->nullable();
            $table->decimal('contribution_percent', 5, 2)->nullable();
            $table->text('hours_adjustment_note')->nullable();
        });

        DB::table('instructor_research_records')
            ->orderBy('id')
            ->chunkById(200, function ($records) {
                foreach ($records as $record) {
                    $member = null;

                    if (Schema::hasTable('research_record_members')) {
                        $member = DB::table('research_record_members')
                            ->where('research_record_id', $record->id)
                            ->where(function ($query) use ($record) {
                                $query->where('is_declarant', true)
                                    ->orWhere('instructor_id', $record->instructor_id);
                            })
                            ->orderByDesc('is_declarant')
                            ->orderBy('sort_order')
                            ->first();
                    }

                    $legacyProductHours = (float) $record->converted_hours;
                    $personalHours = $member
                        ? (float) $member->converted_hours
                        : $legacyProductHours;

                    DB::table('instructor_research_records')
                        ->where('id', $record->id)
                        ->update([
                            'annual_product_hours' => $legacyProductHours,
                            'calculated_hours' => $personalHours,
                            'converted_hours' => $personalHours,
                            'contribution_percent' => $member?->contribution_percent,
                        ]);
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('instructor_research_records')) {
            return;
        }

        DB::table('instructor_research_records')
            ->whereNotNull('annual_product_hours')
            ->update([
                'converted_hours' => DB::raw('annual_product_hours'),
            ]);

        Schema::table('instructor_research_records', function (Blueprint $table) {
            $table->dropColumn([
                'annual_product_hours',
                'calculated_hours',
                'contribution_percent',
                'hours_adjustment_note',
            ]);
        });
    }
};
