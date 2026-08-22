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
        // Kiểm tra xem cột classroom có tồn tại không trước khi migrate
        if (!Schema::hasColumn('classes', 'classroom')) {
            // Cột classroom đã bị xóa, không cần migrate nữa
            return;
        }

        // Migrate dữ liệu từ classroom (string) sang classroom_id (foreign key)
        $classes = DB::table('classes')->whereNotNull('classroom')->get();
        
        foreach ($classes as $class) {
            // Tìm giảng đường có tên tương ứng
            $classroom = DB::table('classrooms')
                ->where('name', $class->classroom)
                ->first();
            
            if ($classroom) {
                // Cập nhật classroom_id
                DB::table('classes')
                    ->where('id', $class->id)
                    ->update(['classroom_id' => $classroom->id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Không cần rollback vì ta giữ lại cả hai trường
    }
};
