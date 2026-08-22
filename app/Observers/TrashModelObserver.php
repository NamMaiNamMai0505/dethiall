<?php

namespace App\Observers;

use App\Models\TrashLog;
use App\Support\TrashRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class TrashModelObserver
{
    public function deleted(Model $model): void
    {
        if (! $this->ready()) {
            return;
        }

        try {
            if (method_exists($model, 'isForceDeleting') && $model->isForceDeleting()) {
                TrashLog::query()
                    ->where('model_type', $model::class)
                    ->where('model_id', $model->getKey())
                    ->whereNull('restored_at')
                    ->delete();

                return;
            }

            $def = TrashRegistry::findByModel($model);
            if (! $def) {
                return;
            }

            $payload = [
                'module_key' => $def['key'],
                'type_label' => $def['label'],
                'title' => (string) ($def['title'])($model),
                'identifier' => $def['identifier']($model),
                'summary' => $def['summary']($model),
                'deleted_by' => Auth::id(),
                'deleted_at' => $model->deleted_at ?? now(),
                'restored_at' => null,
            ];

            $existing = TrashLog::query()
                ->where('model_type', $model::class)
                ->where('model_id', $model->getKey())
                ->whereNull('restored_at')
                ->first();

            if ($existing) {
                $existing->update($payload);

                return;
            }

            TrashLog::query()->create(array_merge($payload, [
                'model_type' => $model::class,
                'model_id' => $model->getKey(),
            ]));
        } catch (\Throwable $e) {
            Log::warning('Trash observer deleted failed', [
                'model' => $model::class,
                'id' => $model->getKey(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function restored(Model $model): void
    {
        if (! $this->ready()) {
            return;
        }

        try {
            TrashLog::query()
                ->where('model_type', $model::class)
                ->where('model_id', $model->getKey())
                ->whereNull('restored_at')
                ->update(['restored_at' => now()]);
        } catch (\Throwable $e) {
            Log::warning('Trash observer restored failed', [
                'model' => $model::class,
                'id' => $model->getKey(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function ready(): bool
    {
        try {
            return Schema::hasTable('trash_logs');
        } catch (\Throwable) {
            return false;
        }
    }
}
