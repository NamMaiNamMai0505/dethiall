<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * php artisan migrate --path=database/migrations/2025_11_03_090643_add_code_to_users_table.php
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('code')->nullable()->after('name')->comment('Mã số học viên/nhân viên');
        });
    }

    /**
     * Reverse the migrations.
     * php artisan migrate:rollback --path=database/migrations/2025_11_03_090643_add_code_to_users_table.php
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
