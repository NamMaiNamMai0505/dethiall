<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Mã đơn vị');
            $table->string('name')->comment('Tên đơn vị');
            $table->foreignId('parent_id')->nullable()->constrained('units')->nullOnDelete()->comment('Đơn vị cấp trên');
            $table->integer('level')->default(1)->comment('Cấp độ đơn vị');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('units');
    }
};
