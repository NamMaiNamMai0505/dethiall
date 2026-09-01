<?php

namespace Modules\TrainingSchedule\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTrainingScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Thêm logic phân quyền nếu cần
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:training_schedules,name,NULL,id,deleted_at,NULL',
            ],
            'code' => [
                'nullable',
                'string',
                'max:50',
                'alpha_dash',
                'unique:training_schedules,code,NULL,id,deleted_at,NULL',
            ],
            'abbreviation' => [
                'nullable',
                'string',
                'max:20',
            ],
            'start_date' => [
                'required',
                'date',
            ],
            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
            ],
            'is_active' => [
                'boolean',
            ],
            'specialization_id' => [
                'required',
                'exists:specializations,id',
            ],
            'class_code' => [
                'required',
                Rule::exists('classes', 'code')->where(fn ($query) =>
                    $query->where('specialization_id', (int) $this->input('specialization_id'))
                ),
            ],
            'classroom_id' => [
                'nullable',
                'exists:classrooms,id',
            ],
            'academic_year' => [
                'required',
                'string',
                'max:20',
                'exists:academic_years,code',
            ],
            'semester' => [
                'required',
                'in:semester_1,semester_2,summer',
            ],
        ];
    }

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

            'class_code.required' => 'Hãy chọn lớp học!',

            'academic_year.required' => 'Năm học là bắt buộc.',
            'academic_year.string' => 'Năm học phải là chuỗi ký tự.',
            'academic_year.max' => 'Năm học không được vượt quá :max ký tự.',

            'semester.required' => 'Học kỳ là bắt buộc.',
            'semester.in' => 'Học kỳ không hợp lệ.',

            'is_active.boolean' => 'Trạng thái hoạt động phải là true hoặc false.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'tên lịch đào tạo',
            'code' => 'mã lịch đào tạo',
            'start_date' => 'ngày bắt đầu',
            'end_date' => 'ngày kết thúc',
            'specialization_id' => 'ngành đào tạo',
            'class_id' => 'lớp học',
            'classroom_id' => 'giảng đường',
            'academic_year' => 'năm học',
            'semester' => 'học kỳ',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->has('is_active') ? (bool)$this->is_active : true,
        ]);

        if ($this->has('code') && !empty($this->code)) {
            $this->merge([
                'code' => strtoupper(trim($this->code)),
            ]);
        }

        if (!$this->has('status') || empty($this->status)) {
            $this->merge(['status' => 'draft']);
        }

        if (!$this->has('schedule_type') || empty($this->schedule_type)) {
            $this->merge(['schedule_type' => 'course']);
        }
    }
}
