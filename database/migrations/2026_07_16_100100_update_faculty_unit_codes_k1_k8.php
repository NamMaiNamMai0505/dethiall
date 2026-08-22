<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Chuẩn hoá mã khoa K1–K8 theo danh mục chính thức.
 */
return new class extends Migration
{
    public function up(): void
    {
        $map = [
            // code => [name patterns]
            'K1' => [
                'name' => 'Khoa Quân sự chung',
                'match' => ['quân sự chung', 'quan su chung', 'hậu cần chung', 'hau can chung'],
                'old_codes' => ['KQSCHUNG', 'KHCC', 'K1'],
            ],
            'K2' => [
                'name' => 'Khoa Khoa học xã hội và nhân văn',
                'match' => ['khoa học xã hội', 'khoa hoc xa hoi', 'nhân văn', 'nhan van'],
                'old_codes' => ['KHXH&NV', 'KHXHNV', 'K2'],
            ],
            'K3' => [
                'name' => 'Khoa Khoa học cơ bản',
                'match' => ['khoa học cơ bản', 'khoa hoc co ban'],
                'old_codes' => ['KKHCB', 'K3'],
            ],
            'K4' => [
                'name' => 'Khoa Y học cơ sở',
                'match' => ['y học cơ sở', 'y hoc co so'],
                'old_codes' => ['KYHCS', 'K4'],
            ],
            'K5' => [
                'name' => 'Khoa Y học lâm sàng',
                'match' => ['y học lâm sàng', 'y hoc lam sang'],
                'old_codes' => ['KYHLS', 'K5'],
            ],
            'K6' => [
                'name' => 'Khoa Y học quân sự',
                'match' => ['y học quân sự', 'y hoc quan su'],
                'old_codes' => ['KYHQS', 'K6'],
            ],
            'K7' => [
                'name' => 'Khoa Điều dưỡng',
                'match' => ['điều dưỡng', 'dieu duong'],
                'old_codes' => ['KDD', 'K7'],
            ],
            'K8' => [
                'name' => 'Khoa Dược',
                'match' => ['khoa dược', 'khoa duoc'],
                // avoid matching "điều dưỡng"
                'old_codes' => ['KD', 'K8'],
            ],
        ];

        if (! \Illuminate\Support\Facades\Schema::hasTable('units')) {
            return;
        }

        $units = DB::table('units')->whereNull('deleted_at')->get();

        foreach ($map as $code => $meta) {
            $unit = null;

            // 1) match old code
            foreach ($meta['old_codes'] as $old) {
                $unit = $units->first(function ($u) use ($old) {
                    return strtoupper((string) $u->code) === strtoupper($old);
                });
                if ($unit) {
                    break;
                }
            }

            // 2) match name
            if (! $unit) {
                $unit = $units->first(function ($u) use ($meta) {
                    $name = mb_strtolower((string) $u->name);
                    foreach ($meta['match'] as $needle) {
                        if (str_contains($name, mb_strtolower($needle))) {
                            // K8: "khoa dược" not "điều dưỡng"
                            if ($meta['name'] === 'Khoa Dược' && str_contains($name, 'điều dưỡng')) {
                                return false;
                            }

                            return true;
                        }
                    }

                    return false;
                });
            }

            if ($unit) {
                DB::table('units')->where('id', $unit->id)->update([
                    'code' => $code,
                    'name' => $meta['name'],
                    'updated_at' => now(),
                ]);
            } else {
                // create if missing
                DB::table('units')->insert([
                    'code' => $code,
                    'name' => $meta['name'],
                    'parent_id' => null,
                    'level' => 2,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // không revert mã khoa (dữ liệu vận hành)
    }
};
