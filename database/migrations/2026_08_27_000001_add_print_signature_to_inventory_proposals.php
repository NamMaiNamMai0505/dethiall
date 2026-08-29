<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('inventory_proposals', function (Blueprint $table): void {
            $table->string('print_mode')->nullable();
            $table->string('signature_method')->nullable();
            $table->string('signature_path')->nullable();
            $table->timestamp('printed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_proposals', function (Blueprint $table): void {
            $table->dropColumn(['print_mode', 'signature_method', 'signature_path', 'printed_at']);
        });
    }
};
