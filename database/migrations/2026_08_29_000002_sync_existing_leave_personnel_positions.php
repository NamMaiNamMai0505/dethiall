<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('leave_personnel as lp')
            ->leftJoin('users as u', 'u.id', '=', 'lp.user_id')
            ->leftJoin('standard_positions as p', 'p.id', '=', 'u.position_id')
            ->where('lp.active', true)
            ->whereNotNull('lp.user_id')
            ->select('lp.id', 'u.position_id', 'p.name')
            ->get();

        foreach ($rows as $row) {
            DB::table('leave_personnel')->where('id', $row->id)->update([
                'position_id' => $row->position_id,
                'position' => $row->name,
            ]);
        }
    }

    public function down(): void
    {
        // The previous free-text values cannot be restored reliably.
    }
};
