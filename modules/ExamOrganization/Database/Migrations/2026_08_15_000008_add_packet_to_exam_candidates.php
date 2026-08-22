<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('exam_organization_candidates', fn(Blueprint $t) => $t->string('packet_number')->nullable()->after('room_name')); }
    public function down(): void { Schema::table('exam_organization_candidates', fn(Blueprint $t) => $t->dropColumn('packet_number')); }
};
