<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::table('leave_requests',function(Blueprint $t):void{$t->date('from_date')->nullable()->change();$t->date('to_date')->nullable()->change();}); } public function down(): void { Schema::table('leave_requests',function(Blueprint $t):void{$t->date('from_date')->nullable(false)->change();$t->date('to_date')->nullable(false)->change();}); } };
