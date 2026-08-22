<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::create('inventory_audit_logs',function(Blueprint $table):void{$table->id();$table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();$table->string('action',80);$table->string('entity_type',80);$table->unsignedBigInteger('entity_id')->nullable();$table->json('details')->nullable();$table->timestamps();}); } public function down(): void { Schema::dropIfExists('inventory_audit_logs'); } };
