<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('research_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('research_categories', 'unit')) {
                $table->string('unit', 50)->nullable()->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('research_categories', function (Blueprint $table) {
            if (Schema::hasColumn('research_categories', 'unit')) {
                $table->dropColumn('unit');
            }
        });
    }
};
