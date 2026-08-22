<?php

namespace Tests\Feature;

use Tests\TestCase;

class PortalPopupShellRegressionTest extends TestCase
{
    public function test_portal_shells_keep_their_popup_mapping(): void
    {
        $notifications = file_get_contents(resource_path('views/partials/notifications.blade.php')) ?: '';
        $this->assertStringContainsString('window.LmsPopup = window.LmsPopup || createPopupApi(\'lms\')', $notifications);
        $this->assertStringContainsString('window.DashboardPopup = window.DashboardPopup || createPopupApi(\'dashboard\')', $notifications);
        $this->assertStringContainsString("document.body.classList.contains('grades-shell')", $notifications);
        $this->assertStringContainsString("document.body.classList.contains('lms-shell')", $notifications);

        $this->assertStringContainsString("@include('partials.turbo-lms')", file_get_contents(resource_path('views/layouts/lms-learner.blade.php')) ?: '');
        $this->assertStringContainsString("@include('partials.turbo-grades')", file_get_contents(resource_path('views/layouts/grades.blade.php')) ?: '');
        $this->assertStringContainsString("@include('partials.turbo-admin')", file_get_contents(resource_path('views/layouts/admin.blade.php')) ?: '');
    }

    public function test_turbo_shells_boot_on_turbo_load(): void
    {
        foreach (['turbo-admin.blade.php', 'turbo-lms.blade.php', 'turbo-grades.blade.php'] as $file) {
            $source = file_get_contents(resource_path('views/partials/'.$file)) ?: '';
            $this->assertStringContainsString('turbo:load', $source, $file.' must handle Turbo navigation');
        }
    }

    public function test_learner_and_grades_views_use_their_own_shells(): void
    {
        foreach (glob(base_path('modules/Lms/Views/learn/*.blade.php')) ?: [] as $file) {
            $source = file_get_contents($file) ?: '';
            $this->assertStringContainsString("@extends('layouts.lms-learner')", $source, basename($file));
        }

        $gradesFiles = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(base_path('modules/Grades/Views'))
        );
        foreach ($gradesFiles as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php' || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            $source = file_get_contents($file->getPathname()) ?: '';
            if (! str_contains($source, '@extends(')) {
                continue; // print-only document intentionally has no application shell
            }
            $this->assertStringContainsString("@extends('layouts.grades')", $source, $file->getFilename());
        }
    }
}
