<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::create('leave_alerts',function(Blueprint $table):void{$table->id();$table->foreignId('user_id')->constrained('users')->cascadeOnDelete();$table->foreignId('request_id')->nullable()->constrained('leave_requests')->cascadeOnDelete();$table->string('kind',50);$table->string('title');$table->text('body')->nullable();$table->timestamp('read_at')->nullable();$table->timestamps();$table->index(['user_id','read_at']);}); } public function down(): void { Schema::dropIfExists('leave_alerts'); } };
