<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void { Schema::table('inventory_categories', function (Blueprint $table): void { $table->foreignId('parent_id')->nullable()->after('id')->constrained('inventory_categories')->nullOnDelete(); }); }
    public function down(): void { Schema::table('inventory_categories', function (Blueprint $table): void { $table->dropConstrainedForeignId('parent_id'); }); }
};
