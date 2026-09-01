<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $now = now();

        DB::table('leave_object_types')->updateOrInsert(
            ['code' => 'HSQBS'],
            ['name' => 'Hạ sĩ quan, binh sĩ', 'sort_order' => 5, 'active' => 1, 'created_at' => $now, 'updated_at' => $now]
        );
        DB::table('leave_object_types')->updateOrInsert(
            ['code' => 'HV'],
            ['name' => 'Hạ sĩ quan, binh sĩ là học viên', 'sort_order' => 6, 'active' => 1, 'created_at' => $now, 'updated_at' => $now]
        );

        DB::table('leave_regulations')
            ->where('leave_type', 'ANNUAL')
            ->where('object_type', 'HSQBS')
            ->where('label', 'Phép hàng năm')
            ->delete();

        DB::table('leave_regulations')->updateOrInsert(
            ['leave_type' => 'ANNUAL', 'object_type' => 'HSQBS', 'min_years' => 1, 'max_years' => null],
            [
                'base_days' => 10,
                'label' => 'Phép hàng năm của HSQBS',
                'description' => 'Hạ sĩ quan, binh sĩ phục vụ tại ngũ từ tháng thứ mười ba trở đi thì được nghỉ phép hàng năm; thời gian nghỉ là 10 ngày (không kể ngày đi và về) và được thanh toán tiền tàu, xe, tiền phụ cấp đi đường theo quy định hiện hành.',
                'active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('leave_regulations')->updateOrInsert(
            ['leave_type' => 'ANNUAL', 'object_type' => 'HV', 'min_years' => 1, 'max_years' => null],
            [
                'base_days' => 0,
                'label' => 'Phép hàng năm của HSQBS là học viên',
                'description' => 'Hạ sĩ quan, binh sĩ là học viên các học viện, nhà trường trong, ngoài Quân đội, thời gian học từ một năm trở lên có thời gian nghỉ hè giữa hai năm học thì thời gian nghỉ này được tính là thời gian nghỉ phép và được thanh toán tiền tàu, xe, tiền phụ cấp đi đường theo quy định hiện hành.',
                'active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('leave_regulations')->updateOrInsert(
            ['leave_type' => 'SPECIAL', 'object_type' => 'HSQBS', 'min_years' => null, 'max_years' => null],
            [
                'base_days' => 5,
                'label' => 'Phép đặc biệt của HSQBS',
                'description' => 'Khoản 4, Điều 3 Nghị định 27/2016/NĐ-CP: Hạ sĩ quan, binh sĩ đã nghỉ phép năm theo chế độ, nếu gia đình gặp thiên tai, hỏa hoạn nặng hoặc bố, mẹ đẻ; bố, mẹ vợ hoặc bố, mẹ chồng; người nuôi dưỡng hợp pháp; vợ hoặc chồng và con đẻ, con nuôi hợp pháp từ trần, mất tích hoặc hạ sĩ quan, binh sĩ lập được thành tích đặc biệt xuất sắc trong thực hiện nhiệm vụ thì được nghỉ phép đặc biệt, thời gian không quá 05 ngày (không kể ngày đi và về) và được thanh toán tiền tàu, xe, tiền phụ cấp đi đường theo quy định hiện hành.',
                'active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    public function down(): void
    {
        DB::table('leave_regulations')
            ->whereIn('label', ['Phép hàng năm của HSQBS', 'Phép hàng năm của HSQBS là học viên', 'Phép đặc biệt của HSQBS'])
            ->delete();
    }
};
