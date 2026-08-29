<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('inventory_transfers', 'quantity')) {
            Schema::table('inventory_transfers', function (Blueprint $table) {
                $table->unsignedInteger('quantity')->default(1)->after('type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('inventory_transfers', 'quantity')) {
            Schema::table('inventory_transfers', function (Blueprint $table) {
                $table->dropColumn('quantity');
            });
        }
    }
};
