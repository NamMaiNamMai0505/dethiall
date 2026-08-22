<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::create('leave_mail_settings',function(Blueprint $t):void{$t->id();$t->string('host')->nullable();$t->unsignedInteger('port')->nullable();$t->string('username')->nullable();$t->text('password')->nullable();$t->string('from_address')->nullable();$t->string('from_name')->nullable();$t->string('encryption')->nullable();$t->boolean('dev_mode')->default(false);$t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();$t->timestamps();}); }
    public function down(): void { Schema::dropIfExists('leave_mail_settings'); }
};
