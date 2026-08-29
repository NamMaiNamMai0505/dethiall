<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Commander routing is now resolved from the account role and unit.
        DB::table('leave_personnel')->update([
            'commander_user_id' => null,
            'commander_name' => null,
        ]);
    }

    public function down(): void
    {
        // Legacy links cannot be reconstructed safely.
    }
};
