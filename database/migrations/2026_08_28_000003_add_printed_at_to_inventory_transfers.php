<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('inventory_transfers', 'printed_at')) {
            Schema::table('inventory_transfers', function (Blueprint $table) {
                $table->dateTime('printed_at')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('inventory_transfers', 'printed_at')) {
            Schema::table('inventory_transfers', function (Blueprint $table) {
                $table->dropColumn('printed_at');
            });
        }
    }
};
