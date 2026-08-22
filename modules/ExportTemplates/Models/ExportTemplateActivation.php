<?php

namespace Modules\ExportTemplates\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\ExportTemplates\Enums\OutputFormat;

class ExportTemplateActivation extends Model
{
    protected $fillable = [
        'feature_key',
        'output_format',
        'template_version_id',
        'activated_by',
        'activated_at',
    ];

    protected $casts = [
        'output_format' => OutputFormat::class,
        'activated_at' => 'datetime',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(ExportTemplateVersion::class, 'template_version_id');
    }

    public function activator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'activated_by');
    }
}
