<?php

namespace Modules\ScheduleDetail\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\Subject\Models\Subject;
use Modules\ScheduleDetail\Models\ScheduleDetail;

class CreateScheduleDetailRequest extends FormRequest
{
    public function authorize()
    {
        return true; // tuỳ bạn check quyền ở middleware hay ở đây
    }

    public function rules()
    {
        return [
            'training_schedule_id' => ['required', 'exists:training_schedules,id'],
            'date'                => ['required', 'date'],
            'period'              => ['required', 'integer', 'min:1'],
            'subject_id'          => ['required', 'exists:subjects,id'],
            'instructor_id'       => ['required', 'exists:instructors,id'],
            'classroom_id'        => ['required', 'exists:classrooms,id'],
            'lesson_type'         => ['required', 'in:theory,practice,self_study'],
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $subjectId   = $this->input('subject_id');
            $lessonType  = $this->input('lesson_type');

            $subject = Subject::find($subjectId);
            if (!$subject) {
                return;
            }

            // Đếm số buổi đã xếp cho môn + loại này
            $scheduled = ScheduleDetail::where('subject_id', $subjectId)
                ->where('lesson_type', $lessonType)
                ->count();

            // Giới hạn theo quota trong bảng subjects
            $limit = match($lessonType) {
                'theory'      => $subject->theory_hours,
                'practice'    => $subject->practice_hours,
                'self_study'  => $subject->self_study_hours,
            };

            if ($scheduled >= $limit) {
                $validator->errors()->add(
                    'lesson_type',
                    "Môn {$subject->name} đã đủ số tiết {$lessonType} ({$limit}h), không thể thêm nữa."
                );
            }
        });
    }

    public function messages()
    {
        return [
            'training_schedule_id.required' => 'Thiếu thông tin lịch học cha',
            'training_schedule_id.exists'   => 'Lịch học không tồn tại',
            'date.required'                 => 'Ngày học là bắt buộc',
            'period.required'               => 'Tiết học là bắt buộc',
            'subject_id.exists'             => 'Môn học không tồn tại',
            'instructor_id.exists'          => 'Giảng viên không tồn tại',
            'classroom_id.exists'           => 'Phòng học không tồn tại',
            'lesson_type.in'                => 'Loại buổi học không hợp lệ',
        ];
    }
}
