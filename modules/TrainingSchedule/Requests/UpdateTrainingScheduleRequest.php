<?php

namespace Modules\TrainingSchedule\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTrainingScheduleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Add your authorization logic here
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $scheduleId = $this->route('training_schedule')
            ? $this->route('training_schedule')->id
            : $this->route('trainingSchedule');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('training_schedules', 'name')
                    ->ignore($scheduleId)
                    ->whereNull('deleted_at')
            ],
            'code' => [
                'nullable',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('training_schedules', 'code')
                    ->ignore($scheduleId)
                    ->whereNull('deleted_at')
            ],
            'abbreviation' => [
                'nullable',
                'string',
                'max:20'
            ],

            'start_date' => [
                'required',
                'date'
            ],
            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date'
            ],
            'specialization_id' => [
                'required',
                'exists:specializations,id'
            ],
            'class_code' => [
                'nullable',
                'exists:classes,code'
            ],
            'classroom_id' => [
                'nullable',
                'exists:classrooms,id'
            ],
            'academic_year' => [
                'required',
                'string',
                'max:20',
                'exists:academic_years,code'
            ],
            'semester' => [
                'required',
                'in:semester_1,semester_2,summer'
            ],
            'schedule_type' => [
                'nullable',
                'string',
                'in:course'
            ],
            'status' => [
                'nullable',
                'string',
                'in:draft,active'
            ],
            'weekly_schedule' => [
                'nullable',
                'array'
            ],
            'weeks' => [
                'nullable',
                'array'
            ],
            'weeks.*.start_date' => [
                'nullable',
                'date'
            ],
            'weeks.*.end_date' => [
                'nullable',
                'date',
                'after_or_equal:weeks.*.start_date'
            ],
            'weeks.*.content' => [
                'nullable',
                'string',
                'max:1000'
            ],
            'weeks.*.instructor_id' => [
                'nullable',
                'exists:users,id'
            ],
            'weeks.*.location' => [
                'nullable',
                'string',
                'max:255'
            ],
            // Daily schedule validation
            'weeks.*.days' => [
                'nullable',
                'array'
            ],
            'weeks.*.days.*.date' => [
                'nullable',
                'date'
            ],
            'weeks.*.days.*.content' => [
                'nullable',
                'string',
                'max:1000'
            ],
            'weeks.*.days.*.instructor_id' => [
                'nullable',
                'exists:users,id'
            ],
            'weeks.*.days.*.location' => [
                'nullable',
                'string',
                'max:255'
            ],
            'is_active' => [
                'boolean'
            ]
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tên lịch đào tạo là bắt buộc.',
            'name.string' => 'Tên lịch đào tạo phải là chuỗi ký tự.',
            'name.max' => 'Tên lịch đào tạo không được vượt quá :max ký tự.',
            'name.unique' => 'Tên lịch đào tạo này đã tồn tại.',

            'code.string' => 'Mã lịch đào tạo phải là chuỗi ký tự.',
            'code.max' => 'Mã lịch đào tạo không được vượt quá :max ký tự.',
            'code.alpha_dash' => 'Mã lịch đào tạo chỉ được chứa chữ cái, số, dấu gạch ngang và gạch dưới.',
            'code.unique' => 'Mã lịch đào tạo này đã tồn tại.',

            'start_date.required' => 'Ngày bắt đầu là bắt buộc.',
            'start_date.date' => 'Ngày bắt đầu không hợp lệ.',

            'end_date.required' => 'Ngày kết thúc là bắt buộc.',
            'end_date.date' => 'Ngày kết thúc không hợp lệ.',
            'end_date.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',

            'specialization_id.required' => 'Ngành đào tạo là bắt buộc.',
            'specialization_id.exists' => 'Ngành đào tạo được chọn không tồn tại.',
            'class_code.exists' => 'Lớp học được chọn không tồn tại.',

            'classroom_id.exists' => 'Giảng đường được chọn không tồn tại.',

            'academic_year.required' => 'Năm học là bắt buộc.',
            'academic_year.string' => 'Năm học phải là chuỗi ký tự.',
            'academic_year.max' => 'Năm học không được vượt quá :max ký tự.',

            'semester.required' => 'Học kỳ là bắt buộc.',
            'semester.in' => 'Học kỳ không hợp lệ.',

            'schedule_type.in' => 'Loại lịch đào tạo không hợp lệ.',
            'status.in' => 'Trạng thái không hợp lệ.',

            'weekly_schedule.array' => 'Dữ liệu tuần phải là một mảng.',
            'weeks.array' => 'Dữ liệu tuần phải là một mảng.',
            'weeks.*.start_date.date' => 'Ngày bắt đầu tuần không hợp lệ.',
            'weeks.*.end_date.date' => 'Ngày kết thúc tuần không hợp lệ.',
            'weeks.*.end_date.after_or_equal' => 'Ngày kết thúc tuần phải sau hoặc bằng ngày bắt đầu.',
            'weeks.*.content.string' => 'Nội dung tuần phải là chuỗi ký tự.',
            'weeks.*.content.max' => 'Nội dung tuần không được vượt quá :max ký tự.',
            'weeks.*.instructor_id.exists' => 'Giảng viên được chọn không tồn tại.',
            'weeks.*.location.string' => 'Địa điểm phải là chuỗi ký tự.',
            'weeks.*.location.max' => 'Địa điểm không được vượt quá :max ký tự.',

            // Daily schedule validation messages
            'weeks.*.days.array' => 'Dữ liệu ngày phải là một mảng.',
            'weeks.*.days.*.date.date' => 'Ngày không hợp lệ.',
            'weeks.*.days.*.content.string' => 'Nội dung ngày phải là chuỗi ký tự.',
            'weeks.*.days.*.content.max' => 'Nội dung ngày không được vượt quá :max ký tự.',
            'weeks.*.days.*.instructor_id.exists' => 'Giảng viên được chọn không tồn tại.',
            'weeks.*.days.*.location.string' => 'Địa điểm ngày phải là chuỗi ký tự.',
            'weeks.*.days.*.location.max' => 'Địa điểm ngày không được vượt quá :max ký tự.',

            'is_active.boolean' => 'Trạng thái hoạt động phải là true hoặc false.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'tên lịch đào tạo',
            'code' => 'mã lịch đào tạo',
            'start_date' => 'ngày bắt đầu',
            'end_date' => 'ngày kết thúc',
            'specialization_id' => 'ngành đào tạo',
            'class_code' => 'lớp học',
            'classroom_id' => 'giảng đường',
            'academic_year' => 'năm học',
            'semester' => 'học kỳ'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        $this->merge([
            'is_active' => $this->has('is_active') ? (bool) $this->is_active : true,
        ]);



        // Clean up code
        if ($this->has('code') && !empty($this->code)) {
            $this->merge([
                'code' => strtoupper(trim($this->code))
            ]);
        }
    }
}
