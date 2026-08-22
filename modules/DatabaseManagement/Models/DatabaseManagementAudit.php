<?php

namespace Modules\DatabaseManagement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatabaseManagementAudit extends Model
{
    protected $table = 'database_management_audits';

    protected $guarded = ['id'];

    protected $casts = ['before_values' => 'array', 'after_values' => 'array'];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
