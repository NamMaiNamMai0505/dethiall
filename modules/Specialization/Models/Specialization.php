<?php

namespace Modules\Specialization\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Class\Models\ClassModel;
use Modules\Instructor\Models\Instructor;
use Modules\Subject\Models\Subject;

class Specialization extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'specializations';

    // Constants for certification types
    const CERTIFICATION_CERTIFICATE = 'certificate';

    const CERTIFICATION_SECONDARY_DIPLOMA = 'secondary_diploma';

    const CERTIFICATION_COLLEGE_DIPLOMA = 'college_diploma';

    const CERTIFICATION_BACHELOR_DEGREE = 'bachelor_degree';

    const CERTIFICATION_MASTER_DEGREE = 'master_degree';

    const CERTIFICATION_DOCTORATE_DEGREE = 'doctorate_degree';

    // Constants for levels
    const LEVEL_BEGINNER = 'beginner';

    const LEVEL_INTERMEDIATE = 'intermediate';

    const LEVEL_ADVANCED = 'advanced';

    const LEVEL_EXPERT = 'expert';

    const TRAINING_FORM_FORMAL = 'formal';

    const TRAINING_FORM_BRIDGING = 'bridging';

    const TRAINING_FORM_CONVERSION = 'conversion';

    protected $fillable = [
        'training_system_id',
        'name',
        'code',
        'major_code',
        'description',
        'level',
        'duration_months',
        'training_form',
        'prerequisites',
        'certification_type',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'prerequisites' => 'array',
        'is_active' => 'boolean',
        'duration_months' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function trainingSystem()
    {
        return $this->belongsTo(TrainingSystem::class, 'training_system_id');
    }

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    // Define relationships

    /**
     * Get the user who created this specialization
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this specialization
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function instructors()
    {
        return $this->hasMany(Instructor::class);
    }

    public function classes()
    {
        return $this->hasMany(ClassModel::class);
    }

    public function subjects()
    {
        return $this->hasMany(Subject::class);
    }

    // Scopes

    /**
     * Scope to get only active specializations
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to search by name or code
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%")
                ->orWhere('major_code', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }

    /**
     * Scope to filter by level
     */
    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    // Accessors & Mutators

    /**
     * Get the level in Vietnamese
     */
    public function getLevelTextAttribute()
    {
        return match ($this->level) {
            'beginner' => 'Sơ cấp',
            'intermediate' => 'Trung cấp',
            'advanced' => 'Cao đẳng',
            'expert' => 'Đại học',
            default => 'Không xác định'
        };
    }

    /**
     * Get the certification type in Vietnamese
     */
    public function getCertificationTypeTextAttribute()
    {
        return match ($this->certification_type) {
            'certificate' => 'Chứng chỉ',
            'secondary_diploma' => 'Bằng trung cấp',
            'college_diploma' => 'Bằng cao đẳng',
            'bachelor_degree' => 'Bằng đại học',
            'master_degree' => 'Bằng thạc sĩ',
            'doctorate_degree' => 'Bằng tiến sĩ',
            default => 'Không xác định'
        };
    }

    /**
     * Get the status text
     */
    public function getStatusTextAttribute()
    {
        return $this->is_active ? 'Đang hoạt động' : 'Tạm ngưng';
    }

    /**
     * Get the status color for UI
     */
    public function getStatusColorAttribute()
    {
        return $this->is_active ? 'success' : 'danger';
    }

    public function getTrainingFormTextAttribute(): string
    {
        return self::getTrainingFormOptions()[$this->training_form] ?? 'Chưa xác định';
    }

    public function getSelectionLabelAttribute(): string
    {
        $meta = array_filter([
            $this->major_code,
            $this->level_text,
            $this->trainingSystem?->name,
            $this->training_form_text,
        ]);

        return $this->name.($meta === [] ? '' : ' ('.implode(' · ', $meta).')');
    }

    public function getReportLabelAttribute(): string
    {
        return $this->name.($this->major_code ? ' ('.$this->major_code.')' : '');
    }

    // Static methods for getting options

    /**
     * Get all certification type options
     */
    public static function getCertificationTypeOptions()
    {
        return [
            self::CERTIFICATION_CERTIFICATE => 'Chứng chỉ',
            self::CERTIFICATION_SECONDARY_DIPLOMA => 'Bằng trung cấp',
            self::CERTIFICATION_COLLEGE_DIPLOMA => 'Bằng cao đẳng',
            self::CERTIFICATION_BACHELOR_DEGREE => 'Bằng đại học',
            self::CERTIFICATION_MASTER_DEGREE => 'Bằng thạc sĩ',
            self::CERTIFICATION_DOCTORATE_DEGREE => 'Bằng tiến sĩ',
        ];
    }

    /**
     * Get all level options
     */
    public static function getLevelOptions()
    {
        return [
            self::LEVEL_BEGINNER => 'Sơ cấp',
            self::LEVEL_INTERMEDIATE => 'Trung cấp',
            self::LEVEL_ADVANCED => 'Cao đẳng',
            self::LEVEL_EXPERT => 'Đại học',
        ];
    }

    public static function getTrainingFormOptions(): array
    {
        return [
            self::TRAINING_FORM_FORMAL => 'Chính quy',
            self::TRAINING_FORM_BRIDGING => 'Liên thông',
            self::TRAINING_FORM_CONVERSION => 'Chuyển loại',
        ];
    }

    /**
     * Get certification type text by value
     */
    public static function getCertificationTypeText($value)
    {
        $options = self::getCertificationTypeOptions();

        return $options[$value] ?? 'Không xác định';
    }

    /**
     * Get level text by value
     */
    public static function getLevelText($value)
    {
        $options = self::getLevelOptions();

        return $options[$value] ?? 'Không xác định';
    }
}
