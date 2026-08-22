<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $this->extendTemplatesTable();
        $this->createVersionsTable();
        $this->createBindingsTable();
        $this->createActivationsTable();
        $this->createAuditLogsTable();
        $this->migrateLegacyTemplates();
    }

    public function down(): void
    {
        Schema::dropIfExists('export_template_audit_logs');
        Schema::dropIfExists('export_template_activations');
        Schema::dropIfExists('export_template_bindings');
        Schema::dropIfExists('export_template_versions');

        if (! Schema::hasTable('export_templates')) {
            return;
        }

        $hasUpdatedBy = Schema::hasColumn('export_templates', 'updated_by');
        $columns = array_values(array_filter([
            'code',
            'module_key',
            'output_format',
            'description',
            'status',
        ], fn (string $column): bool => Schema::hasColumn('export_templates', $column)));

        Schema::table('export_templates', function (Blueprint $table) use ($hasUpdatedBy, $columns) {
            if ($hasUpdatedBy) {
                $table->dropConstrainedForeignId('updated_by');
            }

            if ($columns !== []) {
                $table->dropUnique('export_templates_code_unique');
                $table->dropIndex('export_templates_feature_format_index');
                $table->dropIndex('export_templates_status_index');
                $table->dropColumn($columns);
            }
        });
    }

    private function extendTemplatesTable(): void
    {
        if (! Schema::hasTable('export_templates')) {
            return;
        }

        $addCode = ! Schema::hasColumn('export_templates', 'code');
        $addModuleKey = ! Schema::hasColumn('export_templates', 'module_key');
        $addOutputFormat = ! Schema::hasColumn('export_templates', 'output_format');
        $addDescription = ! Schema::hasColumn('export_templates', 'description');
        $addStatus = ! Schema::hasColumn('export_templates', 'status');
        $addUpdatedBy = ! Schema::hasColumn('export_templates', 'updated_by');

        Schema::table('export_templates', function (Blueprint $table) use (
            $addCode,
            $addModuleKey,
            $addOutputFormat,
            $addDescription,
            $addStatus,
            $addUpdatedBy
        ) {
            if ($addCode) {
                $table->string('code', 128)->nullable()->after('id');
            }
            if ($addModuleKey) {
                $table->string('module_key', 64)->nullable()->after('scope');
            }
            if ($addOutputFormat) {
                $table->string('output_format', 16)->nullable()->after('feature_key');
            }
            if ($addDescription) {
                $table->text('description')->nullable()->after('notes');
            }
            if ($addStatus) {
                $table->string('status', 24)->default('draft')->after('description');
            }
            if ($addUpdatedBy) {
                $table->foreignId('updated_by')
                    ->nullable()
                    ->after('created_by')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        $this->ensureIndex(
            'export_templates',
            'export_templates_code_unique',
            fn (Blueprint $table) => $table->unique('code', 'export_templates_code_unique')
        );
        $this->ensureIndex(
            'export_templates',
            'export_templates_feature_format_index',
            fn (Blueprint $table) => $table->index(
                ['feature_key', 'output_format'],
                'export_templates_feature_format_index'
            )
        );
        $this->ensureIndex(
            'export_templates',
            'export_templates_status_index',
            fn (Blueprint $table) => $table->index('status', 'export_templates_status_index')
        );
    }

    private function createVersionsTable(): void
    {
        if (Schema::hasTable('export_template_versions')) {
            return;
        }

        Schema::create('export_template_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')
                ->constrained('export_templates')
                ->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('disk', 32)->default('local');
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->string('mime', 128)->nullable();
            $table->string('file_extension', 16)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('checksum_sha256', 64)->nullable();
            $table->json('manifest')->nullable();
            $table->string('status', 24)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['template_id', 'version_number'], 'export_template_versions_number_unique');
            $table->index(['template_id', 'status'], 'export_template_versions_status_index');
        });
    }

    private function createBindingsTable(): void
    {
        if (Schema::hasTable('export_template_bindings')) {
            return;
        }

        Schema::create('export_template_bindings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_version_id')
                ->constrained('export_template_versions')
                ->cascadeOnDelete();
            $table->string('target_ref', 255);
            $table->string('target_type', 32);
            $table->string('data_key', 255);
            $table->string('binding_type', 32)->default('scalar');
            $table->string('formatter', 128)->nullable();
            $table->json('options')->nullable();
            $table->json('style_overrides')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(
                ['template_version_id', 'target_ref'],
                'export_template_bindings_target_unique'
            );
            $table->index(
                ['template_version_id', 'binding_type'],
                'export_template_bindings_type_index'
            );
        });
    }

    private function createActivationsTable(): void
    {
        if (Schema::hasTable('export_template_activations')) {
            return;
        }

        Schema::create('export_template_activations', function (Blueprint $table) {
            $table->id();
            $table->string('feature_key', 64);
            $table->string('output_format', 16);
            $table->foreignId('template_version_id')
                ->constrained('export_template_versions')
                ->cascadeOnDelete();
            $table->foreignId('activated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('activated_at');
            $table->timestamps();

            $table->unique(
                ['feature_key', 'output_format'],
                'export_template_activations_feature_format_unique'
            );
            $table->index('template_version_id', 'export_template_activations_version_index');
        });
    }

    private function createAuditLogsTable(): void
    {
        if (Schema::hasTable('export_template_audit_logs')) {
            return;
        }

        Schema::create('export_template_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')
                ->nullable()
                ->constrained('export_templates')
                ->nullOnDelete();
            $table->foreignId('template_version_id')
                ->nullable()
                ->constrained('export_template_versions')
                ->nullOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 64)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['template_id', 'created_at'], 'export_template_audit_template_index');
        });
    }

    private function migrateLegacyTemplates(): void
    {
        if (
            ! Schema::hasTable('export_templates')
            || ! Schema::hasTable('export_template_versions')
        ) {
            return;
        }

        DB::table('export_templates')
            ->orderBy('id')
            ->get()
            ->each(function (object $template): void {
                $detectedFormat = $this->outputFormat(
                    (string) ($template->original_name ?: $template->file_path)
                );
                $format = in_array($template->output_format, ['word', 'excel'], true)
                    ? $template->output_format
                    : $detectedFormat;
                $formatCode = $format ?: 'unsupported';
                $status = (bool) $template->is_active ? 'published' : 'draft';
                $baseCode = Str::of((string) $template->feature_key)
                    ->replace('.', '_')
                    ->slug('_')
                    ->limit(96, '')
                    ->toString();
                $code = ($baseCode !== '' ? $baseCode : 'legacy_template')
                    .'_'.$formatCode.'_'.$template->id;

                DB::table('export_templates')
                    ->where('id', $template->id)
                    ->update([
                        'code' => $template->code ?: $code,
                        'module_key' => $template->module_key ?: $template->scope,
                        'output_format' => $template->output_format ?: $format,
                        'description' => $template->description ?: $template->notes,
                        'status' => $template->status === 'draft' && (bool) $template->is_active
                            ? 'published'
                            : ($template->status ?: $status),
                    ]);

                $version = DB::table('export_template_versions')
                    ->where('template_id', $template->id)
                    ->where('version_number', 1)
                    ->first();

                if (! $version) {
                    $extension = strtolower(pathinfo(
                        (string) ($template->original_name ?: $template->file_path),
                        PATHINFO_EXTENSION
                    ));

                    $versionId = DB::table('export_template_versions')->insertGetId([
                        'template_id' => $template->id,
                        'version_number' => 1,
                        'disk' => $template->disk ?: 'local',
                        'file_path' => $template->file_path,
                        'original_name' => $template->original_name,
                        'mime' => $template->mime,
                        'file_extension' => $extension ?: null,
                        'file_size' => null,
                        'checksum_sha256' => null,
                        'manifest' => json_encode([
                            'legacy_placeholders' => $this->jsonValue($template->placeholders),
                            'legacy_cell_map' => $this->jsonValue($template->cell_map),
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'status' => $status,
                        'created_by' => $template->created_by,
                        'created_at' => $template->created_at ?: now(),
                        'updated_at' => $template->updated_at ?: now(),
                    ]);
                } else {
                    $versionId = $version->id;
                }

                if ((bool) $template->is_active && $format !== null) {
                    DB::table('export_template_activations')->updateOrInsert(
                        [
                            'feature_key' => $template->feature_key,
                            'output_format' => $format,
                        ],
                        [
                            'template_version_id' => $versionId,
                            'activated_by' => $template->created_by,
                            'activated_at' => now(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }

                DB::table('export_template_audit_logs')->insert([
                    'template_id' => $template->id,
                    'template_version_id' => $versionId,
                    'actor_id' => null,
                    'action' => 'legacy.migrated',
                    'metadata' => json_encode([
                        'source_table' => 'export_templates',
                        'version_number' => 1,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    private function outputFormat(string $filename): ?string
    {
        return match (strtolower(pathinfo($filename, PATHINFO_EXTENSION))) {
            'doc', 'docx' => 'word',
            'xls', 'xlsx', 'xlsm', 'xlsb', 'csv' => 'excel',
            default => null,
        };
    }

    private function jsonValue(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }

    private function ensureIndex(
        string $tableName,
        string $indexName,
        callable $definition
    ): void {
        $indexes = Schema::getIndexes($tableName);
        $exists = collect($indexes)->contains(
            fn (array $index): bool => ($index['name'] ?? null) === $indexName
        );

        if (! $exists) {
            Schema::table($tableName, $definition);
        }
    }
};
