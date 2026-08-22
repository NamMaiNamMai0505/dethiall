<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cấu hình Wi‑Fi / mạng trường cho điểm danh QR + lưu IP lúc check-in.
 *
 * Lưu ý kỹ thuật (web): trình duyệt KHÔNG đọc được MAC/BSSID Wi‑Fi thiết bị
 * (bảo mật). Hệ thống lưu MAC AP của trường để quản trị cập nhật khi đổi router,
 * và xác minh thực tế bằng dải IP trường (+ tuỳ chọn probe URL nội bộ).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('campus_network_settings')) {
            Schema::create('campus_network_settings', function (Blueprint $table) {
                $table->id();
                $table->string('name')->default('Wi‑Fi trường');
                // MAC / BSSID access point (dạng AA:BB:CC:DD:EE:FF), có thể nhiều bản ghi
                $table->string('wifi_mac', 32)->nullable()->index();
                // Dải IP/CIDR, phân tách bằng dấu phẩy: 10.0.0.0/8,172.16.0.0/12
                $table->text('ip_cidrs')->nullable();
                // URL chỉ resolve trong LAN trường (optional probe)
                $table->string('probe_url')->nullable();
                $table->boolean('require_campus_network')->default(true);
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->text('note')->nullable();
                $table->timestamps();
            });

            DB::table('campus_network_settings')->insert([
                'name' => 'Wi‑Fi chính CDHC2',
                'wifi_mac' => null,
                'ip_cidrs' => '10.0.0.0/8,172.16.0.0/12,192.168.0.0/16',
                'probe_url' => null,
                'require_campus_network' => 1,
                'is_active' => 1,
                'sort_order' => 1,
                'note' => 'Cập nhật wifi_mac (BSSID AP) khi đổi router. Web xác minh chủ yếu bằng IP CIDR.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (Schema::hasTable('lms_attendance_records')) {
            Schema::table('lms_attendance_records', function (Blueprint $table) {
                if (! Schema::hasColumn('lms_attendance_records', 'client_ip')) {
                    $table->string('client_ip', 45)->nullable()->after('method');
                }
                if (! Schema::hasColumn('lms_attendance_records', 'network_ok')) {
                    $table->boolean('network_ok')->nullable()->after('client_ip');
                }
                if (! Schema::hasColumn('lms_attendance_records', 'network_note')) {
                    $table->string('network_note', 255)->nullable()->after('network_ok');
                }
            });
        }

        if (Schema::hasTable('lms_attendance_sessions')) {
            Schema::table('lms_attendance_sessions', function (Blueprint $table) {
                if (! Schema::hasColumn('lms_attendance_sessions', 'require_campus_wifi')) {
                    $table->boolean('require_campus_wifi')->default(false)->after('mode');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('lms_attendance_sessions') && Schema::hasColumn('lms_attendance_sessions', 'require_campus_wifi')) {
            Schema::table('lms_attendance_sessions', function (Blueprint $table) {
                $table->dropColumn('require_campus_wifi');
            });
        }
        if (Schema::hasTable('lms_attendance_records')) {
            Schema::table('lms_attendance_records', function (Blueprint $table) {
                foreach (['client_ip', 'network_ok', 'network_note'] as $col) {
                    if (Schema::hasColumn('lms_attendance_records', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
        Schema::dropIfExists('campus_network_settings');
    }
};
