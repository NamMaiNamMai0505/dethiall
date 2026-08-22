<?php

namespace Modules\DatabaseManagement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessRelationshipMap extends Model
{
    protected $table = 'business_relationship_maps';

    protected $guarded = ['id'];

    protected $casts = ['rules' => 'array'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
