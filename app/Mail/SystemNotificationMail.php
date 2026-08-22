<?php

namespace App\Mail;

use App\Models\SystemNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SystemNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public SystemNotification $notification,
        public string $recipientName = '',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->notification->title,
        );
    }

    public function content(): Content
    {
        $appUrl = $this->publicBaseUrl();
        $actionUrl = $this->absolutePublicUrl($this->notification->url);

        return new Content(
            html: 'emails.system-notification',
            text: 'emails.system-notification-text',
            with: [
                'notification' => $this->notification,
                'recipientName' => $this->recipientName !== ''
                    ? $this->recipientName
                    : ($this->notification->user?->name ?? 'Bạn'),
                'appName' => config('app.name', 'Quản lý đào tạo - CĐHC2'),
                'appUrl' => $appUrl,
                'actionUrl' => $actionUrl,
                'logoUrl' => (string) config(
                    'app.logo_url',
                    'https://cdhc2.edu.vn/wp-content/uploads/2025/12/Logo-11-12-2025.png'
                ),
                'typeLabel' => $this->typeLabel(),
                'typeColor' => $this->typeColor(),
                'typeIcon' => $this->typeIcon(),
            ],
        );
    }

    private function publicBaseUrl(): string
    {
        $url = (string) config('app.mail_url', config('app.url', 'https://tkb.cdhc2.edu.vn'));

        // Fallback nếu .env còn localhost
        if ($url === '' || str_contains($url, 'localhost') || str_contains($url, '127.0.0.1')) {
            $url = 'https://tkb.cdhc2.edu.vn';
        }

        return rtrim($url, '/');
    }

    private function absolutePublicUrl(?string $url): ?string
    {
        if ($url === null || trim($url) === '') {
            return $this->publicBaseUrl();
        }

        $url = trim($url);
        $base = $this->publicBaseUrl();

        // Relative path stored in DB → public host
        if (str_starts_with($url, '/')) {
            return $base.$url;
        }

        // Absolute localhost / wrong host → rewrite path onto public host
        $parts = parse_url($url);
        if (is_array($parts) && ! empty($parts['host'])) {
            $host = strtolower((string) $parts['host']);
            if (in_array($host, ['localhost', '127.0.0.1'], true) || str_contains($host, 'localhost')) {
                $path = $parts['path'] ?? '/';
                if (! empty($parts['query'])) {
                    $path .= '?'.$parts['query'];
                }
                if (! empty($parts['fragment'])) {
                    $path .= '#'.$parts['fragment'];
                }

                return $base.($path === '' ? '/' : $path);
            }

            return $url;
        }

        return $base.'/'.ltrim($url, '/');
    }

    private function typeLabel(): string
    {
        return match ($this->notification->type) {
            'student_schedule' => 'Lịch học',
            'student_exam' => 'Lịch thi',
            'instructor_schedule' => 'Lịch giảng',
            'instructor_proctor' => 'Lịch coi thi',
            'school_event' => 'Sự kiện nhà trường',
            'system_change' => 'Thay đổi hệ thống',
            default => 'Thông báo',
        };
    }

    private function typeColor(): string
    {
        return match ($this->notification->type) {
            'student_schedule', 'instructor_schedule' => '#1d4ed8',
            'student_exam', 'instructor_proctor' => '#b45309',
            'school_event' => '#047857',
            'system_change' => '#7c3aed',
            default => '#0f766e',
        };
    }

    private function typeIcon(): string
    {
        return match ($this->notification->type) {
            'student_schedule', 'instructor_schedule' => '📅',
            'student_exam', 'instructor_proctor' => '📝',
            'school_event' => '🏫',
            'system_change' => '⚙️',
            default => '🔔',
        };
    }
}
