<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $now = now();
        DB::table('leave_object_types')->updateOrInsert(['code' => 'KHAC'], ['name' => 'Khác', 'sort_order' => 7, 'active' => 1, 'created_at' => $now, 'updated_at' => $now]);

        $annual = [
            ['CNQP', 0, 15, 20], ['CNQP', 15, 25, 25], ['CNQP', 25, null, 30],
            ['QNCN', 0, 15, 20], ['QNCN', 15, 25, 25], ['QNCN', 25, null, 30],
            ['SQ', 0, 15, 20], ['SQ', 15, 25, 25], ['SQ', 25, null, 30],
            ['VCQP', 0, 15, 20], ['VCQP', 15, 25, 25], ['VCQP', 25, null, 30],
            ['HSQBS', 0, null, 10],
        ];
        foreach ($annual as [$object, $min, $max, $days]) {
            DB::table('leave_regulations')->updateOrInsert(
                ['leave_type' => 'ANNUAL', 'object_type' => $object, 'min_years' => $min, 'max_years' => $max],
                ['base_days' => $days, 'label' => 'Phép hàng năm', 'description' => "{$object}: {$days} ngày", 'active' => 1, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        $extra = [
            ['01', 10, 'Đóng quân ở đơn vị xa nơi đăng ký nghỉ phép từ 500 km trở lên'],
            ['02', 10, 'Đóng quân ở địa bàn đặc biệt khó khăn, vùng sâu, vùng xa, biên giới cách nơi đăng ký nghỉ phép từ 300 km trở lên'],
            ['03', 10, 'Đóng quân tại các đảo thuộc quần đảo Trường Sa và Nhà giàn DK'],
            ['04', 5, 'Đơn vị đóng quân cách nơi đăng ký nghỉ phép từ 300 km đến dưới 500 km'],
            ['05', 5, 'Đóng quân ở vùng sâu, vùng xa, biên giới cách nơi đăng ký nghỉ phép từ 200 km đến dưới 300 km'],
            ['06', 5, 'Đóng quân tại các đảo được hưởng phụ cấp khu vực'],
        ];
        foreach ($extra as [$code, $days, $description]) {
            DB::table('leave_regulations')->updateOrInsert(
                ['leave_type' => 'EXTRA', 'label' => 'MS '.$code],
                ['object_type' => null, 'min_years' => null, 'max_years' => null, 'base_days' => $days, 'description' => $description, 'active' => 1, 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    public function down(): void
    {
        DB::table('leave_regulations')->where('leave_type', 'EXTRA')->whereIn('label', ['MS 01','MS 02','MS 03','MS 04','MS 05','MS 06'])->delete();
        DB::table('leave_regulations')->where('leave_type', 'ANNUAL')->whereIn('object_type', ['CNQP','QNCN','SQ','VCQP','HSQBS'])->delete();
        DB::table('leave_object_types')->where('code', 'KHAC')->delete();
    }
};
