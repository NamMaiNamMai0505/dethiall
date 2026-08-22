<?php

namespace Modules\ExportTemplates\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Modules\ExportTemplates\Enums\OutputFormat;
use Modules\ExportTemplates\Enums\TemplateStatus;

class ExportTemplate extends Model
{
    use SoftDeletes;

    public const SCOPE_DASHBOARD = 'dashboard';

    public const SCOPE_LMS = 'lms';

    public const SCOPE_GRADES = 'grades';

    public const SCOPE_SHARED = 'shared';

    protected $table = 'export_templates';

    protected $fillable = [
        'code', 'name', 'scope', 'module_key', 'feature_key', 'output_format',
        'file_path', 'disk',
        'mime', 'original_name', 'placeholders', 'cell_map',
        'notes', 'description', 'status', 'is_active', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'placeholders' => 'array',
        'cell_map' => 'array',
        'is_active' => 'boolean',
        'output_format' => OutputFormat::class,
        'status' => TemplateStatus::class,
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ExportTemplateVersion::class, 'template_id')
            ->orderByDesc('version_number');
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(ExportTemplateVersion::class, 'template_id')
            ->latestOfMany('version_number');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(ExportTemplateAuditLog::class, 'template_id')
            ->latest();
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function scopeForScope($q, string $scope)
    {
        return $q->where(function ($qq) use ($scope) {
            $qq->where('scope', $scope)->orWhere('scope', self::SCOPE_SHARED);
        });
    }

    public function scopeForFeature($q, string $featureKey)
    {
        return $q->where('feature_key', $featureKey);
    }

    public function scopeForFormat($q, OutputFormat|string $format)
    {
        $value = $format instanceof OutputFormat ? $format->value : $format;

        return $q->where('output_format', $value);
    }

    public function absolutePath(): ?string
    {
        if (! $this->file_path) {
            return null;
        }

        return Storage::disk($this->disk ?: 'local')->path($this->file_path);
    }

    public function scopeLabel(): string
    {
        return match ($this->scope) {
            self::SCOPE_DASHBOARD => 'Dashboard',
            self::SCOPE_LMS => 'LMS',
            self::SCOPE_GRADES => 'Quản lý điểm',
            self::SCOPE_SHARED => 'Dùng chung',
            default => $this->scope,
        };
    }
}
