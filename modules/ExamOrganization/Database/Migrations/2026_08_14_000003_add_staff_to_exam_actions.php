<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up():void{Schema::table('exam_organization_actions',function(Blueprint $t){$t->foreignId('instructor_id')->nullable()->constrained('instructors')->nullOnDelete();$t->string('role',30)->nullable();});} public function down():void{Schema::table('exam_organization_actions',fn(Blueprint $t)=>$t->dropConstrainedForeignId('instructor_id'));} };
