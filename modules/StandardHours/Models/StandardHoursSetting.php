<?php

namespace Modules\StandardHours\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StandardHoursSetting extends Model
{
    public const KEY_PERIOD_MODE = 'period_mode';

    public const KEY_RESEARCH_DISTRIBUTION = 'research_distribution_rules';

    protected $table = 'standard_hours_settings';

    protected $fillable = [
        'key',
        'value',
        'updated_by',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
