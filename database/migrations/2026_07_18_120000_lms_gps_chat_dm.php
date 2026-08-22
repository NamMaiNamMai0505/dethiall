<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GPS check-in fields + chat DM (recipient) for LMS learner portal.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lms_attendance_records')) {
            Schema::table('lms_attendance_records', function (Blueprint $table) {
                if (! Schema::hasColumn('lms_attendance_records', 'checkin_lat')) {
                    $table->decimal('checkin_lat', 10, 7)->nullable()->after('method');
                }
                if (! Schema::hasColumn('lms_attendance_records', 'checkin_lng')) {
                    $table->decimal('checkin_lng', 10, 7)->nullable()->after('checkin_lat');
                }
                if (! Schema::hasColumn('lms_attendance_records', 'checkin_accuracy_m')) {
                    $table->unsignedInteger('checkin_accuracy_m')->nullable()->after('checkin_lng');
                }
                if (! Schema::hasColumn('lms_attendance_records', 'distance_m')) {
                    $table->unsignedInteger('distance_m')->nullable()->after('checkin_accuracy_m');
                }
            });
        }

        if (Schema::hasTable('lms_chat_messages')) {
            Schema::table('lms_chat_messages', function (Blueprint $table) {
                if (! Schema::hasColumn('lms_chat_messages', 'recipient_user_id')) {
                    $table->foreignId('recipient_user_id')
                        ->nullable()
                        ->after('user_id')
                        ->constrained('users')
                        ->nullOnDelete();
                    $table->index(['lms_course_id', 'recipient_user_id', 'id'], 'lms_chat_course_recipient_idx');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('lms_chat_messages') && Schema::hasColumn('lms_chat_messages', 'recipient_user_id')) {
            Schema::table('lms_chat_messages', function (Blueprint $table) {
                $table->dropConstrainedForeignId('recipient_user_id');
            });
        }
        if (Schema::hasTable('lms_attendance_records')) {
            Schema::table('lms_attendance_records', function (Blueprint $table) {
                foreach (['checkin_lat', 'checkin_lng', 'checkin_accuracy_m', 'distance_m'] as $col) {
                    if (Schema::hasColumn('lms_attendance_records', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
