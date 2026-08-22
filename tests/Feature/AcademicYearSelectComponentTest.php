<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class AcademicYearSelectComponentTest extends TestCase
{
    public function test_component_renders_options_and_preserves_selected_value(): void
    {
        $html = Blade::render(
            '<x-academic-year-select name="school_year" selected="2099-2100" />'
        );

        $this->assertStringContainsString('name="school_year"', $html);
        $this->assertStringContainsString(
            '<option value="2099-2100" selected>2099-2100</option>',
            $html
        );
    }
}
