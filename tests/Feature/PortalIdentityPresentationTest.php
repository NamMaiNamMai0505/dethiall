<?php

namespace Tests\Feature;

use App\Models\MilitaryRank;
use Tests\TestCase;

class PortalIdentityPresentationTest extends TestCase
{
    public function test_officer_rank_badge_distinguishes_stars_with_vertical_bars(): void
    {
        $colonel = new MilitaryRank([
            'name' => 'Đại tá',
            'group_key' => 'officer_field',
            'group_name' => 'Sĩ quan · Cấp Tá',
            'stars' => 4,
            'bars' => 2,
        ]);
        $general = new MilitaryRank([
            'name' => 'Đại tướng',
            'group_key' => 'officer_general',
            'group_name' => 'Sĩ quan · Cấp Tướng',
            'stars' => 4,
            'bars' => 0,
        ]);

        $this->blade('<x-military-rank-badge :rank="$rank" />', ['rank' => $colonel])
            ->assertSee('★★★★ ||')
            ->assertSee('data-rank-bars="2"', false);

        $this->blade('<x-military-rank-badge :rank="$rank" />', ['rank' => $general])
            ->assertSee('★★★★')
            ->assertSee('data-rank-bars="0"', false)
            ->assertDontSee('★★★★ |');
    }

    public function test_lms_settings_link_is_next_to_profile_and_uses_gear_icon(): void
    {
        $template = file_get_contents(resource_path('views/layouts/lms-learner.blade.php'));
        $profilePosition = strpos($template, 'data-lms-nav="profile"');
        $settingsPosition = strpos($template, 'data-lms-nav="settings"');

        $this->assertNotFalse($profilePosition);
        $this->assertNotFalse($settingsPosition);
        $this->assertLessThan($settingsPosition, $profilePosition);

        $settingsBlock = substr($template, $settingsPosition, 220);
        $this->assertStringContainsString('bi bi-gear', $settingsBlock);
        $this->assertStringContainsString('Cài đặt', $settingsBlock);
    }

    public function test_grades_header_uses_the_same_maximum_width_as_lms(): void
    {
        $lmsTheme = file_get_contents(resource_path('views/partials/lms-theme.blade.php'));
        $gradesTheme = file_get_contents(resource_path('views/partials/grades-theme.blade.php'));

        $this->assertMatchesRegularExpression(
            '/\\.lms-top-inner\\s*\\{[^}]*max-width:\\s*96rem/s',
            $lmsTheme
        );
        $this->assertMatchesRegularExpression(
            '/\\.grades-top-inner\\s*\\{[^}]*max-width:\\s*96rem/s',
            $gradesTheme
        );
    }

    public function test_home_title_uses_a_single_smooth_background_animation(): void
    {
        $template = file_get_contents(base_path('modules/Home/Views/index.blade.php'));

        $this->assertStringContainsString('animation: titleShineRun 5.2s linear infinite', $template);
        $this->assertStringContainsString('will-change: background-position', $template);
        $this->assertStringNotContainsString('titleGlowPulse', $template);
        $this->assertMatchesRegularExpression(
            '/prefers-reduced-motion:\\s*reduce[\\s\\S]*?\\.hero-title-sub\\s*\\{[^}]*animation:\\s*none\\s*!important/s',
            $template
        );
    }
}
