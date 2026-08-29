<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('inventory_transfers', function (Blueprint $table): void {
            $table->date('performed_at')->nullable();
            $table->string('performing_unit')->nullable();
            $table->string('using_unit')->nullable();
            $table->date('decision_date')->nullable();
            $table->string('signer')->nullable();
            $table->string('requesting_unit')->nullable();
            $table->text('supplemental_reason')->nullable();
            $table->text('general_note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_transfers', function (Blueprint $table): void {
            $table->dropColumn(['performed_at', 'performing_unit', 'using_unit', 'decision_date', 'signer', 'requesting_unit', 'supplemental_reason', 'general_note']);
        });
    }
};
