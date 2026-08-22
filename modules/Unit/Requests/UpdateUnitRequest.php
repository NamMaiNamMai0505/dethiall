<?php

namespace Modules\Unit\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Unit\Models\Unit;

class UpdateUnitRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'functional_type' => $this->input('functional_type', Unit::FUNCTIONAL_OTHER),
        ]);
    }

    public function rules()
    {
        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('units')->ignore($this->route('unit'))],
            'name' => ['required', 'string', 'max:255'],
            'abbreviation' => ['nullable', 'string', 'max:50'],
            'parent_id' => [
                'nullable',
                'exists:units,id',
                function ($attribute, $value, $fail) {
                    if ($value == $this->route('unit')->id) {
                        $fail('Đơn vị không thể là đơn vị cấp trên của chính nó.');
                    }
                },
            ],
            'functional_type' => ['required', Rule::in(array_keys(Unit::getFunctionalTypeOptions()))],
            'status' => ['required', 'string', 'in:active,inactive'],
        ];
    }

    public function attributes()
    {
        return [
            'code' => 'Mã đơn vị',
            'name' => 'Tên đơn vị',
            'parent_id' => 'Đơn vị cấp trên',
            'functional_type' => 'Chức năng đơn vị',
            'status' => 'Trạng thái',
        ];
    }

    public function messages()
    {
        return [
            'code.required' => 'Vui lòng nhập mã đơn vị',
            'code.unique' => 'Mã đơn vị đã tồn tại',
            'name.required' => 'Vui lòng nhập tên đơn vị',
            'parent_id.exists' => 'Đơn vị cấp trên không tồn tại',
            'status.required' => 'Vui lòng chọn trạng thái',
        ];
    }
}
