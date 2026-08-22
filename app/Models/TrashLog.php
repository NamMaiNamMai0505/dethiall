<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TrashLog extends Model
{
    protected $fillable = [
        'module_key',
        'type_label',
        'model_type',
        'model_id',
        'title',
        'identifier',
        'summary',
        'deleted_by',
        'deleted_at',
        'restored_at',
    ];

    protected $casts = [
        'summary' => 'array',
        'deleted_at' => 'datetime',
        'restored_at' => 'datetime',
    ];

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function model(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'model_type', 'model_id');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('restored_at');
    }

    public function scopeRestored($query)
    {
        return $query->whereNotNull('restored_at');
    }
}
