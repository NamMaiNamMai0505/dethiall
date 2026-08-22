<?php

namespace Modules\User\Support;

use App\Support\ApplicationRegistry;
use Illuminate\Http\Request;

/**
 * Chuẩn hóa dữ liệu ma trận phân quyền gửi lên từ form vai trò.
 *
 * Form gửi `abilities[<ứng dụng>][] = <hành động>`; chỉ giữ lại ứng dụng và hành
 * động thật sự có trong ApplicationRegistry, đồng thời tự bổ sung "Xem" khi vai
 * trò được cấp thao tác ghi — thiếu Xem thì không màn hình nào mở được.
 */
final class RoleMatrixInput
{
    /**
     * @return array{abilities: array<string, list<string>>, extra_permissions: list<int>}
     */
    public static function normalize(Request $request): array
    {
        $applications = ApplicationRegistry::applications();
        $abilities = $request->input('abilities', []);
        $clean = [];

        if (is_array($abilities)) {
            foreach ($abilities as $applicationKey => $actions) {
                $applicationKey = (string) $applicationKey;
                if (! isset($applications[$applicationKey]) || ! is_array($actions)) {
                    continue;
                }

                $available = $applications[$applicationKey]['actions'];
                $selected = [];

                foreach ($actions as $action) {
                    $action = (string) $action;
                    if (isset($available[$action])) {
                        $selected[$action] = true;
                    }
                }

                if ($selected === []) {
                    continue;
                }

                // Thao tác ghi luôn kèm quyền Xem của cùng ứng dụng.
                if (isset($available[ApplicationRegistry::ACTION_VIEW])) {
                    $selected[ApplicationRegistry::ACTION_VIEW] = true;
                }

                $clean[$applicationKey] = array_keys($selected);
            }
        }

        $extra = $request->input('extra_permissions', []);
        $extraIds = is_array($extra)
            ? array_values(array_unique(array_filter(
                array_map(static fn ($id) => (int) $id, $extra),
                static fn (int $id) => $id > 0
            )))
            : [];

        return [
            'abilities' => $clean,
            'extra_permissions' => $extraIds,
        ];
    }
}
