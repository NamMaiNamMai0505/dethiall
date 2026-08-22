<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hệ đào tạo (Dân sự / Quân sự) + LMS tweaks (chat lock, assignment ↔ lesson).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('training_systems')) {
            Schema::create('training_systems', function (Blueprint $table) {
                $table->id();
                $table->string('code', 40)->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });

            DB::table('training_systems')->insert([
                [
                    'code' => 'civilian',
                    'name' => 'Hệ Dân sự',
                    'description' => 'Chương trình đào tạo hệ dân sự',
                    'is_active' => 1,
                    'sort_order' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'military',
                    'name' => 'Hệ Quân sự',
                    'description' => 'Chương trình đào tạo hệ quân sự',
                    'is_active' => 1,
                    'sort_order' => 2,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        if (Schema::hasTable('specializations') && ! Schema::hasColumn('specializations', 'training_system_id')) {
            Schema::table('specializations', function (Blueprint $table) {
                $table->foreignId('training_system_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('training_systems')
                    ->nullOnDelete();
            });

            // Default existing rows → Hệ Quân sự (phù hợp ngữ cảnh CDHC2)
            $militaryId = DB::table('training_systems')->where('code', 'military')->value('id');
            if ($militaryId) {
                DB::table('specializations')->whereNull('training_system_id')->update(['training_system_id' => $militaryId]);
            }
        }

        if (Schema::hasTable('lms_courses') && ! Schema::hasColumn('lms_courses', 'chat_locked')) {
            Schema::table('lms_courses', function (Blueprint $table) {
                $table->boolean('chat_locked')->default(false)->after('status');
            });
        }

        if (Schema::hasTable('lms_assignments') && ! Schema::hasColumn('lms_assignments', 'lms_lesson_id')) {
            Schema::table('lms_assignments', function (Blueprint $table) {
                $table->foreignId('lms_lesson_id')
                    ->nullable()
                    ->after('lms_course_id')
                    ->constrained('lms_lessons')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('lms_assignments') && Schema::hasColumn('lms_assignments', 'lms_lesson_id')) {
            Schema::table('lms_assignments', function (Blueprint $table) {
                $table->dropConstrainedForeignId('lms_lesson_id');
            });
        }
        if (Schema::hasTable('lms_courses') && Schema::hasColumn('lms_courses', 'chat_locked')) {
            Schema::table('lms_courses', function (Blueprint $table) {
                $table->dropColumn('chat_locked');
            });
        }
        if (Schema::hasTable('specializations') && Schema::hasColumn('specializations', 'training_system_id')) {
            Schema::table('specializations', function (Blueprint $table) {
                $table->dropConstrainedForeignId('training_system_id');
            });
        }
        Schema::dropIfExists('training_systems');
    }
};
