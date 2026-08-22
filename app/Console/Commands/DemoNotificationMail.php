<?php

namespace App\Console\Commands;

use App\Jobs\SendSystemNotificationEmail;
use App\Models\SystemNotification;
use App\Models\User;
use App\Support\SystemNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class DemoNotificationMail extends Command
{
    protected $signature = 'notifications:demo-mail
                            {--email=donglqts00539@fpt.edu.vn : Email nhận demo}
                            {--code=GV-0002 : Mã tài khoản ưu tiên nếu tìm theo code}';

    protected $description = 'Gửi email demo thông báo lịch giảng (đồng bộ web + mail) cho giảng viên';

    public function handle(): int
    {
        $email = (string) $this->option('email');
        $code = (string) $this->option('code');

        $recipient = User::query()
            ->where(function ($q) use ($email, $code) {
                $q->where('email', $email)
                    ->orWhere('code', $code);
            })
            ->first();

        if (! $recipient) {
            $this->error("Không tìm thấy user với email={$email} hoặc code={$code}");

            return self::FAILURE;
        }

        $actor = User::query()
            ->role('super-admin')
            ->first()
            ?? User::query()->where('email', 'admin@example.com')->first()
            ?? $recipient;

        $title = 'Cập nhật lịch giảng & lịch coi thi';
        $message = implode("\n", [
            "{$actor->name} đã cập nhật Lịch đào tạo liên quan đến giảng viên {$recipient->name} ({$recipient->code}).",
            '',
            '• Lịch giảng: có thay đổi tiết dạy trong tuần này',
            '• Lịch coi thi: có phân công coi thi / kiểm tra',
            '• Sự kiện nhà trường: thông báo triển khai lịch mới trên hệ thống',
            '',
            'Vui lòng đăng nhập hệ thống để xem chi tiết lịch giảng và xác nhận lịch coi thi.',
        ]);

        $url = \Illuminate\Support\Facades\Route::has('instructor-schedule.index')
            ? route('instructor-schedule.index')
            : url('/');

        $this->info("Gửi demo tới: {$recipient->name} <{$recipient->email}> (id={$recipient->id})");

        $notification = SystemNotifier::notifyUser(
            recipient: $recipient,
            actor: $actor,
            module: 'training-schedules',
            action: 'update',
            title: $title,
            message: $message,
            url: $url,
            type: SystemNotifier::TYPE_INSTRUCTOR_SCHEDULE,
            meta: [
                'demo' => true,
                'instructor_code' => $recipient->code,
            ],
            sendEmail: true,
        );

        if (! $notification) {
            $this->error('Không tạo được system notification.');

            return self::FAILURE;
        }

        $fresh = SystemNotification::query()->find($notification->id);
        if ($fresh && $fresh->email_sent_at === null && $fresh->email_failed_at === null) {
            try {
                SendSystemNotificationEmail::dispatchSync($fresh->id);
            } catch (\Throwable $e) {
                $this->warn('SMTP error: '.$e->getMessage());
            }
            $fresh->refresh();
        }

        $this->table(
            ['Field', 'Value'],
            [
                ['notification_id', (string) $notification->id],
                ['title', $notification->title],
                ['type', $notification->type],
                ['web_message', Str::limit($notification->message, 80)],
                ['email_sent_at', (string) ($fresh?->email_sent_at ?? 'null')],
                ['email_failed_at', (string) ($fresh?->email_failed_at ?? 'null')],
                ['email_error', Str::limit((string) ($fresh?->email_error ?? ''), 120)],
            ]
        );

        if ($fresh?->email_sent_at) {
            $this->info('✓ Đã gửi email thành công (web + mail đồng bộ).');

            return self::SUCCESS;
        }

        $this->warn('✓ Web notification đã tạo.');
        $this->warn('✗ Email chưa gửi được. Kiểm tra MAIL_* trong .env');

        return self::FAILURE;
    }
}
