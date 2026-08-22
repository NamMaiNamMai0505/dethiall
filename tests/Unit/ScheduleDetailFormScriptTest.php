<?php

namespace Tests\Unit;

use Tests\TestCase;

class ScheduleDetailFormScriptTest extends TestCase
{
    public function test_period_handlers_ignore_tom_select_wrappers_without_names(): void
    {
        $script = file_get_contents(
            base_path('modules/TrainingSchedule/Views/partials/schedule-detail-form-scripts.blade.php')
        );

        $this->assertIsString($script);
        $this->assertStringContainsString('function periodFromElement(el)', $script);
        $this->assertStringContainsString(
            "form.querySelectorAll('select.subject-select')",
            $script
        );
        $this->assertStringContainsString(
            "form.querySelectorAll('select.lesson-type-select')",
            $script
        );
        $this->assertStringContainsString(
            "const form = document.querySelector('[data-schedule-detail-form]')",
            $script
        );
        $this->assertStringNotContainsString('match(/\d+/)[0]', $script);
    }

    public function test_form_waits_for_bundled_tom_select_instead_of_a_fixed_delay(): void
    {
        $script = file_get_contents(
            base_path('modules/TrainingSchedule/Views/partials/schedule-detail-form-scripts.blade.php')
        );
        $initializer = file_get_contents(
            base_path('resources/views/partials/tom-select-init.blade.php')
        );
        $app = file_get_contents(base_path('resources/js/app.js'));

        $this->assertStringContainsString("import TomSelect from 'tom-select'", $app);
        $this->assertStringContainsString('app:tom-select-ready', $app);
        $this->assertStringContainsString('app:tom-select-ready', $script);
        $this->assertStringContainsString('function hydrateInitialForm()', $script);
        $this->assertStringNotContainsString('cdn.jsdelivr.net/npm/tom-select', $initializer);
        $this->assertStringContainsString('typeof window.TomSelect', $initializer);
    }

    public function test_form_uses_full_reload_and_server_navigation_for_stable_controls(): void
    {
        $form = file_get_contents(
            base_path('modules/TrainingSchedule/Views/schedule-detail-form.blade.php')
        );
        $script = file_get_contents(
            base_path('modules/TrainingSchedule/Views/partials/schedule-detail-form-scripts.blade.php')
        );
        $layout = file_get_contents(base_path('resources/views/layouts/admin.blade.php'));

        $this->assertStringContainsString("@section('turbo-cache-control', 'no-cache')", $form);
        $this->assertStringContainsString("@section('turbo-visit-control', 'reload')", $form);
        $this->assertStringContainsString(
            "route('training-schedules.schedule-details.navigate'",
            $form
        );
        $this->assertStringContainsString('data-schedule-detail-page data-turbo="false"', $form);
        $this->assertStringNotContainsString('function navigateToDate', $script);
        $this->assertStringContainsString("@yield('turbo-visit-control')", $layout);
    }

    public function test_instructor_filter_applies_to_super_admin_not_just_faculty(): void
    {
        $script = file_get_contents(
            base_path('modules/TrainingSchedule/Views/partials/schedule-detail-form-scripts.blade.php')
        );

        // Super Admin phải lọc GV theo phân công môn (subjectsById) giống Khoa —
        // chỉ PDOT (đã loại trừ Super Admin qua rolePdot) và tiết thi mới thấy toàn bộ GV.
        $this->assertStringContainsString('const showAllInstructors = rolePdot || isExam;', $script);
        $this->assertStringNotContainsString('showAllInstructors = isSuperAdmin ||', $script);
        // canManageSkeleton cũng đúng với Super Admin (scope SYSTEM) nên không được dùng trực
        // tiếp trong điều kiện này — phải qua rolePdot (đã loại trừ Super Admin).
        $this->assertStringNotContainsString('showAllInstructors = (canManageSkeleton', $script);
    }
}
