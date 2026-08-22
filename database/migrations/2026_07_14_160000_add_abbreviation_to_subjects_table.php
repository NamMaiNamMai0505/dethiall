<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('subjects')) {
            return;
        }

        Schema::table('subjects', function (Blueprint $table) {
            if (! Schema::hasColumn('subjects', 'abbreviation')) {
                $table->string('abbreviation', 50)
                    ->nullable()
                    ->after('code')
                    ->comment('Viết tắt môn học — dùng khi xuất lịch (vd: TTT)');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('subjects') || ! Schema::hasColumn('subjects', 'abbreviation')) {
            return;
        }

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn('abbreviation');
        });
    }
};
