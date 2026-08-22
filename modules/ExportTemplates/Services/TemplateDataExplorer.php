<?php

namespace Modules\ExportTemplates\Services;

class TemplateDataExplorer
{
    public function __construct(
        private readonly TemplateDataSecurityPolicy $security
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function groups(array $schema, ?string $search = null): array
    {
        $groups = [];
        $needle = mb_strtolower(trim((string) $search));

        foreach ($schema['groups'] ?? [] as $group) {
            $nodes = $this->flattenNodes($group['children'] ?? []);
            if (($group['type'] ?? null) === 'collection' && ! empty($group['key'])) {
                array_unshift($nodes, [
                    'key' => $group['key'],
                    'label' => 'Toàn bộ '.$group['label'],
                    'type' => 'collection',
                    'bindable' => true,
                    'description' => 'Binding lặp cho toàn bộ danh sách.',
                ]);
            }

            $children = array_values(array_filter(
                $nodes,
                function (array $field) use ($needle) {
                    if (! $this->security->isAllowed((string) ($field['key'] ?? ''))) {
                        return false;
                    }

                    if ($needle === '') {
                        return true;
                    }

                    $haystack = mb_strtolower(implode(' ', [
                        (string) ($field['key'] ?? ''),
                        (string) ($field['label'] ?? ''),
                        (string) ($field['description'] ?? ''),
                    ]));

                    return str_contains($haystack, $needle);
                }
            ));

            if ($children !== []) {
                $copy = $group;
                $copy['fields'] = $children;
                unset($copy['children']);
                $groups[] = $copy;
            }
        }

        return $groups;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function fields(array $schema, ?string $search = null): array
    {
        return array_values(array_merge(
            ...array_map(
                static fn (array $group): array => $group['fields'],
                $this->groups($schema, $search)
            )
        ));
    }

    public function find(array $schema, string $dataKey): ?array
    {
        foreach ($this->fields($schema) as $field) {
            if (($field['key'] ?? null) === $dataKey) {
                return $field;
            }
        }

        return null;
    }

    public function value(array $data, string $dataKey): mixed
    {
        $segments = explode('.', $dataKey);
        $cursor = $data;

        foreach ($segments as $segment) {
            $collection = str_ends_with($segment, '[]');
            $segment = str_replace('[]', '', $segment);

            if (! is_array($cursor) || ! array_key_exists($segment, $cursor)) {
                return null;
            }

            $cursor = $cursor[$segment];
            if ($collection) {
                if (! is_array($cursor) || $cursor === []) {
                    return null;
                }
                $cursor = reset($cursor);
            }
        }

        return $cursor;
    }

    /**
     * @return list<string>
     */
    public function validateDataShape(array $schema, array $data): array
    {
        $errors = [];

        foreach ($schema['groups'] ?? [] as $group) {
            $root = (string) ($group['key'] ?? '');
            if ($root === '' || ! array_key_exists($root, $data)) {
                $errors[] = "Thiếu nhóm dữ liệu [{$root}].";

                continue;
            }

            $expected = (string) ($group['type'] ?? 'object');
            if ($expected === 'collection' && ! is_array($data[$root])) {
                $errors[] = "Nhóm [{$root}] phải là danh sách.";
            }
            if ($expected === 'object' && ! is_array($data[$root])) {
                $errors[] = "Nhóm [{$root}] phải là object.";
            }
        }

        foreach ($this->fields($schema) as $field) {
            if (($field['bindable'] ?? true) === false) {
                continue;
            }

            $path = (string) $field['key'];
            if (! $this->pathExists($data, $path)) {
                $errors[] = "Dữ liệu không khớp schema tại [{$path}].";
            }
        }

        return array_values(array_unique($errors));
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     * @return list<array<string, mixed>>
     */
    private function flattenNodes(array $nodes): array
    {
        $result = [];

        foreach ($nodes as $node) {
            if (! empty($node['children'])) {
                $result = array_merge($result, $this->flattenNodes($node['children']));

                continue;
            }

            if (! empty($node['key'])) {
                $result[] = $node;
            }
        }

        return $result;
    }

    private function pathExists(array $data, string $path): bool
    {
        $segments = explode('.', str_replace('[]', '', $path));
        $cursor = $data;

        foreach ($segments as $index => $segment) {
            if (! is_array($cursor) || ! array_key_exists($segment, $cursor)) {
                return false;
            }

            $cursor = $cursor[$segment];
            if (str_contains($path, '[]') && $index === 0) {
                if (! is_array($cursor)) {
                    return false;
                }
                if ($cursor === []) {
                    return true;
                }
                $cursor = reset($cursor);
            }
        }

        return true;
    }
}
