<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Modules\Instructor\Models\Instructor;

class BackfillUserInstructorPhone extends Command
{
    protected $signature = 'users:backfill-instructor-phone
        {--apply : Ghi dữ liệu (mặc định chỉ xem trước, không thay đổi)}';

    protected $description = 'Đồng bộ SĐT một lần cho các tài khoản đã liên kết Giảng viên từ trước, '
        .'tạo trước khi có tính năng đồng bộ 2 chiều User <-> Instructor';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $users = User::query()
            ->whereNotNull('instructor_id')
            ->with('instructor:id,name,phone')
            ->get(['id', 'name', 'phone', 'instructor_id']);

        $rows = [];
        foreach ($users as $user) {
            $instructor = $user->instructor;
            if (! $instructor) {
                continue;
            }

            $userPhone = trim((string) $user->phone);
            $instructorPhone = trim((string) $instructor->phone);

            if ($userPhone === $instructorPhone) {
                continue; // đã khớp, không cần đụng tới
            }

            // Ưu tiên hồ sơ Giảng viên làm nguồn nếu nó có dữ liệu (thường
            // được tạo/nhập trước khi có tài khoản); nếu GV trống nhưng User
            // có SĐT thì đẩy ngược từ User sang GV.
            if ($instructorPhone !== '') {
                $direction = 'GV -> User';
                $newUserPhone = $instructorPhone;
                $newInstructorPhone = $instructorPhone;
            } elseif ($userPhone !== '') {
                $direction = 'User -> GV';
                $newUserPhone = $userPhone;
                $newInstructorPhone = $userPhone;
            } else {
                continue; // cả 2 đều trống, không có gì để đồng bộ
            }

            $rows[] = [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'instructor_id' => $instructor->id,
                'instructor_name' => $instructor->name,
                'user_phone_before' => $userPhone ?: '(trống)',
                'instructor_phone_before' => $instructorPhone ?: '(trống)',
                'direction' => $direction,
                'result' => $newUserPhone,
            ];

            if ($apply) {
                User::query()->whereKey($user->id)->update(['phone' => $newUserPhone]);
                Instructor::query()->whereKey($instructor->id)->update(['phone' => $newInstructorPhone]);
            }
        }

        if ($rows === []) {
            $this->info('Không có tài khoản nào lệch SĐT với hồ sơ Giảng viên liên kết.');

            return self::SUCCESS;
        }

        $this->table(
            ['User ID', 'Tài khoản', 'GV liên kết', 'SĐT User (cũ)', 'SĐT GV (cũ)', 'Chiều đồng bộ', 'SĐT sau đồng bộ'],
            array_map(fn (array $r) => [
                $r['user_id'],
                $r['user_name'],
                $r['instructor_name'],
                $r['user_phone_before'],
                $r['instructor_phone_before'],
                $r['direction'],
                $r['result'],
            ], $rows)
        );

        if ($apply) {
            $this->info('Đã đồng bộ '.count($rows).' tài khoản.');
        } else {
            $this->warn('DRY-RUN: chưa thay đổi dữ liệu. Dùng --apply sau khi kiểm tra danh sách.');
        }

        return self::SUCCESS;
    }
}
