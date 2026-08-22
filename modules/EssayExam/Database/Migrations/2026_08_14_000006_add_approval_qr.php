<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('essay_exams', fn(Blueprint $table) => $table->string('approval_qr')->nullable()->unique()->after('approved_at')); }
    public function down(): void { Schema::table('essay_exams', fn(Blueprint $table) => $table->dropColumn('approval_qr')); }
};
