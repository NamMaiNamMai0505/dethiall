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
        Schema::create('specializations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Tên lớp');
            $table->string('code', 50)->unique()->comment('Mã lớp');
            $table->text('description')->nullable()->comment('Mô tả');
            $table->enum('level', ['beginner', 'intermediate', 'advanced', 'expert'])
                  ->comment('Cấp độ: beginner, intermediate, advanced, expert');
            $table->integer('duration_months')->comment('Thời gian đào tạo (tháng)');
            $table->json('prerequisites')->nullable()->comment('Điều kiện tiên quyết');
            $table->enum('certification_type', ['certificate', 'diploma', 'degree', 'professional'])
                  ->comment('Loại chứng chỉ');
            $table->boolean('is_active')->default(true)->comment('Trạng thái hoạt động');

            $table->unsignedBigInteger('created_by')->nullable()->comment('Người tạo');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('Người cập nhật');

            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index(['is_active', 'level']);
            $table->index('certification_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('specializations');
    }
};
