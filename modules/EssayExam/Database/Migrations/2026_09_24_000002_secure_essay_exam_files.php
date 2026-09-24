<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

return new class extends Migration {
    public function up(): void
    {
        $public = Storage::disk('public');
        $private = Storage::disk('local');

        foreach (['imports', 'signed-pdfs', 'approval-documents', 'signatures'] as $folder) {
            foreach ($public->allFiles('essay-exam/'.$folder) as $path) {
                if (! $private->exists($path)) {
                    $stream = $public->readStream($path);
                    if (! $stream) throw new \RuntimeException('Cannot read essay exam file: '.$path);
                    try {
                        if (! $private->writeStream($path, $stream)) {
                            throw new \RuntimeException('Cannot secure essay exam file: '.$path);
                        }
                    } finally {
                        fclose($stream);
                    }
                }
                if ($private->size($path) !== $public->size($path)) {
                    throw new \RuntimeException('Essay exam file size differs after copy: '.$path);
                }
                $public->delete($path);
            }
        }
    }

    public function down(): void
    {
        // Archived exam files must not be returned to a public disk.
    }
};
