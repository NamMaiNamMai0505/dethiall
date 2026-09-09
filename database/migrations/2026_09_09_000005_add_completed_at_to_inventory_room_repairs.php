<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_room_repairs', function (Blueprint $table): void {
            if (! Schema::hasColumn('inventory_room_repairs', 'completed_at')) {
                $table->date('completed_at')->nullable()->after('repair_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_room_repairs', function (Blueprint $table): void {
            if (Schema::hasColumn('inventory_room_repairs', 'completed_at')) {
                $table->dropColumn('completed_at');
            }
        });
    }
};
