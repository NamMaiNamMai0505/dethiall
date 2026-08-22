<?php

namespace App\Support;

use App\Jobs\SendSystemNotificationEmail;
use App\Models\SystemNotification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Modules\TrainingSchedule\Models\TrainingSchedule;

class SystemNotifier
{
    public const TYPE_STUDENT_SCHEDULE = 'student_schedule';

    public const TYPE_STUDENT_EXAM = 'student_exam';

    public const TYPE_INSTRUCTOR_SCHEDULE = 'instructor_schedule';

    public const TYPE_INSTRUCTOR_PROCTOR = 'instructor_proctor';

    public const TYPE_SCHOOL_EVENT = 'school_event';

    public const TYPE_SYSTEM_CHANGE = 'system_change';

    private const MODULE_LABELS = [
        'users' => 'Người dùng nội bộ',
        'students' => 'Học viên',
        'instructors' => 'Giảng viên',
        'classes' => 'Lớp học',
        'subjects' => 'Môn học',
        'specializations' => 'Ngành đào tạo',
        'units' => 'Đơn vị',
        'buildings' => 'Giảng đường',
        'classrooms' => 'Phòng học',
        'training-schedules' => 'Lịch đào tạo',
        'teaching-assignments' => 'Phân công giảng dạy',
        'schedule-details' => 'Chi tiết lịch học',
        'standard-hours' => 'Giờ chuẩn GV',
        'roles' => 'Vai trò',
        'permissions' => 'Quyền hệ thống',
        'dashboards' => 'Bảng điều khiển',
        'school-events' => 'Sự kiện nhà trường',
    ];

    private const ACTION_LABELS = [
        'store' => 'đã tạo mới',
        'create' => 'đã tạo mới',
        'update' => 'đã cập nhật',
        'edit' => 'đã cập nhật',
        'destroy' => 'đã xóa',
        'delete' => 'đã xóa',
        'approve' => 'đã phê duyệt',
        'reject' => 'đã từ chối',
        'import' => 'đã nhập dữ liệu',
        'export' => 'đã xuất dữ liệu',
        'sync' => 'đã đồng bộ',
        'store-schedule-detail' => 'đã cập nhật lịch học',
        'update-schedule-detail' => 'đã cập nhật lịch học',
        'destroy-schedule-detail' => 'đã xóa lịch học',
    ];

    private const SCHEDULE_MODULES = [
        'training-schedules',
        'schedule-details',
        'scheduledetails',
    ];

    private const SCHOOL_EVENT_MODULES = [
        'school-events',
        'events',
    ];

    private const EXCLUDED_ROUTE_PREFIXES = [
        'notifications.',
        'login',
        'logout',
        'register',
        'password.',
    ];

    public static function fromManagerRequest(Request $request, mixed $response): void
    {
        $actor = $request->user();

        if (! $actor instanceof User || ! self::isManagementActor($actor)) {
            return;
        }

        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }

        if (! self::responseIndicatesSuccess($response)) {
            return;
        }

        $routeName = $request->route()?->getName();
        if (! $routeName || self::isExcludedRoute($routeName)) {
            return;
        }

        $parsed = self::parseRouteName($routeName);
        if ($parsed === null) {
            return;
        }

        $message = "{$actor->name} {$parsed['action_label']} {$parsed['module_label']}";
        $url = self::resolveUrl($request, $parsed['module_key']);

        $schedule = $request->route('trainingSchedule')
            ?? $request->route('training_schedule')
            ?? null;

        if ($schedule instanceof TrainingSchedule && in_array($parsed['module_key'], self::SCHEDULE_MODULES, true)) {
            self::notifyScheduleChange(
                schedule: $schedule,
                actor: $actor,
                action: $parsed['action'],
                title: $parsed['title'],
                message: $message,
                url: $url,
            );

            self::notifyManagers(
                actor: $actor,
                module: $parsed['module'],
                action: $parsed['action'],
                title: $parsed['title'],
                message: $message,
                url: $url,
                type: self::TYPE_SYSTEM_CHANGE,
            );

            return;
        }

        if (in_array($parsed['module_key'], self::SCHEDULE_MODULES, true)) {
            self::notifyScheduleAudience(
                actor: $actor,
                module: $parsed['module'],
                action: $parsed['action'],
                title: $parsed['title'],
                message: $message,
                url: $url,
            );

            self::notifyManagers(
                actor: $actor,
                module: $parsed['module'],
                action: $parsed['action'],
                title: $parsed['title'],
                message: $message,
                url: $url,
                type: self::TYPE_SYSTEM_CHANGE,
            );

            return;
        }

        if (in_array($parsed['module_key'], self::SCHOOL_EVENT_MODULES, true)) {
            self::notifySchoolEvent(
                actor: $actor,
                module: $parsed['module'],
                action: $parsed['action'],
                title: $parsed['title'],
                message: $message,
                url: $url,
            );

            return;
        }

        self::notifyManagers(
            actor: $actor,
            module: $parsed['module'],
            action: $parsed['action'],
            title: $parsed['title'],
            message: $message,
            url: $url,
            type: self::TYPE_SYSTEM_CHANGE,
        );
    }

    public static function notifyScheduleChange(
        TrainingSchedule $schedule,
        User $actor,
        string $action,
        string $title,
        string $message,
        ?string $url = null,
        bool $sendEmail = true,
    ): void {
        $schedule->loadMissing(['scheduleDetails', 'classModel', 'class']);

        $hasExam = $schedule->scheduleDetails
            ->contains(fn ($d) => ($d->lesson_type ?? null) === 'final_exam');

        $studentType = $hasExam ? self::TYPE_STUDENT_EXAM : self::TYPE_STUDENT_SCHEDULE;
        $instructorType = $hasExam ? self::TYPE_INSTRUCTOR_PROCTOR : self::TYPE_INSTRUCTOR_SCHEDULE;

        $classId = $schedule->class_id
            ?? $schedule->class?->id
            ?? $schedule->classModel?->id;

        $studentIds = collect();
        if ($classId) {
            $studentIds = User::query()
                ->students()
                ->where('class_id', $classId)
                ->where('id', '!=', $actor->id)
                ->pluck('id');
        }

        $instructorIdsFromDetails = $schedule->scheduleDetails
            ->pluck('instructor_id')
            ->filter()
            ->unique()
            ->values();

        $instructorUserIds = User::query()
            ->instructors()
            ->whereIn('instructor_id', $instructorIdsFromDetails)
            ->where('id', '!=', $actor->id)
            ->pluck('id');

        if ($studentIds->isNotEmpty()) {
            self::deliver(
                userIds: $studentIds,
                actor: $actor,
                module: 'training-schedules',
                action: $action,
                title: $title,
                message: self::studentMessage($message, $schedule, $hasExam),
                url: $url ?? self::studentScheduleUrl(),
                type: $studentType,
                meta: [
                    'training_schedule_id' => $schedule->id,
                    'class_id' => $classId,
                    'has_exam' => $hasExam,
                ],
                sendEmail: $sendEmail,
            );
        }

        if ($instructorUserIds->isNotEmpty()) {
            self::deliver(
                userIds: $instructorUserIds,
                actor: $actor,
                module: 'training-schedules',
                action: $action,
                title: $title,
                message: self::instructorMessage($message, $schedule, $hasExam),
                url: $url ?? self::instructorScheduleUrl(),
                type: $instructorType,
                meta: [
                    'training_schedule_id' => $schedule->id,
                    'has_exam' => $hasExam,
                ],
                sendEmail: $sendEmail,
            );
        }
    }

    public static function notifyScheduleAudience(
        User $actor,
        string $module,
        string $action,
        string $title,
        string $message,
        ?string $url = null,
        bool $sendEmail = true,
    ): void {
        $studentIds = User::query()
            ->students()
            ->where('id', '!=', $actor->id)
            ->pluck('id');

        $instructorIds = User::query()
            ->instructors()
            ->where('id', '!=', $actor->id)
            ->pluck('id');

        if ($studentIds->isNotEmpty()) {
            self::deliver(
                userIds: $studentIds,
                actor: $actor,
                module: $module,
                action: $action,
                title: $title,
                message: $message,
                url: $url ?? self::studentScheduleUrl(),
                type: self::TYPE_STUDENT_SCHEDULE,
                sendEmail: $sendEmail,
            );
        }

        if ($instructorIds->isNotEmpty()) {
            self::deliver(
                userIds: $instructorIds,
                actor: $actor,
                module: $module,
                action: $action,
                title: $title,
                message: $message,
                url: $url ?? self::instructorScheduleUrl(),
                type: self::TYPE_INSTRUCTOR_SCHEDULE,
                sendEmail: $sendEmail,
            );
        }
    }

    public static function notifySchoolEvent(
        User $actor,
        string $module,
        string $action,
        string $title,
        string $message,
        ?string $url = null,
        bool $sendEmail = true,
    ): void {
        $recipientIds = User::query()
            ->where('id', '!=', $actor->id)
            ->where(function ($q) {
                $q->where('user_type', 'student')
                    ->orWhere(function ($q2) {
                        $q2->where('user_type', 'instructor')->whereNotNull('instructor_id');
                    })
                    ->orWhereHas('roles', function ($q3) {
                        $q3->whereIn('name', ['manager', 'super-admin']);
                    });
            })
            ->pluck('id');

        self::deliver(
            userIds: $recipientIds,
            actor: $actor,
            module: $module,
            action: $action,
            title: $title,
            message: $message,
            url: $url,
            type: self::TYPE_SCHOOL_EVENT,
            sendEmail: $sendEmail,
        );
    }

    public static function notifyManagers(
        User $actor,
        string $module,
        string $action,
        string $title,
        string $message,
        ?string $url = null,
        string $type = self::TYPE_SYSTEM_CHANGE,
        bool $sendEmail = true,
    ): void {
        $recipientIds = User::query()
            ->where('id', '!=', $actor->id)
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['manager', 'super-admin']);
            })
            ->pluck('id');

        self::deliver(
            userIds: $recipientIds,
            actor: $actor,
            module: $module,
            action: $action,
            title: $title,
            message: $message,
            url: $url,
            type: $type,
            sendEmail: $sendEmail,
        );
    }

    public static function broadcast(
        User $actor,
        string $module,
        string $action,
        string $title,
        string $message,
        ?string $url = null,
    ): void {
        $moduleKey = explode('.', $module)[0] ?? $module;

        if (in_array($moduleKey, self::SCHEDULE_MODULES, true)) {
            self::notifyScheduleAudience($actor, $module, $action, $title, $message, $url);

            return;
        }

        if (in_array($moduleKey, self::SCHOOL_EVENT_MODULES, true)) {
            self::notifySchoolEvent($actor, $module, $action, $title, $message, $url);

            return;
        }

        self::notifyManagers($actor, $module, $action, $title, $message, $url);
    }

    /**
     * Core delivery: same title/message/url for web + email.
     *
     * @param  Collection<int, int>|array<int, int>  $userIds
     * @param  array<string, mixed>|null  $meta
     */
    public static function deliver(
        Collection|array $userIds,
        User $actor,
        string $module,
        string $action,
        string $title,
        string $message,
        ?string $url = null,
        string $type = self::TYPE_SYSTEM_CHANGE,
        ?array $meta = null,
        bool $sendEmail = true,
    ): void {
        $ids = collect($userIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0 && $id !== (int) $actor->id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return;
        }

        $now = now();

        foreach ($ids as $userId) {
            try {
                $notification = SystemNotification::query()->create([
                    'user_id' => $userId,
                    'actor_id' => $actor->id,
                    'module' => $module,
                    'action' => $action,
                    'type' => $type,
                    'title' => $title,
                    'message' => $message,
                    'url' => self::toAppRelativeUrl($url),
                    'meta' => $meta,
                    'read_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                if ($sendEmail) {
                    try {
                        SendSystemNotificationEmail::dispatch($notification->id);
                    } catch (\Throwable $mailError) {
                        Log::warning('Failed to send notification email', [
                            'notification_id' => $notification->id,
                            'user_id' => $userId,
                            'error' => $mailError->getMessage(),
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to create system notification', [
                    'user_id' => $userId,
                    'module' => $module,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public static function notifyUser(
        User $recipient,
        User $actor,
        string $module,
        string $action,
        string $title,
        string $message,
        ?string $url = null,
        string $type = self::TYPE_INSTRUCTOR_SCHEDULE,
        ?array $meta = null,
        bool $sendEmail = true,
    ): ?SystemNotification {
        self::deliver(
            userIds: [$recipient->id],
            actor: $actor,
            module: $module,
            action: $action,
            title: $title,
            message: $message,
            url: $url,
            type: $type,
            meta: $meta,
            sendEmail: $sendEmail,
        );

        return SystemNotification::query()
            ->where('user_id', $recipient->id)
            ->where('actor_id', $actor->id)
            ->where('title', $title)
            ->latest('id')
            ->first();
    }

    public static function isManagementActor(User $user): bool
    {
        return $user->isManager()
            || $user->isSystemManager()
            || $user->isTrainingOfficeManager()
            || $user->isFacultyScheduleManager()
            || $user->isSuperAdmin();
    }

    private static function studentMessage(string $base, TrainingSchedule $schedule, bool $hasExam): string
    {
        $className = $schedule->class?->name
            ?? $schedule->classModel?->name
            ?? $schedule->class_code
            ?? 'lớp của bạn';

        $kind = $hasExam ? 'lịch học / lịch thi' : 'lịch học';

        return "{$base}. Cập nhật {$kind} cho {$className} ({$schedule->name}). Vui lòng kiểm tra lịch học trên hệ thống.";
    }

    private static function instructorMessage(string $base, TrainingSchedule $schedule, bool $hasExam): string
    {
        $kind = $hasExam ? 'lịch giảng / lịch coi thi' : 'lịch giảng';

        return "{$base}. Có cập nhật {$kind} liên quan lịch \"{$schedule->name}\". Vui lòng kiểm tra lịch giảng trên hệ thống.";
    }

    private static function studentScheduleUrl(): ?string
    {
        return Route::has('student-schedule.index')
            ? route('student-schedule.index', [], false)
            : null;
    }

    private static function instructorScheduleUrl(): ?string
    {
        return Route::has('instructor-schedule.index')
            ? route('instructor-schedule.index', [], false)
            : null;
    }

    private static function isExcludedRoute(string $routeName): bool
    {
        foreach (self::EXCLUDED_ROUTE_PREFIXES as $prefix) {
            if (str_starts_with($routeName, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private static function responseIndicatesSuccess(mixed $response): bool
    {
        if (! is_object($response) || ! method_exists($response, 'getStatusCode')) {
            return false;
        }

        $status = $response->getStatusCode();

        return ($status >= 200 && $status < 300) || ($status >= 300 && $status < 400);
    }

    /**
     * @return array{module: string, module_key: string, action: string, title: string, module_label: string, action_label: string}|null
     */
    private static function parseRouteName(string $routeName): ?array
    {
        $segments = explode('.', $routeName);
        if (count($segments) < 2) {
            return null;
        }

        $actionSegment = (string) array_pop($segments);
        $moduleKey = (string) ($segments[0] ?? 'system');
        $module = implode('.', $segments);

        if (in_array($actionSegment, ['index', 'show', 'create', 'edit'], true)) {
            return null;
        }

        $moduleLabel = self::MODULE_LABELS[$moduleKey] ?? Str::headline(str_replace(['-', '.'], ' ', $moduleKey));
        $actionLabel = self::ACTION_LABELS[$actionSegment] ?? 'đã thay đổi';

        $title = match (true) {
            in_array($moduleKey, self::SCHEDULE_MODULES, true) => "Cập nhật {$moduleLabel}",
            in_array($moduleKey, self::SCHOOL_EVENT_MODULES, true) => "Sự kiện: {$moduleLabel}",
            default => "Thay đổi hệ thống: {$moduleLabel}",
        };

        return [
            'module' => $module,
            'module_key' => $moduleKey,
            'action' => $actionSegment,
            'title' => $title,
            'module_label' => $moduleLabel,
            'action_label' => $actionLabel,
        ];
    }

    private static function resolveUrl(Request $request, string $moduleKey): ?string
    {
        $referer = $request->headers->get('referer');
        if (is_string($referer) && $referer !== '') {
            return self::toAppRelativeUrl($referer);
        }

        $indexRoute = "{$moduleKey}.index";
        if (Route::has($indexRoute)) {
            return self::toAppRelativeUrl(route($indexRoute, [], false));
        }

        return self::toAppRelativeUrl($request->getRequestUri());
    }

    /**
     * Store relative app paths so clients never jump to a wrong host (e.g. APP_URL=localhost).
     */
    public static function toAppRelativeUrl(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $url = trim($url);

        if (str_starts_with($url, '/')) {
            return $url;
        }

        $appUrl = rtrim((string) config('app.url'), '/');
        if ($appUrl !== '' && str_starts_with($url, $appUrl)) {
            $path = substr($url, strlen($appUrl));

            return $path === '' ? '/' : $path;
        }

        $parts = parse_url($url);
        if (is_array($parts) && isset($parts['path'])) {
            $path = $parts['path'] !== '' ? $parts['path'] : '/';
            if (! empty($parts['query'])) {
                $path .= '?'.$parts['query'];
            }
            if (! empty($parts['fragment'])) {
                $path .= '#'.$parts['fragment'];
            }

            return $path;
        }

        return $url;
    }
}
