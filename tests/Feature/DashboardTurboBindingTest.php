<?php

namespace Tests\Feature;

use Tests\TestCase;

class DashboardTurboBindingTest extends TestCase
{
    public function test_dashboard_sections_and_filters_are_turbo_safe_without_navigation_reload(): void
    {
        $dashboard = file_get_contents(base_path('modules/Dashboard/Views/index.blade.php'));
        $classStatistics = file_get_contents(
            base_path('modules/Dashboard/Views/dashboard_stat_class.blade.php')
        );
        $instructorStatistics = file_get_contents(
            base_path('modules/Dashboard/Views/dashboard_stat_instructor.blade.php')
        );

        $this->assertStringContainsString('window.DashboardSections', $dashboard);
        $this->assertStringContainsString("const controllerVersion = '2026-07-30.4'", $dashboard);
        $this->assertStringContainsString("document.addEventListener('change'", $dashboard);
        $this->assertStringContainsString("document.addEventListener('turbo:load', boot)", $dashboard);
        $this->assertStringContainsString('const autoSubmittedForms = new WeakSet()', $dashboard);
        $this->assertStringContainsString('data-dashboard-section-control', $dashboard);
        $this->assertStringContainsString('type="radio"', $dashboard);
        $this->assertStringContainsString('#dashboardSectionControlOverview:checked', $dashboard);
        $this->assertStringContainsString('data-dashboard-section-trigger="stat_class"', $dashboard);
        $this->assertStringContainsString('$initialDashboardSection === \'lms\'', $dashboard);
        $this->assertStringContainsString("url.searchParams.set('section', safeSection)", $dashboard);
        $this->assertStringNotContainsString("href=\"{{ route('dashboard', ['tab'", $dashboard);
        $this->assertStringNotContainsString('page.dataset.bound', $dashboard);
        $this->assertStringNotContainsString('dataset.autoLoaded', $dashboard);

        $this->assertStringContainsString('new WeakSet()', $classStatistics);
        $this->assertStringContainsString('boundForms.has(form)', $classStatistics);
        $this->assertStringNotContainsString('form.dataset.bound', $classStatistics);

        $this->assertStringContainsString('new WeakSet()', $instructorStatistics);
        $this->assertStringContainsString('boundForms.has(form)', $instructorStatistics);
        $this->assertStringNotContainsString('form.dataset.bound', $instructorStatistics);

        $this->assertStringContainsString('dashboard-stat-view--overview', file_get_contents(
            base_path('modules/Dashboard/Views/dashboard_overview.blade.php')
        ));
        $this->assertStringContainsString('dashboard-stat-view--class', $classStatistics);
        $this->assertStringContainsString('dashboard-stat-view--instructor', $instructorStatistics);
        $this->assertStringContainsString('.dashboard-stat-kpi', $dashboard);
        $this->assertStringContainsString('.dashboard-stat-chart-card', $dashboard);
        $this->assertStringContainsString('.dashboard-stat-table', $dashboard);
    }

    public function test_standard_hours_conversion_preview_is_scoped_to_its_current_turbo_form(): void
    {
        $form = file_get_contents(
            base_path('modules/StandardHours/Views/conversion-records/_form.blade.php')
        );
        $create = file_get_contents(
            base_path('modules/StandardHours/Views/conversion-records/create.blade.php')
        );

        $this->assertStringContainsString('data-conversion-record-form', $create);
        $this->assertStringContainsString('new WeakSet()', $form);
        $this->assertStringContainsString('if (!form || boundForms.has(form)) return;', $form);
        $this->assertStringContainsString('if (!form?.isConnected) return;', $form);
        $this->assertStringContainsString('if (!preview || !hoursEl || !formulaEl', $form);
        $this->assertStringNotContainsString(
            "window.onTomChange('conversion_category_id', updateConversionPreview)",
            $form
        );

        $retrieveRequest = file_get_contents(
            base_path('modules/StandardHours/Requests/RetrieveTeachingScheduleRequest.php')
        );
        $calculationService = file_get_contents(
            base_path('modules/StandardHours/Services/CalculationService.php')
        );
        $filterForm = file_get_contents(
            base_path('resources/views/components/filter-form.blade.php')
        );

        $this->assertStringNotContainsString('rangeBelongsToPeriod', $retrieveRequest);
        $this->assertStringContainsString('whereBetween(\'activity_date\'', $calculationService);
        $this->assertStringContainsString('Không lọc theo cột year', $calculationService);
        $this->assertStringContainsString("@elseif(\$filter['type'] === 'date')", $filterForm);
    }
}
