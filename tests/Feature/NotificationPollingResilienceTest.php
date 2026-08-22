<?php

namespace Tests\Feature;

use Tests\TestCase;

class NotificationPollingResilienceTest extends TestCase
{
    public function test_notification_polling_consolidates_requests_and_backs_off_on_waf_468(): void
    {
        $script = file_get_contents(
            base_path('resources/views/partials/notification-center.blade.php')
        );
        $controller = file_get_contents(
            base_path('app/Http/Controllers/SystemNotificationController.php')
        );

        $this->assertStringContainsString('const WAF_BLOCK_STATUS = 468', $script);
        $this->assertStringContainsString('const POLL_INTERVAL_MS = 60000', $script);
        $this->assertStringContainsString('blockedUntil = Date.now() + WAF_BACKOFF_MS', $script);
        $this->assertStringContainsString(
            'const payload = await fetchNotifications({ since: lastSeenId > 0 })',
            $script
        );
        $this->assertStringNotContainsString('await fetchUnreadCount()', $script);
        $this->assertStringContainsString("'count' => \$this->unreadCountFor(\$userId)", $controller);
        $this->assertStringContainsString("'count' => \$this->unreadCountFor(\$request->user()->id)", $controller);
    }

    public function test_panel_shows_a_visible_message_instead_of_staying_blank_when_waf_blocks_it(): void
    {
        $script = file_get_contents(
            base_path('resources/views/partials/notification-center.blade.php')
        );

        // Truoc day refreshPanel() im lang return khi bi WAF chan (468), khien panel
        // trong khong ro ly do trong suot WAF_BACKOFF_MS (5 phut). Phai render mot
        // trang thai ro rang thay vi de trong.
        $this->assertStringContainsString('function renderBlocked()', $script);
        $this->assertStringContainsString(
            "if (payload?.blocked) {\n            renderBlocked();\n            return;\n        }",
            $script
        );
    }
}
