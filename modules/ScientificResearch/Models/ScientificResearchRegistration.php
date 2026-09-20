<?php

namespace Modules\ScientificResearch\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\StandardHours\Models\ResearchCategory;

class ScientificResearchRegistration extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_PROPOSED = 'PROPOSED';
    public const STATUS_SUBMITTED = 'SUBMITTED';
    public const STATUS_UNIT_APPROVED = 'UNIT_APPROVED';
    public const STATUS_UNDER_REVIEW = 'UNDER_REVIEW';
    public const STATUS_NEEDS_REVISION = 'NEEDS_REVISION';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';
    public const STATUS_EXTENSION_REQUESTED = 'EXTENSION_REQUESTED';
    public const STATUS_ACCEPTANCE_PENDING = 'ACCEPTANCE_PENDING';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_REJECTED = 'REJECTED';

    protected $fillable = [
        'user_id',
        'research_category_id',
        'announcement_id',
        'title',
        'content',
        'topic',
        'academic_year',
        'implementation_year',
        'duration_years',
        'start_date',
        'end_date',
        'budget',
        'product_quantity',
        'participant_count',
        'lead_contribution_percent',
        'status',
        'registered_at',
        'submitted_at',
        'review_note',
        'revision_response_note',
        'revision_submitted_at',
        'unit_review_note',
        'unit_reviewed_by',
        'unit_reviewed_at',
        'reviewed_by',
        'reviewed_at',
        'created_by',
        'updated_by',
        'project_code',
        'progress_percent',
        'extended_until',
        'extension_requested_until',
        'extension_request_note',
        'extension_previous_status',
        'extension_requested_at',
        'extension_review_note',
        'extension_reviewed_by',
        'extension_reviewed_at',
        'standard_hours_research_record_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
        'duration_years' => 'decimal:2',
        'lead_contribution_percent' => 'decimal:2',
        'registered_at' => 'datetime',
        'submitted_at' => 'datetime',
        'revision_submitted_at' => 'datetime',
        'unit_reviewed_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'extended_until' => 'date',
        'extension_requested_until' => 'date',
        'extension_requested_at' => 'datetime',
        'extension_reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function researchCategory(): BelongsTo
    {
        return $this->belongsTo(ResearchCategory::class, 'research_category_id');
    }

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(ScientificResearchAnnouncement::class, 'announcement_id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(ScientificResearchResult::class, 'registration_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ScientificResearchRegistrationMember::class, 'registration_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function extensionRequests(): HasMany
    {
        return $this->hasMany(ScientificResearchRegistrationExtension::class, 'registration_id')
            ->latest('requested_at')
            ->latest('id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(ScientificResearchFile::class, 'owner_id')
            ->where('owner_type', 'registration');
    }
}
