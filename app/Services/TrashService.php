<?php

namespace App\Services;

use App\Models\TrashLog;
use App\Support\TrashRegistry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class TrashService
{
    /**
     * List soft-deleted items across registered modules (no auto-expiry).
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $items = $this->collect($filters)
            ->sortByDesc(fn (array $row) => $row['deleted_at']?->timestamp ?? 0)
            ->values();

        $page = max(1, (int) ($filters['page'] ?? request('page', 1)));
        $total = $items->count();
        $slice = $items->forPage($page, $perPage)->values();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function collect(array $filters = []): Collection
    {
        $module = $filters['module'] ?? null;
        $search = trim((string) ($filters['search'] ?? ''));
        $definitions = TrashRegistry::definitions();

        if ($module && isset($definitions[$module])) {
            $definitions = [$module => $definitions[$module]];
        }

        $rows = collect();
        $hasTrashLog = $this->hasTrashLogTable();

        foreach ($definitions as $key => $def) {
            try {
                /** @var class-string<Model> $modelClass */
                $modelClass = $def['model'];
                if (! class_exists($modelClass) || ! TrashRegistry::usesSoftDeletes($modelClass)) {
                    continue;
                }

                // Bảng model chưa migrate → bỏ qua, không 500
                $table = (new $modelClass)->getTable();
                if (! Schema::hasTable($table)) {
                    continue;
                }

                $trashed = $modelClass::onlyTrashed()
                    ->orderByDesc('deleted_at')
                    ->get();

                foreach ($trashed as $model) {
                    // Same Eloquent class can map to multiple trash modules (e.g. User → students|users)
                    if (isset($def['match']) && is_callable($def['match']) && ! ($def['match'])($model)) {
                        continue;
                    }

                    $log = null;
                    if ($hasTrashLog) {
                        $log = TrashLog::query()
                            ->with('deleter')
                            ->where('model_type', $modelClass)
                            ->where('model_id', $model->getKey())
                            ->whereNull('restored_at')
                            ->orderByDesc('deleted_at')
                            ->first();
                    }

                    $title = $log?->title ?: (string) ($def['title'])($model);
                    $identifier = $log?->identifier ?: $def['identifier']($model);
                    $summary = $log?->summary ?: $def['summary']($model);

                    $haystack = mb_strtolower(implode(' ', array_filter([
                        $title,
                        $identifier,
                        $def['label'],
                        is_array($summary) ? implode(' ', $summary) : '',
                    ])));

                    if ($search !== '' && ! str_contains($haystack, mb_strtolower($search))) {
                        continue;
                    }

                    $rows->push([
                        'module_key' => $key,
                        'type_label' => $def['label'],
                        'icon' => $def['icon'],
                        'model_type' => $modelClass,
                        'model_id' => $model->getKey(),
                        'title' => $title,
                        'identifier' => $identifier,
                        'summary' => $summary,
                        'deleted_at' => $model->deleted_at,
                        'deleted_by' => $log?->deleter,
                        'deleted_by_name' => $log?->deleter?->name,
                        'log_id' => $log?->id,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Trash collect skip module', [
                    'module' => $key,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $rows;
    }

    public function countsByModule(): array
    {
        $counts = [];
        foreach (TrashRegistry::definitions() as $key => $def) {
            try {
                $modelClass = $def['model'];
                if (! class_exists($modelClass) || ! TrashRegistry::usesSoftDeletes($modelClass)) {
                    $counts[$key] = 0;
                    continue;
                }
                $table = (new $modelClass)->getTable();
                if (! Schema::hasTable($table)) {
                    $counts[$key] = 0;
                    continue;
                }

                if (isset($def['match']) && is_callable($def['match'])) {
                    $counts[$key] = $modelClass::onlyTrashed()
                        ->get()
                        ->filter(fn (Model $model) => ($def['match'])($model))
                        ->count();
                } else {
                    $counts[$key] = $modelClass::onlyTrashed()->count();
                }
            } catch (\Throwable) {
                $counts[$key] = 0;
            }
        }

        return $counts;
    }

    public function totalCount(): int
    {
        return array_sum($this->countsByModule());
    }

    public function restore(string $moduleKey, int $modelId): Model
    {
        $def = TrashRegistry::find($moduleKey);
        if (! $def) {
            throw new InvalidArgumentException('Loại dữ liệu không hợp lệ.');
        }

        /** @var class-string<Model> $modelClass */
        $modelClass = $def['model'];
        $model = $modelClass::onlyTrashed()->findOrFail($modelId);

        DB::transaction(function () use ($model) {
            $model->restore();
        });

        return $model->fresh();
    }

    /**
     * Permanent delete (no recovery). Super-admin only at controller layer.
     */
    public function forceDelete(string $moduleKey, int $modelId): void
    {
        $def = TrashRegistry::find($moduleKey);
        if (! $def) {
            throw new InvalidArgumentException('Loại dữ liệu không hợp lệ.');
        }

        /** @var class-string<Model> $modelClass */
        $modelClass = $def['model'];
        $model = $modelClass::onlyTrashed()->findOrFail($modelId);

        DB::transaction(function () use ($model) {
            $model->forceDelete();
        });
    }

    public function findItem(string $moduleKey, int $modelId): ?array
    {
        return $this->collect(['module' => $moduleKey])
            ->first(fn (array $row) => (int) $row['model_id'] === $modelId);
    }

    private function hasTrashLogTable(): bool
    {
        try {
            return Schema::hasTable('trash_logs');
        } catch (\Throwable) {
            return false;
        }
    }
}
