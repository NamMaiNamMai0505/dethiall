<?php

namespace Modules\StandardHours\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\StandardHours\Models\ExternalActivityRecord;
use Modules\StandardHours\Support\InstructorScope;

class StoreExternalActivityRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'instructor_id' => ['required', 'exists:instructors,id'],
            'activity_type' => ['required', Rule::in(array_keys(ExternalActivityRecord::activityTypeOptions()))],
            'activity_name' => ['required', 'string', 'max:255'],
            'activity_details' => ['nullable', 'string', 'max:5000'],
            'from_date' => ['required', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'role_or_position' => ['nullable', 'string', 'max:255'],
            'organizer' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'result' => ['nullable', 'string', 'max:3000'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'evidence' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx', 'max:10240'],
        ];
    }

    public function attributes(): array
    {
        return [
            'instructor_id' => 'Giảng viên',
            'activity_type' => 'Nhóm hoạt động',
            'activity_name' => 'Tên hoạt động',
            'activity_details' => 'Chi tiết hoạt động',
            'from_date' => 'Từ ngày',
            'to_date' => 'Đến ngày',
            'role_or_position' => 'Vai trò / chức trách',
            'organizer' => 'Đơn vị tổ chức',
            'location' => 'Địa điểm',
            'result' => 'Kết quả',
            'notes' => 'Ghi chú',
            'evidence' => 'Minh chứng',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($instructorId = InstructorScope::instructorId()) {
            $this->merge(['instructor_id' => $instructorId]);
        }
    }
}
