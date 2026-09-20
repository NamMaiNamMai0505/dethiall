<?php

namespace Modules\ScientificResearch\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScientificResearchAnnouncement extends Model
{
    use SoftDeletes;

    public const STATUS_OPEN = 'OPEN';
    public const STATUS_CLOSED = 'CLOSED';

    protected $fillable = [
        'title',
        'content',
        'closes_at',
        'status',
        'template_path',
        'template_name',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'closes_at' => 'datetime',
    ];

    public function registrations(): HasMany
    {
        return $this->hasMany(ScientificResearchRegistration::class, 'announcement_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN
            && (! $this->closes_at || $this->closes_at->isFuture());
    }
}
