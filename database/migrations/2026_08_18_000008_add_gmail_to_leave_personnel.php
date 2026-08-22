<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('leave_personnel', function (Blueprint $table): void {
            if (!Schema::hasColumn('leave_personnel', 'gmail')) $table->string('gmail')->nullable()->after('email')->index();
        });
    }

    public function down(): void
    {
        Schema::table('leave_personnel', function (Blueprint $table): void {
            if (Schema::hasColumn('leave_personnel', 'gmail')) $table->dropColumn('gmail');
        });
    }
};
