<?php

namespace Tests\Unit;

use Tests\TestCase;

class TrainingScheduleSprint23ExportUiTest extends TestCase
{
    public function test_export_tab_keeps_locked_actions_visible_and_only_enables_lhl_word(): void
    {
        $view = file_get_contents(
            base_path('modules/TrainingSchedule/Views/calendar.blade.php')
        );

        $this->assertIsString($view);
        $this->assertStringContainsString('id="tabContentExport"', $view);
        $this->assertStringContainsString('id="submitExport"', $view);
        $this->assertStringContainsString('id="submitExportLhl"', $view);
        $this->assertStringContainsString('id="submitExportLhlWord"', $view);
        $this->assertStringContainsString('id="submitExportFaculty"', $view);
        $this->assertSame(2, substr_count($view, 'data-development-lock="1"'));
        $this->assertStringContainsString('Đang phát triển thêm — chức năng chưa được mở sử dụng', $view);
        $this->assertStringContainsString("wordButton.dataset.canExport !== '1'", $view);
        $this->assertStringContainsString('if (excelButton) excelButton.disabled = true;', $view);
        $this->assertStringContainsString("document.getElementById('submitExport').disabled = true;", $view);
    }
}
