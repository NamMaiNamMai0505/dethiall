<?php

namespace Tests\Feature;

use Tests\TestCase;

class NativeDialogRegressionTest extends TestCase
{
    public function test_application_code_does_not_call_browser_native_dialogs(): void
    {
        $root = base_path();
        $directories = ['app', 'modules', 'resources', 'routes'];
        $violations = [];

        foreach ($directories as $directory) {
            $path = $root.DIRECTORY_SEPARATOR.$directory;
            if (! is_dir($path)) {
                continue;
            }
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
            foreach ($iterator as $file) {
                if (! $file->isFile() || ! in_array(strtolower($file->getExtension()), ['php', 'js', 'ts'], true)) {
                    continue;
                }
                // Notifications component intentionally exposes its own alert()/confirm()
                // popup API; those methods are not browser-native calls.
                if ($file->getFilename() === 'notifications.blade.php') {
                    continue;
                }
                $contents = file_get_contents($file->getPathname()) ?: '';
                $pattern = in_array(strtolower($file->getExtension()), ['js', 'ts'], true)
                    ? '/(?:window|globalThis)\.(?:alert|confirm|prompt)\s*\(|(?<![\\w.])(?:alert|confirm|prompt)\s*\(/i'
                    : '/(?:window|globalThis)\.(?:alert|confirm|prompt)\s*\(/i';
                if (preg_match($pattern, $contents, $match, PREG_OFFSET_CAPTURE)) {
                    $line = substr_count(substr($contents, 0, $match[0][1]), "\n") + 1;
                    $violations[] = $file->getPathname().':'.$line;
                }
            }
        }

        $this->assertSame([], $violations, 'Native browser dialog call detected: '.implode(', ', $violations));
    }
}
