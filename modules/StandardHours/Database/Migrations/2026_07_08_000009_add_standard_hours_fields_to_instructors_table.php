<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instructors', function (Blueprint $table) {
            $table->foreignId('object_type_id')->nullable()->after('unit_id')
                ->constrained('standard_object_types')->nullOnDelete();
            $table->foreignId('position_id')->nullable()->after('object_type_id')
                ->constrained('standard_positions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('instructors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('position_id');
            $table->dropConstrainedForeignId('object_type_id');
        });
    }
};
