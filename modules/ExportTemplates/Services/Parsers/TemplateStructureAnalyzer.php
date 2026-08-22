<?php

namespace Modules\ExportTemplates\Services\Parsers;

use Modules\ExportTemplates\Exceptions\InvalidTemplateException;

class TemplateStructureAnalyzer
{
    public function __construct(
        private readonly TemplateParserRegistry $registry,
        private readonly OoxmlFileGuard $fileGuard
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function analyze(string $absolutePath, string $extension): array
    {
        $extension = strtolower(trim($extension, ". \t\n\r\0\x0B"));
        $this->fileGuard->assertSafe($absolutePath, $extension);
        $parser = $this->registry->resolve($extension);

        try {
            $manifest = $parser->parse($absolutePath);
        } catch (InvalidTemplateException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new InvalidTemplateException(
                'Không thể phân tích cấu trúc template.',
                [$exception->getMessage()],
                $exception
            );
        }

        $targets = $manifest['targets'] ?? [];
        $references = [];
        $duplicateReferences = [];
        foreach ($targets as $target) {
            $reference = (string) ($target['ref'] ?? '');
            if ($reference === '') {
                continue;
            }
            if (isset($references[$reference])) {
                $duplicateReferences[] = $reference;
            }
            $references[$reference] = true;
        }

        $validation = $manifest['validation'] ?? [];
        $errors = array_values($validation['errors'] ?? []);
        if ($duplicateReferences !== []) {
            $errors[] = 'Target bị trùng: '.implode(', ', array_unique($duplicateReferences));
        }

        $manifest['schema_version'] = 1;
        $manifest['validation'] = [
            'valid' => $errors === [],
            'errors' => $errors,
            'warnings' => array_values($validation['warnings'] ?? []),
        ];
        $manifest['summary'] = array_merge(
            $manifest['summary'] ?? [],
            [
                'target_count' => count($targets),
                'element_count' => count($manifest['elements'] ?? []),
                'placeholder_count' => count($manifest['placeholders'] ?? []),
            ]
        );

        if ($errors !== []) {
            throw new InvalidTemplateException(
                'Template không vượt qua kiểm tra cấu trúc.',
                $errors
            );
        }

        return $manifest;
    }
}
