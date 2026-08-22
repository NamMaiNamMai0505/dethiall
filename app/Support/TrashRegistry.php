<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Building\Models\Building;
use Modules\Class\Models\ClassModel;
use Modules\Classroom\Models\Classroom;
use Modules\Instructor\Models\Instructor;
use Modules\Specialization\Models\Specialization;
use Modules\StandardHours\Models\ConversionCategory;
use Modules\StandardHours\Models\ConversionRecord;
use Modules\StandardHours\Models\HourExchangeRecord;
use Modules\StandardHours\Models\HourNorm;
use Modules\StandardHours\Models\ObjectType;
use Modules\StandardHours\Models\Position;
use Modules\StandardHours\Models\ResearchCategory;
use Modules\StandardHours\Models\ResearchNorm;
use Modules\StandardHours\Models\ResearchRecord;
use Modules\Subject\Models\Subject;
use Modules\TrainingSchedule\Models\TrainingSchedule;
use Modules\Unit\Models\Unit;

class TrashRegistry
{
    /**
     * @return array<string, array{
     *     model: class-string<Model>,
     *     label: string,
     *     icon: string,
     *     title: callable(Model): string,
     *     identifier: callable(Model): ?string,
     *     summary: callable(Model): array<string, mixed>,
     *     match?: callable(Model): bool
     * }>
     */
    public static function definitions(): array
    {
        return [
            'students' => [
                'model' => User::class,
                'label' => 'Học viên',
                'icon' => 'bi-mortarboard',
                'match' => fn (Model $m) => $m instanceof User && $m->user_type === 'student',
                'title' => fn (Model $m) => (string) ($m->name ?? 'Học viên #'.$m->getKey()),
                'identifier' => fn (Model $m) => $m->code ?? $m->email ?? null,
                'summary' => fn (Model $m) => array_filter([
                    'Mã' => $m->code,
                    'Họ tên' => $m->name,
                    'Email' => $m->email,
                    'Lớp ID' => $m->class_id,
                    'Trạng thái' => $m->status,
                ], fn ($v) => $v !== null && $v !== ''),
            ],
            'users' => [
                'model' => User::class,
                'label' => 'Người dùng',
                'icon' => 'bi-person',
                'match' => fn (Model $m) => $m instanceof User && $m->user_type !== 'student',
                'title' => fn (Model $m) => (string) ($m->name ?? 'Người dùng #'.$m->getKey()),
                'identifier' => fn (Model $m) => $m->code ?? $m->email ?? null,
                'summary' => fn (Model $m) => array_filter([
                    'Mã' => $m->code,
                    'Họ tên' => $m->name,
                    'Email' => $m->email,
                    'Loại' => $m->user_type,
                    'Đơn vị ID' => $m->unit_id,
                    'Trạng thái' => $m->status,
                ], fn ($v) => $v !== null && $v !== ''),
            ],
            'training-schedules' => [
                'model' => TrainingSchedule::class,
                'label' => 'Lịch đào tạo',
                'icon' => 'bi-calendar3',
                'title' => fn (Model $m) => (string) ($m->name ?? 'Lịch #'.$m->getKey()),
                'identifier' => fn (Model $m) => $m->code ?? null,
                'summary' => fn (Model $m) => array_filter([
                    'Mã' => $m->code,
                    'Tên' => $m->name,
                    'Lớp ID' => $m->class_id,
                    'Năm học' => $m->academic_year,
                    'Học kỳ' => $m->semester,
                ], fn ($v) => $v !== null && $v !== ''),
            ],
            'subjects' => [
                'model' => Subject::class,
                'label' => 'Môn học',
                'icon' => 'bi-journal-text',
                'title' => fn (Model $m) => (string) ($m->name ?? 'Môn học #'.$m->getKey()),
                'identifier' => fn (Model $m) => $m->code ?? null,
                'summary' => fn (Model $m) => array_filter([
                    'Mã' => $m->code,
                    'Tên' => $m->name,
                    'Tín chỉ' => $m->credits,
                    'Ngành ID' => $m->specialization_id,
                    'Mô tả' => $m->description,
                ], fn ($v) => $v !== null && $v !== ''),
            ],
            'specializations' => [
                'model' => Specialization::class,
                'label' => 'Ngành đào tạo',
                'icon' => 'bi-mortarboard',
                'title' => fn (Model $m) => (string) ($m->name ?? 'Ngành #'.$m->getKey()),
                'identifier' => fn (Model $m) => $m->major_code ?? $m->code ?? null,
                'summary' => fn (Model $m) => array_filter([
                    'Mã ngành' => $m->major_code ?? null,
                    'Mã số nội bộ' => $m->code,
                    'Tên' => $m->name,
                    'Cấp độ' => $m->level,
                    'Mô tả' => $m->description,
                ], fn ($v) => $v !== null && $v !== ''),
            ],
            'instructors' => [
                'model' => Instructor::class,
                'label' => 'Giảng viên',
                'icon' => 'bi-person-badge',
                'title' => fn (Model $m) => (string) ($m->name ?? 'GV #'.$m->getKey()),
                'identifier' => fn (Model $m) => $m->code ?? null,
                'summary' => fn (Model $m) => array_filter([
                    'Mã' => $m->code,
                    'Họ tên' => $m->name,
                    'Email' => $m->email ?? null,
                    'Đơn vị ID' => $m->unit_id,
                ], fn ($v) => $v !== null && $v !== ''),
            ],
            'units' => [
                'model' => Unit::class,
                'label' => 'Đơn vị',
                'icon' => 'bi-building',
                'title' => fn (Model $m) => (string) ($m->name ?? 'Đơn vị #'.$m->getKey()),
                'identifier' => fn (Model $m) => $m->code ?? null,
                'summary' => fn (Model $m) => array_filter([
                    'Mã' => $m->code,
                    'Tên' => $m->name,
                    'Đơn vị cha ID' => $m->parent_id ?? null,
                ], fn ($v) => $v !== null && $v !== ''),
            ],
            'buildings' => [
                'model' => Building::class,
                'label' => 'Giảng đường / tòa nhà',
                'icon' => 'bi-hospital',
                'title' => fn (Model $m) => (string) ($m->name ?? 'Tòa #'.$m->getKey()),
                'identifier' => fn (Model $m) => $m->code ?? null,
                'summary' => fn (Model $m) => array_filter([
                    'Mã' => $m->code,
                    'Tên' => $m->name,
                ], fn ($v) => $v !== null && $v !== ''),
            ],
            'classrooms' => [
                'model' => Classroom::class,
                'label' => 'Phòng học',
                'icon' => 'bi-door-open',
                'title' => fn (Model $m) => (string) ($m->name ?? 'Phòng #'.$m->getKey()),
                'identifier' => fn (Model $m) => $m->code ?? null,
                'summary' => fn (Model $m) => array_filter([
                    'Mã' => $m->code ?? null,
                    'Tên' => $m->name,
                    'Tòa ID' => $m->building_id ?? null,
                    'Sức chứa' => $m->capacity ?? null,
                ], fn ($v) => $v !== null && $v !== ''),
            ],
            'classes' => [
                'model' => ClassModel::class,
                'label' => 'Lớp học',
                'icon' => 'bi-people',
                'title' => fn (Model $m) => (string) ($m->name ?? 'Lớp #'.$m->getKey()),
                'identifier' => fn (Model $m) => $m->code ?? null,
                'summary' => fn (Model $m) => array_filter([
                    'Mã' => $m->code,
                    'Tên' => $m->name,
                    'Ngành ID' => $m->specialization_id,
                    'GV chủ nhiệm ID' => $m->instructor_id,
                ], fn ($v) => $v !== null && $v !== ''),
            ],
            'object-types' => [
                'model' => ObjectType::class,
                'label' => 'Đối tượng (Giờ chuẩn)',
                'icon' => 'bi-person-vcard',
                'title' => fn (Model $m) => (string) ($m->name ?? 'Đối tượng #'.$m->getKey()),
                'identifier' => fn (Model $m) => $m->code ?? null,
                'summary' => fn (Model $m) => array_filter([
                    'Mã' => $m->code ?? null,
                    'Tên' => $m->name,
                ], fn ($v) => $v !== null && $v !== ''),
            ],
            'positions' => [
                'model' => Position::class,
                'label' => 'Chức danh (Giờ chuẩn)',
                'icon' => 'bi-award',
                'title' => fn (Model $m) => (string) ($m->name ?? 'Chức danh #'.$m->getKey()),
                'identifier' => fn (Model $m) => $m->code ?? null,
                'summary' => fn (Model $m) => array_filter([
                    'Mã' => $m->code ?? null,
                    'Tên' => $m->name,
                ], fn ($v) => $v !== null && $v !== ''),
            ],
            // hour-norms / research-norms deprecated — norms live on object-types
            'hour-norms' => [
                'model' => HourNorm::class,
                'label' => 'Định mức giờ chuẩn',
                'icon' => 'bi-speedometer2',
                'title' => fn (Model $m) => 'Định mức GC #'.$m->getKey().' ('.($m->academic_year ?? '—').')',
                'identifier' => fn (Model $m) => (string) $m->getKey(),
                'summary' => fn (Model $m) => array_filter([
                    'Năm học' => $m->academic_year ?? null,
                    'Giờ chuẩn' => $m->standard_hours ?? null,
                    'Đối tượng ID' => $m->object_type_id ?? null,
                    'Chức danh ID' => $m->position_id ?? null,
                ], fn ($v) => $v !== null && $v !== ''),
            ],
            'research-norms' => [
                'model' => ResearchNorm::class,
                'label' => 'Định mức NCKH',
                'icon' => 'bi-clipboard-data',
                'title' => fn (Model $m) => (string) ($m->name ?? 'ĐM NCKH #'.$m->getKey()),
                'identifier' => fn (Model $m) => (string) $m->getKey(),
                'summary' => fn (Model $m) => array_filter([
                    'Tên' => $m->name ?? null,
                    'Giờ' => $m->hours ?? $m->research_hours ?? null,
                ], fn ($v) => $v !== null && $v !== ''),
            ],
            'conversion-categories' => [
                'model' => ConversionCategory::class,
                'label' => 'Danh mục HĐ chuyên môn',
                'icon' => 'bi-list-check',
                'title' => fn (Model $m) => (string) ($m->name ?? 'Danh mục #'.$m->getKey()),
                'identifier' => fn (Model $m) => $m->code ?? (string) $m->getKey(),
                'summary' => fn (Model $m) => array_filter([
                    'Mã' => $m->code ?? null,
                    'Tên' => $m->name,
                ], fn ($v) => $v !== null && $v !== ''),
            ],
            'research-categories' => [
                'model' => ResearchCategory::class,
                'label' => 'Danh mục NCKH',
                'icon' => 'bi-bookmark-star',
                'title' => fn (Model $m) => (string) ($m->name ?? 'DM NCKH #'.$m->getKey()),
                'identifier' => fn (Model $m) => $m->code ?? (string) $m->getKey(),
                'summary' => fn (Model $m) => array_filter([
                    'Mã' => $m->code ?? null,
                    'Tên' => $m->name,
                ], fn ($v) => $v !== null && $v !== ''),
            ],
            'conversion-records' => [
                'model' => ConversionRecord::class,
                'label' => 'Kê khai HĐ chuyên môn',
                'icon' => 'bi-file-earmark-text',
                'title' => fn (Model $m) => (string) ($m->activity_name ?? 'HĐ CM #'.$m->getKey()),
                'identifier' => fn (Model $m) => (string) $m->getKey(),
                'summary' => fn (Model $m) => array_filter([
                    'Hoạt động' => $m->activity_name,
                    'GV ID' => $m->instructor_id,
                    'Giờ QĐ' => $m->converted_hours,
                    'Trạng thái' => $m->status,
                ], fn ($v) => $v !== null && $v !== ''),
            ],
            'research-records' => [
                'model' => ResearchRecord::class,
                'label' => 'Kê khai NCKH',
                'icon' => 'bi-lightbulb',
                'title' => fn (Model $m) => (string) ($m->product_name ?? 'NCKH #'.$m->getKey()),
                'identifier' => fn (Model $m) => (string) $m->getKey(),
                'summary' => fn (Model $m) => array_filter([
                    'Sản phẩm' => $m->product_name,
                    'GV ID' => $m->instructor_id,
                    'Giờ QĐ' => $m->converted_hours,
                    'Trạng thái' => $m->status,
                ], fn ($v) => $v !== null && $v !== ''),
            ],
            'hour-exchanges' => [
                'model' => HourExchangeRecord::class,
                'label' => 'Quy đổi giờ',
                'icon' => 'bi-arrow-left-right',
                'title' => fn (Model $m) => 'Quy đổi giờ #'.$m->getKey().' ('.($m->academic_year ?? '—').')',
                'identifier' => fn (Model $m) => (string) $m->getKey(),
                'summary' => fn (Model $m) => array_filter([
                    'GV ID' => $m->instructor_id ?? null,
                    'Năm học' => $m->academic_year ?? null,
                    'Hướng' => $m->direction ?? null,
                    'Giờ nguồn' => $m->source_hours ?? null,
                    'Giờ đích' => $m->target_hours ?? null,
                ], fn ($v) => $v !== null && $v !== ''),
            ],
        ];
    }

    public static function keys(): array
    {
        return array_keys(self::definitions());
    }

    public static function options(): array
    {
        return collect(self::definitions())
            ->mapWithKeys(fn (array $def, string $key) => [$key => $def['label']])
            ->all();
    }

    public static function find(string $key): ?array
    {
        return self::definitions()[$key] ?? null;
    }

    public static function findByModel(Model $model): ?array
    {
        $fallback = null;

        foreach (self::definitions() as $key => $def) {
            if (! ($model instanceof $def['model'])) {
                continue;
            }

            if (isset($def['match']) && is_callable($def['match'])) {
                if (($def['match'])($model)) {
                    return array_merge($def, ['key' => $key]);
                }

                continue;
            }

            // Prefer definitions with match when same model is registered multiple times
            $fallback ??= array_merge($def, ['key' => $key]);
        }

        return $fallback;
    }

    public static function keyByModelClass(string $class): ?string
    {
        foreach (self::definitions() as $key => $def) {
            if ($def['model'] === $class) {
                return $key;
            }
        }

        return null;
    }

    /**
     * @return list<class-string<Model>>
     */
    public static function modelClasses(): array
    {
        return array_values(array_unique(array_map(
            fn (array $def) => $def['model'],
            self::definitions()
        )));
    }

    public static function usesSoftDeletes(string $modelClass): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($modelClass), true);
    }
}
