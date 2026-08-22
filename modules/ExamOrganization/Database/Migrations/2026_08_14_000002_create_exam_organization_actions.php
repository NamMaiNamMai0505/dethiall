<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up():void{Schema::create('exam_organization_actions',function(Blueprint $t){$t->id();$t->foreignId('plan_id')->constrained('exam_organization_plans')->cascadeOnDelete();$t->string('action_type',30);$t->string('name');$t->string('status',30)->default('CREATED');$t->text('note')->nullable();$t->timestamps();});} public function down():void{Schema::dropIfExists('exam_organization_actions');} };
