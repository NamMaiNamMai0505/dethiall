<?php

namespace Tests\Unit;

use Tests\TestCase;

class TrainingScheduleSprint20UiTest extends TestCase
{
    public function test_training_schedule_filters_submit_and_reset_to_the_list_route(): void
    {
        $view = file_get_contents(
            base_path('modules/TrainingSchedule/Views/index.blade.php')
        );

        $this->assertIsString($view);
        $this->assertStringContainsString(
            '<form action="{{ route(\'training-schedules.list\') }}" method="GET" id="filter-form">',
            $view
        );
        $this->assertGreaterThanOrEqual(
            2,
            substr_count($view, "route('training-schedules.list')")
        );
        $this->assertStringNotContainsString(
            '<form action="{{ route(\'training-schedules.index\') }}"',
            $view
        );
    }

    public function test_tom_select_dropdown_expands_without_resizing_its_control(): void
    {
        $initializer = file_get_contents(
            base_path('resources/views/partials/tom-select-init.blade.php')
        );

        $this->assertIsString($initializer);
        $this->assertStringContainsString('function measureDropdownContent(tom)', $initializer);
        $this->assertStringContainsString('function applyAdaptiveDropdownGeometry(tom)', $initializer);
        $this->assertStringContainsString("input.dataset.dropdownWidth === 'control'", $initializer);
        $this->assertStringContainsString("window.addEventListener('resize', repositionOpenDropdowns", $initializer);
        $this->assertStringContainsString("document.addEventListener('scroll', repositionOpenDropdowns", $initializer);
        $this->assertStringContainsString('function resetDropdownGeometry(dropdown)', $initializer);
        $this->assertStringContainsString('overflow-wrap: anywhere', $initializer);
    }

    public function test_lms_uses_the_shared_tom_select_lifecycle(): void
    {
        $layout = file_get_contents(
            base_path('resources/views/layouts/lms-learner.blade.php')
        );

        $this->assertIsString($layout);
        $this->assertStringContainsString("@include('partials.tom-select-init')", $layout);
        $this->assertStringContainsString("typeof window.initTomSelects === 'function'", $layout);
        $this->assertStringNotContainsString('tom-select.complete.min.js', $layout);
        $this->assertStringNotContainsString("dropdownParent: 'body'", $layout);
    }
}
