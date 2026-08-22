<?php

namespace Modules\ExportTemplates\Services;

use Illuminate\Support\Str;

/** Chuẩn hóa schema provider thành danh sách biến có thể chọn trong Builder. */
class TemplateVariableCatalog
{
    public function __construct(private readonly TemplateDataRegistry $registry) {}

    /** @return list<array{key:string,label:string,group:string,type:string,description:string,example:mixed,collection:bool}> */
    public function forFeature(string $featureKey): array
    {
        $provider = $this->registry->get($featureKey);
        $mock = $provider->mockData();
        $variables = [];

        foreach (($provider->schema()['groups'] ?? []) as $group) {
            $groupLabel = (string) ($group['label'] ?? $group['key'] ?? 'Dữ liệu');
            // Provider chuẩn hiện dùng `children`; vẫn đọc `fields` để tương
            // thích với manifest cũ đã được lưu trước khi có Builder.
            $fields = $group['children'] ?? $group['fields'] ?? [];
            foreach (is_array($fields) ? $fields : [] as $field) {
                $key = (string) ($field['key'] ?? '');
                if ($key === '') {
                    continue;
                }
                $isCollection = str_contains($key, '[]');
                $lookup = str_replace('[]', '', $key);
                $example = data_get($mock, $lookup);
                if ($isCollection) {
                    $collectionPath = Str::before($key, '[]');
                    $first = data_get($mock, $collectionPath.'.0');
                    $example = $first === null ? null : data_get($first, Str::after($key, '[].'));
                }
                $variables[] = [
                    'key' => $key,
                    'label' => (string) ($field['label'] ?? $key),
                    'group' => $groupLabel,
                    'type' => (string) ($field['type'] ?? 'string'),
                    'description' => (string) ($field['description']
                        ?? ('Xuất ra '.mb_strtolower((string) ($field['label'] ?? $key))
                            .' thuộc nhóm '.$groupLabel.'.')),
                    'example' => $example,
                    'collection' => $isCollection,
                ];
            }
        }

        return $variables;
    }
}
