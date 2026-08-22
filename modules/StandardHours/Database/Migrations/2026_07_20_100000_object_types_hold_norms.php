<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Gộp định mức giờ chuẩn + NCKH vào Đối tượng (không gắn chức danh).
 * Chức danh chỉ còn tỉ lệ %; giờ phải đạt = standard_hours × ratio%.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('standard_object_types')) {
            Schema::table('standard_object_types', function (Blueprint $table) {
                if (! Schema::hasColumn('standard_object_types', 'code')) {
                    $table->string('code', 20)->nullable()->after('id');
                }
                if (! Schema::hasColumn('standard_object_types', 'standard_hours')) {
                    $table->decimal('standard_hours', 8, 2)->default(0)->after('description');
                }
                if (! Schema::hasColumn('standard_object_types', 'research_hours')) {
                    $table->decimal('research_hours', 8, 2)->default(0)->after('standard_hours');
                }
            });
        }

        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'object_type_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('object_type_id')
                    ->nullable()
                    ->after('position_id')
                    ->constrained('standard_object_types')
                    ->nullOnDelete();
            });
        }

        $this->backfillFromLegacyNorms();
        $this->ensureDefaultObjectTypes();

        // Unique code sau khi đã gán mã không trùng
        if (Schema::hasTable('standard_object_types') && Schema::hasColumn('standard_object_types', 'code')) {
            try {
                Schema::table('standard_object_types', function (Blueprint $table) {
                    $table->unique('code', 'standard_object_types_code_unique');
                });
            } catch (\Throwable) {
                // already exists
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'object_type_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('object_type_id');
            });
        }

        if (Schema::hasTable('standard_object_types')) {
            Schema::table('standard_object_types', function (Blueprint $table) {
                try {
                    $table->dropUnique('standard_object_types_code_unique');
                } catch (\Throwable) {
                }
                foreach (['code', 'standard_hours', 'research_hours'] as $col) {
                    if (Schema::hasColumn('standard_object_types', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }

    private function backfillFromLegacyNorms(): void
    {
        if (! Schema::hasTable('standard_object_types')) {
            return;
        }

        $usedCodes = [];
        $rows = DB::table('standard_object_types')->whereNull('deleted_at')->orderBy('id')->get();

        foreach ($rows as $ot) {
            $standard = (float) ($ot->standard_hours ?? 0);
            $research = (float) ($ot->research_hours ?? 0);

            if (Schema::hasTable('standard_hour_norms')) {
                $maxStd = DB::table('standard_hour_norms')
                    ->where('object_type_id', $ot->id)
                    ->whereNull('deleted_at')
                    ->max('standard_hours');
                if ($maxStd !== null && (float) $maxStd > 0) {
                    $standard = (float) $maxStd;
                }
            }

            if (Schema::hasTable('research_hour_norms')) {
                $maxRes = DB::table('research_hour_norms')
                    ->where('object_type_id', $ot->id)
                    ->whereNull('deleted_at')
                    ->max('research_hours');
                if ($maxRes !== null && (float) $maxRes > 0) {
                    $research = (float) $maxRes;
                }
            }

            $code = $ot->code ? (string) $ot->code : $this->guessCode((string) $ot->name);
            if ($code === '' || isset($usedCodes[$code])) {
                $code = 'OT-'.$ot->id;
            }
            // still collision
            while (isset($usedCodes[$code])) {
                $code = 'OT-'.$ot->id.'-'.substr(uniqid(), -3);
            }
            $usedCodes[$code] = true;

            DB::table('standard_object_types')->where('id', $ot->id)->update([
                'code' => $code,
                'standard_hours' => $standard,
                'research_hours' => $research,
                'updated_at' => now(),
            ]);
        }
    }

    private function ensureDefaultObjectTypes(): void
    {
        if (! Schema::hasTable('standard_object_types')) {
            return;
        }

        $defaults = [
            ['code' => '01', 'name' => 'Đối tượng 01', 'description' => 'Học viện / Trường sĩ quan / Đại học', 'standard_hours' => 280, 'research_hours' => 600],
            ['code' => '02', 'name' => 'Đối tượng 02', 'description' => 'Cao đẳng (CDHC2)', 'standard_hours' => 380, 'research_hours' => 300],
            ['code' => '03', 'name' => 'Đối tượng 03', 'description' => 'Trung cấp / Trường quân sự', 'standard_hours' => 430, 'research_hours' => 150],
        ];

        foreach ($defaults as $d) {
            $byCode = DB::table('standard_object_types')->whereNull('deleted_at')->where('code', $d['code'])->first();
            $byName = DB::table('standard_object_types')->whereNull('deleted_at')->where('name', $d['name'])->first();
            $exists = $byCode ?: $byName;

            if ($exists) {
                $std = (float) ($exists->standard_hours ?? 0);
                $res = (float) ($exists->research_hours ?? 0);
                DB::table('standard_object_types')->where('id', $exists->id)->update([
                    'code' => $d['code'],
                    'name' => $exists->name ?: $d['name'],
                    'description' => $exists->description ?: $d['description'],
                    'standard_hours' => $std > 0 ? $std : $d['standard_hours'],
                    'research_hours' => $res > 0 ? $res : $d['research_hours'],
                    'is_active' => 1,
                    'updated_at' => now(),
                ]);
            } else {
                // free code taken by another row?
                $codeTaken = DB::table('standard_object_types')->where('code', $d['code'])->whereNull('deleted_at')->exists();
                if ($codeTaken) {
                    continue;
                }
                DB::table('standard_object_types')->insert([
                    'code' => $d['code'],
                    'name' => $d['name'],
                    'description' => $d['description'],
                    'standard_hours' => $d['standard_hours'],
                    'research_hours' => $d['research_hours'],
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function guessCode(string $name): string
    {
        $n = mb_strtolower($name);
        if (preg_match('/\b01\b/', $n) || str_contains($n, 'đại học') || str_contains($n, 'học viện') || str_contains($n, 'sĩ quan')) {
            return '01';
        }
        if (preg_match('/\b02\b/', $n) || str_contains($n, 'cao đẳng')) {
            return '02';
        }
        if (preg_match('/\b03\b/', $n) || str_contains($n, 'trung cấp') || str_contains($n, 'quân sự')) {
            return '03';
        }

        return '';
    }
};
