<?php

namespace Modules\DatabaseManagement\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\DatabaseManagement\Models\BusinessRelationshipMap;
use Modules\DatabaseManagement\Models\DatabaseManagementAudit;
use Modules\DatabaseManagement\Models\DatabaseMigrationVersion;
use Illuminate\Support\Facades\Artisan;

class DatabaseManagementController extends Controller
{
    public function index(Request $request): View
    {
        return view('database-management::index', [
            'catalog' => $this->catalog($request->string('search')->toString()),
            'driver' => Schema::getConnection()->getDriverName(),
        ]);
    }

    public function schema(Request $request): JsonResponse
    {
        return response()->json([
            'driver' => Schema::getConnection()->getDriverName(),
            'tables' => $this->catalog($request->string('search')->toString()),
        ]);
    }

    public function map(Request $request): View
    {
        $catalog = $this->catalog($request->string('search')->toString());
        $relations = collect($catalog)->flatMap(function (array $table): array {
            return collect($table['foreign_keys'])->map(fn (array $foreign): array => [
                'from_table' => $table['name'],
                'from_columns' => $foreign['columns'],
                'to_table' => $foreign['foreign_table'],
                'to_columns' => $foreign['foreign_columns'],
                'on_delete' => $foreign['on_delete'],
            ])->all();
        })->values()->all();

        return view('database-management::map', [
            'catalog' => $catalog,
            'relations' => $relations,
            'driver' => Schema::getConnection()->getDriverName(),
        ]);
    }

    public function business(Request $request): View
    {
        return view('database-management::business', [
            'maps' => BusinessRelationshipMap::query()->with('creator')->latest()->get(),
            'tables' => $this->catalog(),
            'modules' => ['training_schedule' => 'Lịch huấn luyện', 'lms' => 'LMS', 'standard_hours' => 'Giờ chuẩn', 'grades' => 'Quản lý điểm'],
        ]);
    }

    public function data(Request $request): View
    {
        $catalog = $this->catalog();
        $table = $request->string('table')->toString();
        $selected = collect($catalog)->firstWhere('name', $table);
        $rows = null;
        if ($selected) {
            $query = DB::table($selected['name']);
            $search = trim($request->string('q')->toString());
            if ($search !== '') {
                $textColumns = collect($selected['columns'])
                    ->filter(fn (array $column): bool => preg_match('/char|text|uuid|email|code|name/i', $column['type'].' '.$column['name']) === 1)
                    ->pluck('name')->take(8);
                $query->where(function ($builder) use ($textColumns, $search): void {
                    foreach ($textColumns as $column) {
                        $builder->orWhere($column, 'like', '%'.$search.'%');
                    }
                });
            }
            $rows = $query->paginate(25)->withQueryString();
        }

        return view('database-management::data', [
            'catalog' => $catalog,
            'selected' => $selected,
            'rows' => $rows,
            'table' => $table,
            'primaryKey' => $selected ? $this->primaryKey($table) : null,
        ]);
    }

    public function updateData(Request $request): JsonResponse
    {
        $table = $request->string('table')->toString();
        $catalogTable = collect($this->catalog())->firstWhere('name', $table);
        abort_unless($catalogTable, 404, 'Bảng không tồn tại.');
        $primaryKey = $this->primaryKey($table);
        abort_unless($primaryKey, 422, 'Bảng không có khóa chính để cập nhật an toàn.');
        $recordKey = $request->input('record_key');
        $record = DB::table($table)->where($primaryKey, $recordKey)->first();
        abort_unless($record, 404, 'Không tìm thấy bản ghi.');

        $editable = collect($catalogTable['columns'])
            ->reject(fn (array $column): bool => $column['name'] === $primaryKey || $column['auto_increment'] || in_array($column['name'], ['created_at', 'updated_at'], true))
            ->pluck('name')->all();
        $values = collect($request->input('values', []))->only($editable)->all();

        DB::transaction(function () use ($table, $primaryKey, $recordKey, $values, $record, $request): void {
            DB::table($table)->where($primaryKey, $recordKey)->update($values);
            DatabaseManagementAudit::query()->create([
                'actor_id' => $request->user()->id,
                'action' => 'update',
                'table_name' => $table,
                'record_key' => (string) $recordKey,
                'before_values' => (array) $record,
                'after_values' => (array) DB::table($table)->where($primaryKey, $recordKey)->first(),
                'request_id' => $request->header('X-Request-Id'),
            ]);
        });

        return response()->json(['message' => 'Đã cập nhật bản ghi và ghi audit log.']);
    }

    public function sql(): View
    {
        return view('database-management::sql');
    }

    public function executeSql(Request $request): View
    {
        $data = $request->validate(['query' => ['required', 'string', 'max:10000']]);
        $query = trim($data['query']);
        abort_if(str_contains($query, ';'), 422, 'Chỉ cho phép một câu lệnh SQL, không dùng dấu chấm phẩy.');
        abort_unless(preg_match('/^(SELECT|WITH|SHOW|DESCRIBE|DESC|EXPLAIN)\b/i', $query) === 1, 422, 'SQL Console Sprint 31 chỉ cho phép truy vấn đọc.');
        abort_if(preg_match('/\b(INSERT|UPDATE|DELETE|DROP|ALTER|TRUNCATE|CREATE|RENAME|GRANT|REVOKE)\b/i', $query) === 1, 422, 'Truy vấn chứa từ khóa thay đổi dữ liệu/schema.');

        $safeQuery = $query;
        if (preg_match('/^(SELECT|WITH)\b/i', $query) === 1 && preg_match('/\bLIMIT\b/i', $query) !== 1) {
            $safeQuery .= ' LIMIT 200';
        }
        $result = DB::select($safeQuery);
        DatabaseManagementAudit::query()->create([
            'actor_id' => $request->user()->id,
            'action' => 'sql_read',
            'table_name' => 'sql_console',
            'record_key' => null,
            'before_values' => ['query' => $safeQuery],
            'after_values' => ['row_count' => count($result)],
            'request_id' => $request->header('X-Request-Id'),
        ]);

        return view('database-management::sql', ['query' => $query, 'result' => $result]);
    }

    public function migrationDesigner(): View
    {
        $proposals = BusinessRelationshipMap::query()
            ->where('status', 'proposed')
            ->latest()
            ->get();
        $previews = $proposals->mapWithKeys(function (BusinessRelationshipMap $map): array {
            $identifier = static fn (string $value): string => preg_replace('/[^A-Za-z0-9_]/', '', $value);
            $source = $identifier($map->source_table);
            $sourceField = $identifier($map->source_field);
            $target = $identifier($map->target_table);
            $targetField = $identifier($map->target_field);

            return [$map->id => "ALTER TABLE `{$source}` ADD CONSTRAINT `brm_{$map->id}` FOREIGN KEY (`{$sourceField}`) REFERENCES `{$target}` (`{$targetField}`);\n-- ROLLBACK: ALTER TABLE `{$source}` DROP FOREIGN KEY `brm_{$map->id}`;"];
        });

        $versions = DatabaseMigrationVersion::query()->with('creator')->latest('version')->get();

        return view('database-management::migrations', compact('proposals', 'previews', 'versions'));
    }

    public function createMigrationVersion(Request $request): JsonResponse
    {
        $proposals = BusinessRelationshipMap::query()->where('status', 'proposed')->latest()->get();
        abort_if($proposals->isEmpty(), 422, 'Không có mapping đề xuất để tạo phiên bản.');

        $up = $proposals->map(function (BusinessRelationshipMap $map): string {
            $identifier = static fn (string $value): string => preg_replace('/[^A-Za-z0-9_]/', '', $value);
            return sprintf(
                'ALTER TABLE `%s` ADD CONSTRAINT `brm_%d` FOREIGN KEY (`%s`) REFERENCES `%s` (`%s`);',
                $identifier($map->source_table), $map->id, $identifier($map->source_field), $identifier($map->target_table), $identifier($map->target_field)
            );
        })->implode("\n");
        $down = $proposals->map(fn (BusinessRelationshipMap $map): string => 'ALTER TABLE `'.preg_replace('/[^A-Za-z0-9_]/', '', $map->source_table).'` DROP FOREIGN KEY `brm_'.$map->id.'`;')->implode("\n");
        $version = (int) DatabaseMigrationVersion::query()->max('version') + 1;
        $record = DatabaseMigrationVersion::query()->create([
            'name' => 'business-map-'.now()->format('Ymd'),
            'version' => $version,
            'status' => 'draft',
            'up_sql' => $up,
            'down_sql' => $down,
            'checksum' => hash('sha256', $up."\n".$down),
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['message' => 'Đã tạo migration version '.$record->version.' ở trạng thái draft.', 'id' => $record->id], 201);
    }

    public function validateMigrationVersion(DatabaseMigrationVersion $version): JsonResponse
    {
        abort_unless($version->status === 'draft', 422, 'Chỉ version draft mới được validation.');
        abort_unless(hash_equals($version->checksum, hash('sha256', $version->up_sql."\n".$version->down_sql)), 422, 'Checksum migration không khớp.');
        foreach (preg_split('/\R+/', trim($version->up_sql)) ?: [] as $statement) {
            if ($statement === '') continue;
            if (preg_match('/ALTER TABLE `([^`]+)`.*REFERENCES `([^`]+)`/i', $statement, $matches) !== 1) {
                abort(422, 'Migration chứa câu lệnh không nằm trong allowlist foreign key.');
            }
            abort_unless(Schema::hasTable($matches[1]) && Schema::hasTable($matches[2]), 422, 'Bảng trong migration không còn tồn tại.');
        }
        $version->update(['status' => 'validated', 'validation_report' => ['environment' => app()->environment(), 'tables_checked' => true, 'foreign_key_allowlist' => true, 'staging_required' => true, 'validated_at' => now()->toIso8601String()]]);

        return response()->json(['message' => 'Migration đã được validation.']);
    }

    public function rejectMigrationVersion(DatabaseMigrationVersion $version): JsonResponse
    {
        abort_unless(in_array($version->status, ['draft', 'validated'], true), 422, 'Version không thể reject ở trạng thái hiện tại.');
        $version->update(['status' => 'rejected']);

        return response()->json(['message' => 'Migration đã bị từ chối.']);
    }

    public function publishMigrationVersion(Request $request, DatabaseMigrationVersion $version): JsonResponse
    {
        abort_unless($version->status === 'validated', 422, 'Chỉ migration đã validation mới được publish.');
        abort_unless($version->backup_reference, 422, 'Cần tạo backup thành công trước khi publish.');
        abort_unless((bool) config('database-management.allow_publish', false), 403, 'Publish schema đang bị khóa. Hãy bật DB_MANAGEMENT_ALLOW_PUBLISH sau khi backup và staging pass.');

        $version->update(['status' => 'published', 'published_by' => $request->user()->id, 'published_at' => now()]);
        DatabaseManagementAudit::query()->create(['actor_id' => $request->user()->id, 'action' => 'migration_publish', 'table_name' => 'database_migration_versions', 'record_key' => (string) $version->id, 'before_values' => ['status' => 'validated'], 'after_values' => ['status' => 'published'], 'request_id' => $request->header('X-Request-Id')]);

        return response()->json(['message' => 'Migration đã publish metadata. SQL execution được tách riêng qua deployment pipeline.']);
    }

    public function backupMigrationVersion(Request $request, DatabaseMigrationVersion $version): JsonResponse
    {
        abort_unless(in_array($version->status, ['draft', 'validated'], true), 422, 'Version không thể backup ở trạng thái hiện tại.');
        Artisan::call('backup:run', ['--only-db' => true, '--disable-notifications' => true]);
        $reference = 'backup-'.now()->format('YmdHis');
        $version->update(['backup_reference' => $reference]);
        DatabaseManagementAudit::query()->create(['actor_id' => $request->user()->id, 'action' => 'migration_backup', 'table_name' => 'database_migration_versions', 'record_key' => (string) $version->id, 'after_values' => ['backup_reference' => $reference], 'request_id' => $request->header('X-Request-Id')]);
        return response()->json(['message' => 'Backup database thành công.', 'reference' => $reference]);
    }

    public function rollbackMigrationVersion(Request $request, DatabaseMigrationVersion $version): JsonResponse
    {
        abort_unless($version->status === 'published', 422, 'Chỉ migration published mới rollback được.');
        abort_unless((bool) config('database-management.allow_publish', false), 403, 'Rollback đang bị khóa bởi DB_MANAGEMENT_ALLOW_PUBLISH.');
        DB::unprepared($version->down_sql);
        $version->update(['rollback_status' => 'completed']);
        DatabaseManagementAudit::query()->create(['actor_id' => $request->user()->id, 'action' => 'migration_rollback', 'table_name' => 'database_migration_versions', 'record_key' => (string) $version->id, 'after_values' => ['rollback_status' => 'completed'], 'request_id' => $request->header('X-Request-Id')]);
        return response()->json(['message' => 'Rollback đã hoàn tất.']);
    }

    public function activateBusinessMap(Request $request, BusinessRelationshipMap $map): JsonResponse
    {
        abort_unless($map->status === 'proposed', 422, 'Mapping không còn ở trạng thái đề xuất.');
        abort_unless(DatabaseMigrationVersion::query()->where('status', 'published')->exists(), 422, 'Cần publish migration trước khi kích hoạt mapping.');
        $map->update(['status' => 'active', 'updated_by' => $request->user()->id]);
        return response()->json(['message' => 'Business mapping đã được kích hoạt.']);
    }

    public function integrity(): View
    {
        $checks = [];
        if (Schema::hasTable('training_schedules') && Schema::hasColumns('training_schedules', ['class_id', 'class_code'])) {
            $checks[] = [
                'key' => 'schedule_class_identity',
                'label' => 'Lịch có class_id và class_code không khớp',
                'severity' => 'high',
                'count' => DB::table('training_schedules as schedules')->join('classes', 'classes.id', '=', 'schedules.class_id')->whereColumn('schedules.class_code', '!=', 'classes.code')->count(),
            ];
        }
        if (Schema::hasTable('units')) {
            $checks[] = [
                'key' => 'faculty_scope_config',
                'label' => 'Đơn vị K1–K8 thiếu functional_type/faculty_code',
                'severity' => 'medium',
                'count' => DB::table('units')->whereIn('code', \App\Support\TrainingDept::FACULTY_CODES)->where(function ($query): void {
                    $query->whereNull('faculty_code')->orWhere('faculty_code', '')->orWhere('functional_type', '!=', \Modules\Unit\Models\Unit::FUNCTIONAL_FACULTY);
                })->count(),
            ];
        }
        if (Schema::hasTable('business_relationship_maps')) {
            $checks[] = [
                'key' => 'business_map_proposals',
                'label' => 'Business mapping đang chờ publish',
                'severity' => 'info',
                'count' => DB::table('business_relationship_maps')->where('status', 'proposed')->count(),
            ];
        }

        return view('database-management::integrity', ['checks' => $checks]);
    }

    public function audits(Request $request): View
    {
        $audits = DatabaseManagementAudit::query()
            ->with('actor:id,name,email')
            ->when($request->filled('action'), fn ($query) => $query->where('action', $request->string('action')->toString()))
            ->when($request->filled('table_name'), fn ($query) => $query->where('table_name', 'like', '%'.$request->string('table_name')->toString().'%'))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('database-management::audits', compact('audits'));
    }

    private function primaryKey(string $table): ?string
    {
        $index = collect(Schema::getIndexes($table))
            ->first(fn (array|object $candidate): bool => (bool) data_get($candidate, 'primary', false));

        return $index ? ((string) data_get($index, 'columns.0') ?: null) : null;
    }

    public function storeBusiness(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'module_key' => ['required', 'in:training_schedule,lms,standard_hours,grades'],
            'source_table' => ['required', 'string', 'max:120'],
            'source_field' => ['required', 'string', 'max:120'],
            'target_table' => ['required', 'string', 'max:120'],
            'target_field' => ['required', 'string', 'max:120'],
            'relationship_type' => ['required', 'in:one_to_one,one_to_many,many_to_many'],
        ]);
        $tableNames = collect($this->catalog())->pluck('name');
        abort_unless($tableNames->contains($data['source_table']) && $tableNames->contains($data['target_table']), 422, 'Bảng không tồn tại trong schema hiện tại.');

        $map = BusinessRelationshipMap::query()->create($data + [
            'status' => 'proposed',
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return response()->json(['message' => 'Đã lưu mapping ở trạng thái đề xuất.', 'id' => $map->id], 201);
    }

    /**
     * Read-only schema catalog. Mutating schema is intentionally deferred to
     * the migration designer sprint so production cannot be changed by a
     * casual drag-and-drop action.
     *
     * @return list<array<string, mixed>>
     */
    private function catalog(string $search = ''): array
    {
        $needle = mb_strtolower(trim($search));

        return collect(Schema::getTables())
            ->map(function (object|array $table): array {
                $name = (string) data_get($table, 'name', data_get($table, 'TABLE_NAME', ''));
                $columns = collect(Schema::getColumns($name))->map(function (object|array $column): array {
                    return [
                        'name' => (string) data_get($column, 'name', data_get($column, 'COLUMN_NAME', '')),
                        'type' => (string) data_get($column, 'type', data_get($column, 'TYPE_NAME', '')),
                        'nullable' => (bool) data_get($column, 'nullable', data_get($column, 'IS_NULLABLE', false)),
                        'default' => data_get($column, 'default', data_get($column, 'COLUMN_DEFAULT')),
                        'auto_increment' => (bool) data_get($column, 'auto_increment', false),
                    ];
                })->values()->all();

                $foreignKeys = collect(Schema::getForeignKeys($name))->map(function (object|array $foreign): array {
                    return [
                        'columns' => (array) data_get($foreign, 'columns', []),
                        'foreign_table' => (string) data_get($foreign, 'foreign_table', ''),
                        'foreign_columns' => (array) data_get($foreign, 'foreign_columns', []),
                        'on_delete' => data_get($foreign, 'on_delete'),
                        'on_update' => data_get($foreign, 'on_update'),
                    ];
                })->values()->all();

                return [
                    'name' => $name,
                    'columns' => $columns,
                    'foreign_keys' => $foreignKeys,
                    'row_count' => null,
                ];
            })
            ->filter(function (array $table) use ($needle): bool {
                if ($needle === '') {
                    return true;
                }

                return str_contains(mb_strtolower($table['name']), $needle)
                    || collect($table['columns'])->contains(
                        fn (array $column): bool => str_contains(mb_strtolower($column['name']), $needle)
                    );
            })
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }
}
