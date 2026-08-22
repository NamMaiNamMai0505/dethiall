<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * php artisan migrate --path=database/migrations/2025_10_31_120819_add_class_instructor_and_type_to_users_table.php
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->foreignId('instructor_id')->nullable()->constrained('instructors')->nullOnDelete();
            $table->enum('user_type', ['internal_user', 'student', 'instructor'])->default('internal_user');
        });
    }

    /**
     * Reverse the migrations.
     * php artisan migrate:rollback --step=1
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['class_id']);
            $table->dropForeign(['instructor_id']);
            $table->dropColumn(['class_id', 'instructor_id', 'user_type']);
        });
    }
};
