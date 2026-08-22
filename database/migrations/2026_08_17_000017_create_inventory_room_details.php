<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::create('inventory_room_images',function(Blueprint $t):void{$t->id();$t->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();$t->string('path');$t->string('caption')->nullable();$t->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();$t->timestamps();}); Schema::create('inventory_room_users',function(Blueprint $t):void{$t->id();$t->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();$t->foreignId('user_id')->constrained('users')->cascadeOnDelete();$t->string('role')->nullable();$t->timestamps();$t->unique(['classroom_id','user_id']);}); }
    public function down(): void { Schema::dropIfExists('inventory_room_users');Schema::dropIfExists('inventory_room_images'); }
};
