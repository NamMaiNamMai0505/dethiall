<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueInstructorUser implements ValidationRule
{
    protected ?int $exceptUserId;

    public function __construct(?int $exceptUserId = null)
    {
        $this->exceptUserId = $exceptUserId;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        $query = User::where('instructor_id', $value);

        if ($this->exceptUserId) {
            $query->where('id', '!=', $this->exceptUserId);
        }

        if ($query->exists()) {
            $fail('Giáo viên này đã có tài khoản đăng nhập. Vui lòng chọn giáo viên khác.');
        }
    }
}
