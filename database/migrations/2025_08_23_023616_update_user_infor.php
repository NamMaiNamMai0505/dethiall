<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Unit
            if (!Schema::hasColumn('users', 'unit_id')) {
                $table->unsignedBigInteger('unit_id')->nullable()->after('id');
            }

            // Role
            if (!Schema::hasColumn('users', 'role_id')) {
                $table->unsignedInteger('role_id')->after('unit_id');
            }

            // Status
            if (!Schema::hasColumn('users', 'status')) {
                $table->unsignedTinyInteger('status')
                    ->default(1)
                    ->after('role_id')
                    ->comment('1: Active, 0: Inactive');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'unit_id')) {
                $table->dropColumn('unit_id');
            }
            if (Schema::hasColumn('users', 'role_id')) {
                $table->dropColumn('role_id');
            }
            if (Schema::hasColumn('users', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
