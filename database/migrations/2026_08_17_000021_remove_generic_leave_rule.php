<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('leave_regulations')->where('leave_type', 'ANNUAL')->whereNull('object_type')->delete();
    }

    public function down(): void
    {
        DB::table('leave_regulations')->insert([
            'leave_type' => 'ANNUAL', 'object_type' => null, 'base_days' => 12,
            'label' => 'Phép năm tiêu chuẩn', 'active' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
};
