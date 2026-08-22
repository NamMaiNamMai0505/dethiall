<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_warehouse_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('inventory_warehouses')->cascadeOnDelete();
            $table->foreignId('material_id')->nullable()->constrained('inventory_materials')->nullOnDelete();
            $table->string('code', 100);
            $table->string('name');
            $table->string('unit', 30)->nullable();
            $table->decimal('quantity', 14, 2)->default(0);
            $table->decimal('minimum_quantity', 14, 2)->default(0);
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['warehouse_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_warehouse_items');
    }
};
