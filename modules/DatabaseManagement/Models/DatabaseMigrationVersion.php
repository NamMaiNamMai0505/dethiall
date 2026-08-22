<?php

namespace Modules\DatabaseManagement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatabaseMigrationVersion extends Model
{
    protected $table = 'database_migration_versions';

    protected $guarded = ['id'];

    protected $casts = ['published_at' => 'datetime', 'validation_report' => 'array'];

    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function publisher(): BelongsTo { return $this->belongsTo(User::class, 'published_by'); }
}
