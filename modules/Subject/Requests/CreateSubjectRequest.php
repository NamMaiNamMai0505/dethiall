<?php

namespace Modules\Subject\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Unit\Models\Unit;

class CreateSubjectRequest extends FormRequest
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
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'code' => [
                'nullable',
                'string',
                'max:50',
                'alpha_dash',
                'unique:subjects,code,NULL,id,deleted_at,NULL',
            ],
            'abbreviation' => [
                'nullable',
                'string',
                'max:50',
            ],
            'faculty_code' => [
                'nullable',
                'string',
                Rule::exists('units', 'faculty_code')->where(
                    fn ($query) => $query->where('functional_type', Unit::FUNCTIONAL_FACULTY)
                ),
            ],
            'absence_limit_percent' => [
                'nullable',
                'integer',
                'min:0',
                'max:100',
            ],
            'color' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^#?[0-9A-Fa-f]{6}$/',
            ],
            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'specialization_id' => [
                'required',
                'integer',
                'exists:specializations,id',
            ],
            'credits' => [
                'required',
                'integer',
                'min:1',
                'max:20',
            ],
            'theory_hours' => [
                'required',
                'integer',
                'min:0',
                'max:500',
            ],
            'practice_hours' => [
                'required',
                'integer',
                'min:0',
                'max:500',
            ],
            'exam_hours' => [
                'nullable',
                'integer',
                'min:0',
                'max:100',
            ],
            'self_study_hours' => [
                'required',
                'integer',
                'min:0',
                'max:500',
            ],
            'level' => [
                'required',
                'string',
                'in:basic,intermediate,advanced',
            ],
            'semester' => [
                'required',
                'string',
                'in:1,2,3,4,5,6,semester_1,semester_2,semester_3,semester_4,semester_5,semester_6,summer',
            ],
            'prerequisites' => [
                'nullable',
                'array',
            ],
            'prerequisites.*' => [
                'string',
                'max:255',
            ],
            'assessment_method' => [
                'required',
                'string',
                'in:exam,assignment,project,combined,multiple_choice_questions',
            ],
            'is_required' => [
                'boolean',
            ],
            'is_active' => [
                'boolean',
            ],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tên môn học là bắt buộc.',
            'name.string' => 'Tên môn học phải là chuỗi ký tự.',
            'name.max' => 'Tên môn học không được vượt quá :max ký tự.',

            'code.string' => 'Mã môn học phải là chuỗi ký tự.',
            'code.max' => 'Mã môn học không được vượt quá :max ký tự.',
            'code.alpha_dash' => 'Mã môn học chỉ được chứa chữ cái, số, dấu gạch ngang và gạch dưới.',
            'code.unique' => 'Mã môn học này đã tồn tại.',

            'description.string' => 'Mô tả phải là chuỗi ký tự.',
            'description.max' => 'Mô tả không được vượt quá :max ký tự.',

            'specialization_id.required' => 'Lớp là bắt buộc.',
            'specialization_id.integer' => 'Lớp không hợp lệ.',
            'specialization_id.exists' => 'Lớp đã chọn không tồn tại.',

            'credits.required' => 'Số tín chỉ là bắt buộc.',
            'credits.integer' => 'Số tín chỉ phải là số nguyên.',
            'credits.min' => 'Số tín chỉ phải ít nhất :min.',
            'credits.max' => 'Số tín chỉ không được vượt quá :max.',

            'theory_hours.required' => 'Số tiết lý thuyết là bắt buộc.',
            'theory_hours.integer' => 'Số tiết lý thuyết phải là số nguyên.',
            'theory_hours.min' => 'Số tiết lý thuyết không được âm.',
            'theory_hours.max' => 'Số tiết lý thuyết không được vượt quá :max.',

            'practice_hours.required' => 'Số tiết thực hành là bắt buộc.',
            'practice_hours.integer' => 'Số tiết thực hành phải là số nguyên.',
            'practice_hours.min' => 'Số tiết thực hành không được âm.',
            'practice_hours.max' => 'Số tiết thực hành không được vượt quá :max.',

            'exam_hours.integer' => 'Số tiết thi phải là số nguyên.',
            'exam_hours.min' => 'Số tiết thi không được âm.',
            'exam_hours.max' => 'Số tiết thi không được vượt quá :max.',

            'self_study_hours.required' => 'Số tiết tự học là bắt buộc.',
            'self_study_hours.integer' => 'Số tiết tự học phải là số nguyên.',
            'self_study_hours.min' => 'Số tiết tự học không được âm.',
            'self_study_hours.max' => 'Số tiết tự học không được vượt quá :max.',

            'level.required' => 'Cấp độ là bắt buộc.',
            'level.in' => 'Cấp độ không hợp lệ.',

            'semester.required' => 'Học kỳ là bắt buộc.',
            'semester.in' => 'Học kỳ không hợp lệ.',

            'prerequisites.array' => 'Môn học tiên quyết phải là một mảng.',
            'prerequisites.*.string' => 'Mỗi môn học tiên quyết phải là chuỗi ký tự.',
            'prerequisites.*.max' => 'Mỗi môn học tiên quyết không được vượt quá :max ký tự.',

            'assessment_method.required' => 'Phương pháp đánh giá là bắt buộc.',
            'assessment_method.in' => 'Phương pháp đánh giá không hợp lệ.',

            'is_required.boolean' => 'Tính chất môn học phải là true hoặc false.',
            'is_active.boolean' => 'Trạng thái hoạt động phải là true hoặc false.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'tên môn học',
            'code' => 'mã môn học',
            'abbreviation' => 'viết tắt',
            'color' => 'màu nhận diện',
            'description' => 'mô tả',
            'semester' => 'học kỳ',
            'specialization_id' => 'lớp',
            'credits' => 'số tín chỉ',
            'theory_hours' => 'số tiết lý thuyết',
            'practice_hours' => 'số tiết thực hành',
            'self_study_hours' => 'số tiết tự học',
            'level' => 'cấp độ',
            'prerequisites' => 'môn học tiên quyết',
            'assessment_method' => 'phương pháp đánh giá',
            'is_required' => 'tính chất môn học',
            'is_active' => 'trạng thái hoạt động',
            'exam_hours' => 'số tiết thi',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        $this->merge([
            'is_required' => $this->has('is_required') ? (bool) $this->is_required : true,
            'is_active' => $this->has('is_active') ? (bool) $this->is_active : true,
        ]);

        // Normalize semester input: accept numeric (1..6), 'semester_x' or 'summer'
        if ($this->has('semester') && ! empty($this->semester)) {
            $sem = trim((string) $this->semester);
            if (preg_match('/^[1-6]$/', $sem)) {
                $sem = 'semester_'.$sem;
            } elseif (preg_match('/^semester_\d+$/', $sem) || $sem === 'summer') {
                // keep as is
            } else {
                $sem = 'semester_1';
            }
            $this->merge(['semester' => $sem]);
        } else {
            $this->merge(['semester' => 'semester_1']);
        }

        // Clean up prerequisites array
        if ($this->has('prerequisites') && is_array($this->prerequisites)) {
            $prerequisites = array_filter($this->prerequisites, function ($item) {
                return ! empty(trim($item));
            });
            $this->merge(['prerequisites' => array_values($prerequisites)]);
        }

        // Clean up code
        if ($this->has('code') && ! empty($this->code)) {
            $this->merge([
                'code' => strtoupper(trim($this->code)),
            ]);
        }

        // Viết tắt: nhập tay hoặc tự lấy chữ cái đầu tên môn (Thuốc thông thường → TTT)
        $name = trim((string) $this->input('name', ''));
        $abbr = trim((string) $this->input('abbreviation', ''));
        if ($abbr === '' && $name !== '') {
            $abbr = \Modules\Subject\Models\Subject::generateAbbreviationFromName($name);
        }
        $this->merge([
            'abbreviation' => $abbr !== '' ? mb_strtoupper($abbr, 'UTF-8') : null,
        ]);

        $normalizedColor = \Modules\Subject\Models\Subject::normalizeColor($this->input('color'));
        if ($normalizedColor === null && $name !== '') {
            $normalizedColor = \Modules\Subject\Models\Subject::defaultColorForKey($name);
        }
        $this->merge(['color' => $normalizedColor]);

        // Convert hours to integers
        foreach (['theory_hours', 'practice_hours', 'self_study_hours', 'credits', 'exam_hours'] as $field) {
            if ($this->has($field)) {
                $this->merge([$field => (int) $this->$field]);
            }
        }
    }
}
