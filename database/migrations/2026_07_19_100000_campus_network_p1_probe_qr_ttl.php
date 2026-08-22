<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P1 web-only điểm danh:
 * - QR token TTL + thời điểm hết hạn (rotate)
 * - Ghi nhận kết quả LAN probe trên bản ghi check-in
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lms_attendance_sessions')) {
            Schema::table('lms_attendance_sessions', function (Blueprint $table) {
                if (! Schema::hasColumn('lms_attendance_sessions', 'token_expires_at')) {
                    $table->timestamp('token_expires_at')->nullable()->after('checkin_token');
                }
                if (! Schema::hasColumn('lms_attendance_sessions', 'qr_ttl_minutes')) {
                    $table->unsignedSmallInteger('qr_ttl_minutes')->nullable()->after('token_expires_at');
                }
            });
        }

        if (Schema::hasTable('lms_attendance_records')) {
            Schema::table('lms_attendance_records', function (Blueprint $table) {
                if (! Schema::hasColumn('lms_attendance_records', 'probe_ok')) {
                    $table->boolean('probe_ok')->nullable()->after('network_note');
                }
                if (! Schema::hasColumn('lms_attendance_records', 'probe_note')) {
                    $table->string('probe_note', 255)->nullable()->after('probe_ok');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('lms_attendance_records')) {
            Schema::table('lms_attendance_records', function (Blueprint $table) {
                foreach (['probe_ok', 'probe_note'] as $col) {
                    if (Schema::hasColumn('lms_attendance_records', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('lms_attendance_sessions')) {
            Schema::table('lms_attendance_sessions', function (Blueprint $table) {
                foreach (['token_expires_at', 'qr_ttl_minutes'] as $col) {
                    if (Schema::hasColumn('lms_attendance_sessions', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
