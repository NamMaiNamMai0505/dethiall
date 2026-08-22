<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table): void {
            if (!Schema::hasColumn('leave_requests', 'bgh_signed_at')) $table->timestamp('bgh_signed_at')->nullable();
            if (!Schema::hasColumn('leave_requests', 'bgh_signed_by_user_id')) $table->foreignId('bgh_signed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            if (!Schema::hasColumn('leave_requests', 'bgh_note')) $table->text('bgh_note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table): void {
            if (Schema::hasColumn('leave_requests', 'bgh_signed_by_user_id')) $table->dropForeign(['bgh_signed_by_user_id']);
            $columns = array_values(array_filter(['bgh_signed_at','bgh_signed_by_user_id','bgh_note'], fn($column)=>Schema::hasColumn('leave_requests',$column)));
            if ($columns) $table->dropColumn($columns);
        });
    }
};
