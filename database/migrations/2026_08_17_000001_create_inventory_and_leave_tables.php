<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('inventory_materials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('inventory_categories')->nullOnDelete();
            $table->string('code', 80)->unique();
            $table->string('name');
            $table->string('unit', 30)->default('cái');
            $table->decimal('quantity', 14, 2)->default(0);
            $table->string('location')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('material_id')->constrained('inventory_materials')->cascadeOnDelete();
            $table->enum('type', ['IN', 'OUT', 'ADJUST']);
            $table->decimal('quantity', 14, 2);
            $table->string('reference')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('leave_personnel', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('staff_code', 80)->nullable()->index();
            $table->string('name');
            $table->string('unit')->nullable();
            $table->string('position')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('leave_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('personnel_id')->constrained('leave_personnel')->cascadeOnDelete();
            $table->date('from_date');
            $table->date('to_date');
            $table->string('leave_type', 80)->default('Nghỉ phép');
            $table->text('reason')->nullable();
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_personnel');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventory_materials');
        Schema::dropIfExists('inventory_categories');
    }
};
