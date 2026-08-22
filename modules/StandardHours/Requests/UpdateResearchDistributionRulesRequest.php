<?php

namespace Modules\StandardHours\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateResearchDistributionRulesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('standard-hours.override-approved') ?? false;
    }

    public function rules(): array
    {
        return [
            'rules' => ['required', 'array'],
            'rules.single.lead' => ['required', 'numeric', 'min:0', 'max:1'],
            'rules.two.lead' => ['required', 'numeric', 'min:0', 'max:1'],
            'rules.two.member' => ['required', 'numeric', 'min:0', 'max:1'],
            'rules.three.lead' => ['required', 'numeric', 'min:0', 'max:1'],
            'rules.three.member' => ['required', 'numeric', 'min:0', 'max:1'],
            'rules.four_plus.lead_fixed' => ['required', 'numeric', 'min:0', 'max:1'],
            'rules.four_plus.use_contribution_percent' => ['sometimes', 'boolean'],
            'rules.four_plus.equal_split_remainder' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $rules = $this->input('rules', []);
        $fourPlus = $rules['four_plus'] ?? [];

        $this->merge([
            'rules' => array_merge($rules, [
                'four_plus' => array_merge($fourPlus, [
                    'use_contribution_percent' => $this->boolean('rules.four_plus.use_contribution_percent'),
                    'equal_split_remainder' => $this->boolean('rules.four_plus.equal_split_remainder'),
                ]),
            ]),
        ]);
    }

    public function attributes(): array
    {
        return [
            'rules.single.lead' => 'Tỷ lệ 1 thành viên',
            'rules.two.lead' => 'Tỷ lệ chủ nhiệm (2 người)',
            'rules.two.member' => 'Tỷ lệ thành viên (2 người)',
            'rules.three.lead' => 'Tỷ lệ chủ nhiệm (3 người)',
            'rules.three.member' => 'Tỷ lệ thành viên (3 người)',
            'rules.four_plus.lead_fixed' => 'Tỷ lệ cố định chủ nhiệm (4+ người)',
        ];
    }
}
