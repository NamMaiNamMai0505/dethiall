<?php

namespace Modules\StandardHours\Requests;

use App\Support\ApplicationRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\StandardHours\Models\YearlyResult;

class ReviewYearlyDeclarationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null) {
            return false;
        }

        // Duyệt hồ sơ kê khai: quyền Duyệt của ứng dụng Tính giờ chuẩn hoặc của
        // ứng dụng Hồ sơ kê khai giờ chuẩn — không nhận quyền tổng phân hệ.
        $abilities = array_merge(
            ApplicationRegistry::permissionNamesFor('standard-hours.calculations', ApplicationRegistry::ACTION_APPROVE),
            ApplicationRegistry::permissionNamesFor('standard-hours.declarations', ApplicationRegistry::ACTION_APPROVE)
        );

        foreach ($abilities as $ability) {
            if ($user->can($ability)) {
                return true;
            }
        }

        return false;
    }

    public function rules(): array
    {
        return [
            'declaration_status' => [
                'required',
                Rule::in([
                    YearlyResult::DECLARATION_APPROVED,
                    YearlyResult::DECLARATION_REJECTED,
                ]),
            ],
            'declaration_review_note' => [
                Rule::requiredIf(
                    fn () => $this->input('declaration_status') === YearlyResult::DECLARATION_REJECTED
                ),
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}
