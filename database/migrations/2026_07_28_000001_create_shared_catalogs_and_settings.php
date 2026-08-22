<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('military_ranks', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('group_key', 40);
            $table->string('group_name', 100);
            $table->string('navy_equivalent')->nullable();
            $table->string('abbreviation', 20)->nullable();
            $table->unsignedTinyInteger('stars')->nullable();
            $table->unsignedTinyInteger('bars')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['group_key', 'is_active', 'sort_order'], 'military_ranks_catalog_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('military_rank_id')
                ->nullable()
                ->after('role_id')
                ->constrained('military_ranks')
                ->nullOnDelete();
        });

        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('code', 9)->unique();
            $table->unsignedSmallInteger('start_year')->unique();
            $table->unsignedSmallInteger('end_year');
            $table->string('name', 50);
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->boolean('is_current')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'start_year'], 'academic_years_active_idx');
        });

        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('portal', 30)->default('shared');
            $table->string('key', 100);
            $table->json('value')->nullable();
            $table->string('type', 30)->default('string');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['portal', 'key'], 'system_settings_portal_key_uq');
        });

        $now = now();
        $ranks = [
            ['general_army', 'Đại tướng', 'officer_general', 'Sĩ quan · Cấp Tướng', null, 'ĐT', 4, 0],
            ['senior_general', 'Thượng tướng', 'officer_general', 'Sĩ quan · Cấp Tướng', 'Đô đốc Hải quân', 'ThgT', 3, 0],
            ['lieutenant_general', 'Trung tướng', 'officer_general', 'Sĩ quan · Cấp Tướng', 'Phó Đô đốc Hải quân', 'TrTg', 2, 0],
            ['major_general', 'Thiếu tướng', 'officer_general', 'Sĩ quan · Cấp Tướng', 'Chuẩn Đô đốc Hải quân', 'ThTg', 1, 0],
            ['colonel', 'Đại tá', 'officer_field', 'Sĩ quan · Cấp Tá', null, '4//', 4, 2],
            ['senior_lieutenant_colonel', 'Thượng tá', 'officer_field', 'Sĩ quan · Cấp Tá', null, '3//', 3, 2],
            ['lieutenant_colonel', 'Trung tá', 'officer_field', 'Sĩ quan · Cấp Tá', null, '2//', 2, 2],
            ['major', 'Thiếu tá', 'officer_field', 'Sĩ quan · Cấp Tá', null, '1//', 1, 2],
            ['captain', 'Đại úy', 'officer_company', 'Sĩ quan · Cấp Úy', null, '4/', 4, 1],
            ['senior_lieutenant', 'Thượng úy', 'officer_company', 'Sĩ quan · Cấp Úy', null, '3/', 3, 1],
            ['first_lieutenant', 'Trung úy', 'officer_company', 'Sĩ quan · Cấp Úy', null, '2/', 2, 1],
            ['second_lieutenant', 'Thiếu úy', 'officer_company', 'Sĩ quan · Cấp Úy', null, '1/', 1, 1],
            ['sergeant_major', 'Thượng sĩ', 'nco_soldier', 'Hạ sĩ quan – Binh sĩ', null, null, 3, null],
            ['sergeant', 'Trung sĩ', 'nco_soldier', 'Hạ sĩ quan – Binh sĩ', null, null, 2, null],
            ['corporal', 'Hạ sĩ', 'nco_soldier', 'Hạ sĩ quan – Binh sĩ', null, null, 1, null],
            ['private_first_class', 'Binh nhất', 'nco_soldier', 'Hạ sĩ quan – Binh sĩ', null, null, null, null],
            ['private', 'Binh nhì', 'nco_soldier', 'Hạ sĩ quan – Binh sĩ', null, null, null, null],
            ['qpc_senior_lieutenant_colonel', 'Thượng tá QNCN', 'professional', 'Quân nhân chuyên nghiệp', null, '3// QNCN', 3, 2],
            ['qpc_lieutenant_colonel', 'Trung tá QNCN', 'professional', 'Quân nhân chuyên nghiệp', null, '2// QNCN', 2, 2],
            ['qpc_major', 'Thiếu tá QNCN', 'professional', 'Quân nhân chuyên nghiệp', null, '1// QNCN', 1, 2],
            ['qpc_captain', 'Đại úy QNCN', 'professional', 'Quân nhân chuyên nghiệp', null, '4/ QNCN', 4, 1],
            ['qpc_senior_lieutenant', 'Thượng úy QNCN', 'professional', 'Quân nhân chuyên nghiệp', null, '3/ QNCN', 3, 1],
            ['qpc_first_lieutenant', 'Trung úy QNCN', 'professional', 'Quân nhân chuyên nghiệp', null, '2/ QNCN', 2, 1],
            ['qpc_second_lieutenant', 'Thiếu úy QNCN', 'professional', 'Quân nhân chuyên nghiệp', null, '1/ QNCN', 1, 1],
        ];

        DB::table('military_ranks')->insert(array_map(
            fn (array $rank, int $index) => [
                'code' => $rank[0],
                'name' => $rank[1],
                'group_key' => $rank[2],
                'group_name' => $rank[3],
                'navy_equivalent' => $rank[4],
                'abbreviation' => $rank[5],
                'stars' => $rank[6],
                'bars' => $rank[7],
                'sort_order' => $index + 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $ranks,
            array_keys($ranks)
        ));

        $currentStart = now()->month >= 8 ? now()->year : now()->year - 1;
        $years = [];
        for ($start = $currentStart - 2; $start <= $currentStart + 5; $start++) {
            $years[] = [
                'code' => $start.'-'.($start + 1),
                'start_year' => $start,
                'end_year' => $start + 1,
                'name' => 'Năm học '.$start.'-'.($start + 1),
                'starts_at' => $start.'-08-01',
                'ends_at' => ($start + 1).'-07-31',
                'is_current' => $start === $currentStart,
                'is_active' => true,
                'sort_order' => $start,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('academic_years')->insert($years);

        DB::table('system_settings')->insert([
            ['portal' => 'shared', 'key' => 'organization_name', 'value' => json_encode('TRƯỜNG CAO ĐẲNG HẬU CẦN 2', JSON_UNESCAPED_UNICODE), 'type' => 'string', 'created_at' => $now, 'updated_at' => $now],
            ['portal' => 'lms', 'key' => 'default_course_status', 'value' => json_encode('draft'), 'type' => 'string', 'created_at' => $now, 'updated_at' => $now],
            ['portal' => 'grades', 'key' => 'max_score', 'value' => json_encode(10), 'type' => 'number', 'created_at' => $now, 'updated_at' => $now],
            ['portal' => 'grades', 'key' => 'pass_score', 'value' => json_encode(5), 'type' => 'number', 'created_at' => $now, 'updated_at' => $now],
            ['portal' => 'grades', 'key' => 'decimal_places', 'value' => json_encode(1), 'type' => 'number', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('academic_years');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('military_rank_id');
        });

        Schema::dropIfExists('military_ranks');
    }
};
