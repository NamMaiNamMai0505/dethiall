<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 44 / B3 — Phân công Khoa cho môn học phải là dữ liệu tường minh,
 * không suy diễn từ 2 ký tự cuối của mã môn học (mã môn có thể đổi theo quy
 * ước khác, hoặc môn thuộc NVQY không có hậu tố K1..K8 nào cả).
 *
 * `subjects.faculty_code` là nguồn phân công thật; hậu tố mã môn chỉ còn dùng
 * làm giá trị nạp sẵn cho môn cũ (xem backfill) và làm phương án dự phòng khi
 * `faculty_code` chưa được gán (SubjectCodePrefix::applyFacultyCodeScope()).
 *
 * `units.abbreviation` là tên viết tắt của đơn vị dùng trong báo cáo/thống kê
 * — tên đầy đủ "Khoa Công nghệ thông tin" quá dài để hiện trên các bảng hẹp.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('subjects') && ! Schema::hasColumn('subjects', 'faculty_code')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->string('faculty_code', 16)->nullable()->after('code')
                    ->index('subjects_faculty_code_index');
            });

            // Nạp sẵn từ hậu tố mã môn hiện có (K1..K8) để không đổi hành vi
            // ngay sau khi chạy migration — chỉ đổi CÁCH lưu, không đổi kết
            // quả lọc cho dữ liệu cũ. Các trường hợp đặc biệt (NVQY, đổi khoa
            // sau này...) sửa tay qua form Môn học.
            $driver = DB::connection()->getDriverName();

            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                DB::statement("
                    UPDATE subjects
                    SET faculty_code = UPPER(RIGHT(code, 2))
                    WHERE faculty_code IS NULL
                      AND UPPER(RIGHT(code, 2)) REGEXP '^K[1-8]$'
                ");
            } else {
                // Driver không có REGEXP built-in (vd SQLite) — nạp bằng PHP.
                DB::table('subjects')->whereNull('faculty_code')->orderBy('id')
                    ->select('id', 'code')->chunkById(500, function ($rows) {
                        foreach ($rows as $row) {
                            $suffix = mb_strtoupper(mb_substr((string) $row->code, -2));
                            if (preg_match('/^K[1-8]$/', $suffix)) {
                                DB::table('subjects')->where('id', $row->id)->update(['faculty_code' => $suffix]);
                            }
                        }
                    });
            }
        }

        if (Schema::hasTable('units') && ! Schema::hasColumn('units', 'abbreviation')) {
            Schema::table('units', function (Blueprint $table) {
                $table->string('abbreviation', 50)->nullable()->after('name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('subjects') && Schema::hasColumn('subjects', 'faculty_code')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->dropIndex('subjects_faculty_code_index');
                $table->dropColumn('faculty_code');
            });
        }

        if (Schema::hasTable('units') && Schema::hasColumn('units', 'abbreviation')) {
            Schema::table('units', function (Blueprint $table) {
                $table->dropColumn('abbreviation');
            });
        }
    }
};
