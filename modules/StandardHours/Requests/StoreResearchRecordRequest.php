<?php

namespace Modules\StandardHours\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\StandardHours\Models\ResearchRecord;
use Modules\StandardHours\Support\InstructorScope;

class StoreResearchRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'instructor_id' => ['required', 'exists:instructors,id'],
            'research_category_id' => ['required', 'exists:research_categories,id'],
            'product_name' => ['required', 'string', 'max:255'],
            'publication_date' => ['nullable', 'date'],
            'publication_place' => ['nullable', 'string', 'max:255'],
            'acceptance_date' => ['required', 'date'],
            'member_count' => ['required', 'integer', 'min:1', 'max:99'],
            'participation_type' => [
                'required',
                'in:'.ResearchRecord::PARTICIPATION_LEAD.','.ResearchRecord::PARTICIPATION_MEMBER,
            ],
            'contribution_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'duration_years' => ['required', 'integer', 'min:1', 'max:20'],
            'converted_hours' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'hours_adjustment_note' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string'],
            'evidence' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
            'status' => ['sometimes', 'in:'.ResearchRecord::STATUS_DRAFT.','.ResearchRecord::STATUS_SUBMITTED],
        ];
    }

    public function attributes(): array
    {
        return [
            'instructor_id' => 'Giảng viên kê khai',
            'research_category_id' => 'Danh mục NCKH',
            'product_name' => 'Tên sản phẩm',
            'publication_date' => 'Ngày công bố',
            'publication_place' => 'Nơi xuất bản',
            'acceptance_date' => 'Ngày nghiệm thu',
            'member_count' => 'Số thành viên tham gia',
            'participation_type' => 'Vai trò/chức danh',
            'contribution_percent' => 'Tỷ lệ đóng góp',
            'duration_years' => 'Số năm thực hiện',
            'converted_hours' => 'Số giờ kê khai',
            'hours_adjustment_note' => 'Giải trình điều chỉnh giờ',
            'notes' => 'Ghi chú',
            'evidence' => 'Minh chứng',
        ];
    }

    protected function prepareForValidation(): void
    {
        $values = [
            'member_count' => $this->input('member_count', 1),
            'duration_years' => $this->input('duration_years', 1),
            'contribution_percent' => $this->filled('contribution_percent')
                ? $this->input('contribution_percent')
                : null,
            'converted_hours' => $this->filled('converted_hours')
                ? $this->input('converted_hours')
                : null,
        ];

        if ($instructorId = InstructorScope::instructorId()) {
            $values['instructor_id'] = $instructorId;
        }

        if ($this->isMethod('post') && ! $this->has('status')) {
            $values['status'] = ResearchRecord::STATUS_DRAFT;
        }

        $this->merge($values);
    }
}
