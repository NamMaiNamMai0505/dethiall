<?php

namespace Modules\ExportTemplates\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExportTemplateBuilderDocument extends Model
{
    protected $table = 'export_template_builder_documents';

    protected $fillable = [
        'template_version_id',
        'schema',
        'schema_version',
        'created_by',
    ];

    protected $casts = ['schema' => 'array'];

    public function version(): BelongsTo
    {
        return $this->belongsTo(ExportTemplateVersion::class, 'template_version_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
