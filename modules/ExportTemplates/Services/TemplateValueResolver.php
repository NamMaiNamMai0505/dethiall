<?php

namespace Modules\ExportTemplates\Services;

class TemplateValueResolver
{
    public function resolve(array $data, string $dataKey, ?array $item = null): mixed
    {
        if ($item !== null && str_contains($dataKey, '[]')) {
            $suffix = trim((string) substr($dataKey, strpos($dataKey, '[]') + 2), '.');

            return $suffix === '' ? $item : $this->get($item, $suffix);
        }

        if (str_contains($dataKey, '[]')) {
            [$root, $suffix] = array_pad(explode('[]', $dataKey, 2), 2, '');
            $items = $this->get($data, trim($root, '.'));
            if (! is_array($items)) {
                return null;
            }
            $suffix = trim($suffix, '.');
            if ($suffix === '') {
                return $items;
            }

            return array_map(
                fn (mixed $row): mixed => is_array($row) ? $this->get($row, $suffix) : null,
                $items
            );
        }

        return $this->get($data, $dataKey);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function collection(array $data, string $dataKey): array
    {
        $value = $this->resolve($data, $dataKey);

        return is_array($value)
            ? array_values(array_filter($value, 'is_array'))
            : [];
    }

    private function get(array $data, string $path): mixed
    {
        if ($path === '') {
            return $data;
        }

        $cursor = $data;
        foreach (explode('.', $path) as $segment) {
            if (! is_array($cursor) || ! array_key_exists($segment, $cursor)) {
                return null;
            }
            $cursor = $cursor[$segment];
        }

        return $cursor;
    }
}
