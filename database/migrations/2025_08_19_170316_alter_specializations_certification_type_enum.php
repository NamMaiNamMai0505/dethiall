<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Bước 1: Thêm cột tạm thời để lưu giá trị mới
        Schema::table('specializations', function (Blueprint $table) {
            $table->string('certification_type_new', 20)->nullable()->after('certification_type');
        });

        // Bước 2: Mapping dữ liệu từ enum cũ sang giá trị mới
        DB::statement("
            UPDATE specializations 
            SET certification_type_new = CASE certification_type
                WHEN 'certificate' THEN 'certificate'
                WHEN 'diploma' THEN 'secondary_diploma'
                WHEN 'degree' THEN 'bachelor_degree'
                WHEN 'professional' THEN 'certificate'
                ELSE 'certificate'
            END
        ");

        // Bước 3: Xóa index cũ và cột certification_type cũ
        Schema::table('specializations', function (Blueprint $table) {
            $table->dropIndex(['certification_type']);
            $table->dropColumn('certification_type');
        });

        // Bước 4: Đổi tên cột mới thành certification_type
        Schema::table('specializations', function (Blueprint $table) {
            $table->renameColumn('certification_type_new', 'certification_type');
        });

        // Bước 5: Thay đổi kiểu dữ liệu thành enum với các giá trị mới
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("
            ALTER TABLE specializations
            MODIFY COLUMN certification_type ENUM(
                'certificate',
                'secondary_diploma', 
                'college_diploma', 
                'bachelor_degree', 
                'master_degree', 
                'doctorate_degree'
            ) NOT NULL 
            COMMENT 'Loại chứng chỉ: certificate=Chứng chỉ, secondary_diploma=Bằng trung cấp, college_diploma=Bằng cao đẳng, bachelor_degree=Bằng đại học, master_degree=Bằng thạc sĩ, doctorate_degree=Bằng tiến sĩ'
            ");
        }

        // Bước 6: Thêm lại index
        Schema::table('specializations', function (Blueprint $table) {
            $table->index('certification_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Bước 1: Thêm cột tạm thời để lưu giá trị cũ
        Schema::table('specializations', function (Blueprint $table) {
            $table->string('certification_type_old', 20)->nullable()->after('certification_type');
        });

        // Bước 2: Mapping dữ liệu từ enum mới về giá trị cũ
        DB::statement("
            UPDATE specializations 
            SET certification_type_old = CASE certification_type
                WHEN 'certificate' THEN 'certificate'
                WHEN 'secondary_diploma' THEN 'diploma'
                WHEN 'college_diploma' THEN 'diploma'
                WHEN 'bachelor_degree' THEN 'degree'
                WHEN 'master_degree' THEN 'degree'
                WHEN 'doctorate_degree' THEN 'degree'
                ELSE 'certificate'
            END
        ");

        // Bước 3: Xóa index cũ và cột certification_type hiện tại
        Schema::table('specializations', function (Blueprint $table) {
            $table->dropIndex(['certification_type']);
            $table->dropColumn('certification_type');
        });

        // Bước 4: Đổi tên cột cũ thành certification_type
        Schema::table('specializations', function (Blueprint $table) {
            $table->renameColumn('certification_type_old', 'certification_type');
        });

        // Bước 5: Khôi phục enum cũ
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("
            ALTER TABLE specializations
            MODIFY COLUMN certification_type ENUM(
                'certificate',
                'diploma',
                'degree', 
                'professional'
            ) NOT NULL COMMENT 'Loại chứng chỉ'
            ");
        }

        // Bước 6: Thêm lại index
        Schema::table('specializations', function (Blueprint $table) {
            $table->index('certification_type');
        });
    }
};
