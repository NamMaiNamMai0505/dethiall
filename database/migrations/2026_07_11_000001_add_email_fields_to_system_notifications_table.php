<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_notifications', function (Blueprint $table) {
            if (! Schema::hasColumn('system_notifications', 'type')) {
                $table->string('type', 64)->default('system')->after('action');
            }
            if (! Schema::hasColumn('system_notifications', 'meta')) {
                $table->json('meta')->nullable()->after('url');
            }
            if (! Schema::hasColumn('system_notifications', 'email_sent_at')) {
                $table->timestamp('email_sent_at')->nullable()->after('read_at');
            }
            if (! Schema::hasColumn('system_notifications', 'email_failed_at')) {
                $table->timestamp('email_failed_at')->nullable()->after('email_sent_at');
            }
            if (! Schema::hasColumn('system_notifications', 'email_error')) {
                $table->string('email_error', 500)->nullable()->after('email_failed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('system_notifications', function (Blueprint $table) {
            $cols = array_values(array_filter([
                Schema::hasColumn('system_notifications', 'type') ? 'type' : null,
                Schema::hasColumn('system_notifications', 'meta') ? 'meta' : null,
                Schema::hasColumn('system_notifications', 'email_sent_at') ? 'email_sent_at' : null,
                Schema::hasColumn('system_notifications', 'email_failed_at') ? 'email_failed_at' : null,
                Schema::hasColumn('system_notifications', 'email_error') ? 'email_error' : null,
            ]));

            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};
