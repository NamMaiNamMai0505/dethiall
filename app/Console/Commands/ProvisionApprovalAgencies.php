<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\ApprovalAgency;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Unit\Models\Unit;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ProvisionApprovalAgencies extends Command
{
    protected $signature = 'approval-agencies:provision
        {--thu-password= : Mật khẩu khởi tạo cho Nguyễn Thị Thu (chỉ dùng khi tạo mới)}
        {--hieu-password= : Mật khẩu khởi tạo cho Phạm Thị Hiếu (chỉ dùng khi tạo mới)}';

    protected $description = 'Tạo/cập nhật tài khoản và role Cơ Quan Phê Duyệt theo mã đơn vị production';

    private const ACCOUNTS = [
        'thu' => [
            'name' => 'Nguyễn Thị Thu',
            'email' => 'thunt@cdhc2.edu.vn',
            'unit_code' => ApprovalAgency::RESEARCH_UNIT_CODE,
            'scope' => 'Duyệt kê khai NCKH',
        ],
        'hieu' => [
            'name' => 'Phạm Thị Hiếu',
            'email' => 'hieupt@cdhc.edu.vn',
            'unit_code' => ApprovalAgency::STANDARD_HOURS_GRADES_UNIT_CODE,
            'scope' => 'Duyệt HĐCM, giờ chuẩn và quản lý/duyệt điểm; không duyệt NCKH',
        ],
    ];

    private const ROLE_PERMISSIONS = [
        'dashboards.index',
        'standard-hours.index',
        'standard-hours.show',
        'standard-hours.approve',
        'standard-hours.view',
        'standard-hours.conversion-records.view',
        'standard-hours.conversion-records.approve',
        'standard-hours.research-records.view',
        'standard-hours.research-records.approve',
        'standard-hours.external-activities.view',
        'standard-hours.external-activities.approve',
        'standard-hours.calculations.view',
        'standard-hours.calculations.approve',
        'standard-hours.reports.view',
        'standard-hours.reports.export',
    ];

    public function handle(): int
    {
        $thuPassword = $this->validatedPasswordOption('thu-password');
        $hieuPassword = $this->validatedPasswordOption('hieu-password');
        if ($thuPassword === false || $hieuPassword === false) {
            return self::FAILURE;
        }

        $passwords = [
            'thu' => $thuPassword,
            'hieu' => $hieuPassword,
        ];

        $units = [];
        foreach (self::ACCOUNTS as $key => $account) {
            $unit = Unit::query()
                ->get(['id', 'code', 'name'])
                ->first(fn (Unit $candidate) => ApprovalAgency::normalizeUnitCode($candidate->code)
                    === ApprovalAgency::normalizeUnitCode($account['unit_code']));

            if (! $unit) {
                $this->error(
                    "Không tìm thấy đơn vị mã {$account['unit_code']}. "
                    .'Chưa thay đổi role hoặc tài khoản nào.'
                );

                return self::FAILURE;
            }

            $units[$key] = $unit;
        }

        $createdCredentials = [];
        $rows = DB::transaction(function () use ($units, $passwords, &$createdCredentials): array {
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $permissions = collect(self::ROLE_PERMISSIONS)
                ->map(fn (string $name) => Permission::firstOrCreate([
                    'name' => $name,
                    'guard_name' => 'web',
                ]));

            $role = Role::firstOrCreate([
                'name' => ApprovalAgency::ROLE,
                'guard_name' => 'web',
            ]);
            $role->syncPermissions($permissions);

            $rows = [];
            foreach (self::ACCOUNTS as $key => $account) {
                [$user, $created, $generatedPassword] = $this->provisionUser(
                    $account,
                    $units[$key],
                    $role,
                    $passwords[$key]
                );

                if ($created) {
                    $createdCredentials[$user->email] = $generatedPassword;
                }

                $rows[] = [
                    $created ? 'Đã tạo' : 'Đã cập nhật',
                    $user->name,
                    $user->email,
                    $units[$key]->code,
                    $account['scope'],
                ];
            }

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return $rows;
        });

        $this->info('Đã đồng bộ role Cơ Quan Phê Duyệt và hai tài khoản.');
        $this->table(
            ['Trạng thái', 'Họ tên', 'Email', 'Đơn vị', 'Phạm vi'],
            $rows
        );

        if ($createdCredentials !== []) {
            $this->newLine();
            $this->warn('Mật khẩu khởi tạo — yêu cầu người dùng đổi ngay sau lần đăng nhập đầu:');
            foreach ($createdCredentials as $email => $password) {
                $this->line("  {$email}: {$password}");
            }
        } else {
            $this->comment('Hai tài khoản đã tồn tại; mật khẩu hiện tại được giữ nguyên.');
        }

        return self::SUCCESS;
    }

    /**
     * @param  array{name:string,email:string,unit_code:string,scope:string}  $account
     * @return array{0:User,1:bool,2:string}
     */
    private function provisionUser(
        array $account,
        Unit $unit,
        Role $role,
        ?string $requestedPassword
    ): array {
        $user = User::withTrashed()
            ->where('email', $account['email'])
            ->first();
        $created = $user === null;
        $password = $requestedPassword ?: Str::password(20);

        if ($created) {
            $user = User::query()->create([
                'name' => $account['name'],
                'email' => $account['email'],
                'email_verified_at' => now(),
                'password' => Hash::make($password),
                'unit_id' => $unit->id,
                'role_id' => $role->id,
                'status' => 1,
                'user_type' => 'internal_user',
                'instructor_id' => null,
                'class_id' => null,
            ]);
        } else {
            if ($user->trashed()) {
                $user->restore();
            }

            $user->forceFill([
                'name' => $account['name'],
                'unit_id' => $unit->id,
                'role_id' => $role->id,
                'status' => 1,
                'user_type' => 'internal_user',
                'instructor_id' => null,
                'class_id' => null,
            ])->save();
        }

        $user->syncRoles([$role]);

        return [$user->fresh(['unit', 'roles']), $created, $password];
    }

    private function validatedPasswordOption(string $name): string|false|null
    {
        $value = $this->option($name);
        if ($value === null || $value === '') {
            return null;
        }

        if (mb_strlen((string) $value) < 12) {
            $this->error("Tùy chọn --{$name} phải có ít nhất 12 ký tự.");

            return false;
        }

        return (string) $value;
    }
}
