<?php

namespace Modules\ExportTemplates\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Modules\ExportTemplates\Enums\TemplateStatus;

class ExportTemplateVersion extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'template_id',
        'version_number',
        'disk',
        'file_path',
        'original_name',
        'mime',
        'file_extension',
        'file_size',
        'checksum_sha256',
        'manifest',
        'status',
        'created_by',
    ];

    protected $casts = [
        'version_number' => 'integer',
        'file_size' => 'integer',
        'manifest' => 'array',
        'status' => TemplateStatus::class,
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ExportTemplate::class, 'template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function bindings(): HasMany
    {
        return $this->hasMany(ExportTemplateBinding::class, 'template_version_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function activations(): HasMany
    {
        return $this->hasMany(ExportTemplateActivation::class, 'template_version_id');
    }

    public function builderDocument(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ExportTemplateBuilderDocument::class, 'template_version_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(ExportTemplateAuditLog::class, 'template_version_id')
            ->latest();
    }

    public function absolutePath(): ?string
    {
        if (! $this->file_path) {
            return null;
        }

        return Storage::disk($this->disk ?: 'local')->path($this->file_path);
    }
}
