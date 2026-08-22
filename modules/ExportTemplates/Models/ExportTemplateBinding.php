<?php

namespace Modules\ExportTemplates\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\ExportTemplates\Enums\TemplateBindingType;

class ExportTemplateBinding extends Model
{
    protected $fillable = [
        'template_version_id',
        'target_ref',
        'target_type',
        'data_key',
        'binding_type',
        'formatter',
        'options',
        'style_overrides',
        'sort_order',
    ];

    protected $casts = [
        'binding_type' => TemplateBindingType::class,
        'options' => 'array',
        'style_overrides' => 'array',
        'sort_order' => 'integer',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(ExportTemplateVersion::class, 'template_version_id');
    }
}
