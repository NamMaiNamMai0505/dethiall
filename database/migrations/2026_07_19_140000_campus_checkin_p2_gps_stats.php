<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P2 web-only điểm danh:
 * - Session: require_gps (hard) + allow_gps_bypass (GPS trong bán kính cứu mạng fail)
 * - Record: gps_ok
 * - Event log: mọi attempt (ok/fail) để thống kê
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lms_attendance_sessions')) {
            Schema::table('lms_attendance_sessions', function (Blueprint $table) {
                if (! Schema::hasColumn('lms_attendance_sessions', 'require_gps')) {
                    $table->boolean('require_gps')->default(false)->after('require_campus_wifi');
                }
                if (! Schema::hasColumn('lms_attendance_sessions', 'allow_gps_bypass')) {
                    $table->boolean('allow_gps_bypass')->default(false)->after('require_gps');
                }
            });
        }

        if (Schema::hasTable('lms_attendance_records')) {
            Schema::table('lms_attendance_records', function (Blueprint $table) {
                if (! Schema::hasColumn('lms_attendance_records', 'gps_ok')) {
                    $table->boolean('gps_ok')->nullable()->after('probe_note');
                }
            });
        }

        if (! Schema::hasTable('lms_checkin_events')) {
            Schema::create('lms_checkin_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lms_course_id')->nullable()->constrained('lms_courses')->nullOnDelete();
                $table->foreignId('lms_attendance_session_id')->nullable()
                    ->constrained('lms_attendance_sessions')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->boolean('ok')->default(false);
                $table->string('reason', 64)->nullable()->index(); // ok, token, expired, network, probe, gps, closed, manual_only
                $table->string('client_ip', 45)->nullable();
                $table->boolean('network_ok')->nullable();
                $table->boolean('probe_ok')->nullable();
                $table->boolean('gps_ok')->nullable();
                $table->unsignedInteger('distance_m')->nullable();
                $table->decimal('lat', 10, 7)->nullable();
                $table->decimal('lng', 10, 7)->nullable();
                $table->string('note', 500)->nullable();
                $table->timestamps();

                $table->index(['created_at']);
                $table->index(['ok', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lms_checkin_events');

        if (Schema::hasTable('lms_attendance_records') && Schema::hasColumn('lms_attendance_records', 'gps_ok')) {
            Schema::table('lms_attendance_records', function (Blueprint $table) {
                $table->dropColumn('gps_ok');
            });
        }

        if (Schema::hasTable('lms_attendance_sessions')) {
            Schema::table('lms_attendance_sessions', function (Blueprint $table) {
                foreach (['require_gps', 'allow_gps_bypass'] as $col) {
                    if (Schema::hasColumn('lms_attendance_sessions', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
