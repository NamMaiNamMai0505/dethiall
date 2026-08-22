<?php

namespace App\Jobs;

use App\Mail\SystemNotificationMail;
use App\Models\SystemNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendSystemNotificationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $notificationId
    ) {}

    public function handle(): void
    {
        $notification = SystemNotification::query()
            ->with('user:id,name,email')
            ->find($this->notificationId);

        if (! $notification || ! $notification->user) {
            return;
        }

        if ($notification->email_sent_at !== null) {
            return;
        }

        $email = $notification->user->email;
        if (! is_string($email) || $email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $notification->forceFill([
                'email_failed_at' => now(),
                'email_error' => 'Người dùng không có email hợp lệ',
            ])->save();

            return;
        }

        try {
            Mail::to($email, $notification->user->name)
                ->send(new SystemNotificationMail($notification, $notification->user->name));

            $notification->forceFill([
                'email_sent_at' => now(),
                'email_failed_at' => null,
                'email_error' => null,
            ])->save();
        } catch (\Throwable $e) {
            Log::warning('Failed to send system notification email', [
                'notification_id' => $notification->id,
                'user_id' => $notification->user_id,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            $notification->forceFill([
                'email_failed_at' => now(),
                'email_error' => mb_substr($e->getMessage(), 0, 500),
            ])->save();

            if (config('queue.default') !== 'sync') {
                throw $e;
            }
        }
    }
}
