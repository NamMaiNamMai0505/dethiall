<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('training_schedules', function (Blueprint $table) {
            $table->string('abbreviation', 20)->nullable()->after('code')->comment('Tên viết tắt của lịch huấn luyện');
            $table->index('abbreviation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_schedules', function (Blueprint $table) {
            $table->dropIndex(['abbreviation']);
            $table->dropColumn('abbreviation');
        });
    }
};
