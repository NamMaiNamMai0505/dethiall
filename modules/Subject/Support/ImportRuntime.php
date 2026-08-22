<?php

namespace Modules\Subject\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Applies predictable runtime limits and records fatal import failures.
 *
 * Fatal errors such as OOM/timeout bypass controller try/catch. The shutdown
 * hook keeps the import ID and resource usage visible in the container log.
 */
class ImportRuntime
{
    private const MAX_EXECUTION_SECONDS = 600;

    private const MEMORY_LIMIT = '1024M';

    private static ?array $activeImport = null;

    private static bool $shutdownRegistered = false;

    public static function resolveId(?string $candidate = null): string
    {
        $candidate = trim((string) $candidate);

        return Str::isUuid($candidate) ? $candidate : (string) Str::uuid();
    }

    public static function safeExceptionMessage(\Throwable $exception): string
    {
        $message = mb_convert_encoding($exception->getMessage(), 'UTF-8', 'UTF-8');
        $message = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', ' ', $message) ?? '';
        $message = trim(preg_replace('/\s+/u', ' ', $message) ?? $message);

        return Str::limit($message !== '' ? $message : 'Lỗi xử lý dữ liệu không xác định.', 800);
    }

    public static function jsonResponse(
        array $payload,
        int $status,
        string $importId
    ): JsonResponse {
        $payload['import_id'] = $importId;

        return response()
            ->json(
                $payload,
                $status,
                [],
                JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
            )
            ->header('X-Import-ID', $importId);
    }

    public static function begin(string $importId, array $context = []): void
    {
        @set_time_limit(self::MAX_EXECUTION_SECONDS);
        self::raiseMemoryLimitIfNeeded(self::MEMORY_LIMIT);

        self::$activeImport = [
            ...$context,
            'import_id' => $importId,
            'started_at' => microtime(true),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
        ];

        if (! self::$shutdownRegistered) {
            register_shutdown_function([self::class, 'handleShutdown']);
            self::$shutdownRegistered = true;
        }

        try {
            Log::info('Subject import started.', self::publicContext(self::$activeImport));
        } catch (\Throwable) {
            // Diagnostics must never prevent an import from starting.
        }
    }

    public static function finish(bool $successful, ?\Throwable $exception = null): void
    {
        if (self::$activeImport === null) {
            return;
        }

        $context = self::publicContext(self::$activeImport) + [
            'successful' => $successful,
            'duration_ms' => (int) round((microtime(true) - self::$activeImport['started_at']) * 1000),
            'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        ];

        if ($exception) {
            $context['exception_class'] = $exception::class;
            $context['exception_message'] = $exception->getMessage();
        }

        try {
            Log::info('Subject import finished.', $context);
        } catch (\Throwable) {
            // Diagnostics must never replace the actual import response.
        }
        self::$activeImport = null;
    }

    public static function handleShutdown(): void
    {
        if (self::$activeImport === null) {
            return;
        }

        $error = error_get_last();
        $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];

        if (! $error || ! in_array($error['type'] ?? null, $fatalTypes, true)) {
            return;
        }

        try {
            Log::critical('Subject import terminated fatally.', self::publicContext(self::$activeImport) + [
                'fatal_type' => $error['type'] ?? null,
                'fatal_message' => $error['message'] ?? 'Unknown fatal error',
                'fatal_file' => $error['file'] ?? null,
                'fatal_line' => $error['line'] ?? null,
                'duration_ms' => (int) round((microtime(true) - self::$activeImport['started_at']) * 1000),
                'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            ]);
        } catch (\Throwable) {
            // The process is already terminating; nothing else can be recovered.
        }
    }

    private static function raiseMemoryLimitIfNeeded(string $target): void
    {
        $current = (string) ini_get('memory_limit');
        $currentBytes = self::toBytes($current);
        $targetBytes = self::toBytes($target);

        if ($currentBytes !== -1 && $currentBytes < $targetBytes) {
            @ini_set('memory_limit', $target);
        }
    }

    private static function toBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '-1') {
            return -1;
        }

        $number = (float) $value;
        $unit = strtolower(substr($value, -1));

        return (int) match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }

    private static function publicContext(array $context): array
    {
        unset($context['started_at']);

        return $context;
    }
}
