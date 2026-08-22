<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            /** nguoi_lam_lich | kt_truong_phong | kt_hieu_truong | custom */
            $table->string('slot_key', 64)->index();
            $table->string('display_name', 255);
            $table->string('role_line1', 255)->nullable();
            $table->string('role_line2', 255)->nullable();
            $table->string('image_path', 500)->nullable();
            /** JSON list tên để claim tự động */
            $table->json('match_names')->nullable();
            $table->boolean('is_system_template')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'slot_key']);
            $table->index(['is_system_template', 'slot_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_signatures');
    }
};
