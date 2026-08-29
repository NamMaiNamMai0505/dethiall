<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('leave_requests', 'printed_at')) {
            Schema::table('leave_requests', fn (Blueprint $table) => $table->timestamp('printed_at')->nullable()->after('status'));
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('leave_requests', 'printed_at')) {
            Schema::table('leave_requests', fn (Blueprint $table) => $table->dropColumn('printed_at'));
        }
    }
};
