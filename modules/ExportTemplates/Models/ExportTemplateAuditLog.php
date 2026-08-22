<?php

namespace Modules\ExportTemplates\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExportTemplateAuditLog extends Model
{
    public const ACTION_LEGACY_MIGRATED = 'legacy.migrated';

    public const ACTION_ACTIVATED = 'version.activated';

    public const ACTION_DEACTIVATED = 'version.deactivated';

    public const ACTION_TEMPLATE_CREATED = 'template.created';

    public const ACTION_TEMPLATE_CLONED = 'template.cloned';

    public const ACTION_VERSION_CREATED = 'version.created';

    public const ACTION_DOWNLOADED = 'version.downloaded';

    public const ACTION_ARCHIVED = 'template.archived';

    public const ACTION_ANALYZED = 'version.analyzed';

    public const ACTION_BINDING_UPDATED = 'binding.updated';

    public const ACTION_BINDING_DELETED = 'binding.deleted';

    public const ACTION_RENDERED = 'export.rendered';

    public const ACTION_FALLBACK = 'export.fallback';

    protected $fillable = [
        'template_id',
        'template_version_id',
        'actor_id',
        'action',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ExportTemplate::class, 'template_id');
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(ExportTemplateVersion::class, 'template_version_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
