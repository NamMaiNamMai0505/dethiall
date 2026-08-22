<?php

return [
    App\Providers\AppServiceProvider::class,
    Kaibatech\ViettelCloudS3\ViettelCloudS3ServiceProvider::class,

    // Module Service Providers
    Modules\Authentication\Providers\AuthenticationServiceProvider::class,
    Modules\Dashboard\Providers\DashboardServiceProvider::class,
    Modules\Home\Providers\HomeServiceProvider::class,
    Modules\Instructor\Providers\InstructorServiceProvider::class,
    Modules\Specialization\Providers\SpecializationServiceProvider::class,
    Modules\Subject\Providers\SubjectServiceProvider::class,
    Modules\TrainingSchedule\Providers\TrainingScheduleServiceProvider::class,
    Modules\Unit\Providers\UnitServiceProvider::class,
    Modules\Class\Providers\ClassServiceProvider::class,
    Modules\Classroom\Providers\ClassroomServiceProvider::class,
    Modules\User\Providers\UserServiceProvider::class,
    Modules\Building\Providers\BuildingServiceProvider::class,
    Modules\TeachingAssignment\Providers\TeachingAssignmentServiceProvider::class,
    Modules\ScheduleDetail\Providers\ScheduleDetailServiceProvider::class,
    Modules\InstructorSchedule\Providers\InstructorScheduleServiceProvider::class,
    Modules\Student\Providers\StudentServiceProvider::class,
    Modules\StudentSchedule\Providers\StudentScheduleServiceProvider::class,
    Modules\StandardHours\Providers\StandardHoursServiceProvider::class,
    Modules\Trash\Providers\TrashServiceProvider::class,
    Modules\Lms\Providers\LmsServiceProvider::class,
    Modules\Grades\Providers\GradesServiceProvider::class,
    Modules\ExportTemplates\Providers\ExportTemplatesServiceProvider::class,
    Modules\SystemSettings\Providers\SystemSettingsServiceProvider::class,
    Modules\DatabaseManagement\Providers\DatabaseManagementServiceProvider::class,
    Modules\EssayExam\Providers\EssayExamServiceProvider::class,
    Modules\ExamOrganization\Providers\ExamOrganizationServiceProvider::class,
    Modules\Inventory\Providers\InventoryServiceProvider::class,
    Modules\LeaveManagement\Providers\LeaveManagementServiceProvider::class,

    // Third-party Service Providers
    Spatie\Permission\PermissionServiceProvider::class,
];
