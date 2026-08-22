<?php

namespace App\Http\Controllers;

use App\Models\SystemNotification;
use App\Support\SystemNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SystemNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! $this->notificationsReady()) {
            return response()->json(['count' => 0, 'data' => []]);
        }

        try {
            $sinceId = (int) $request->query('since_id', 0);
            $userId = $request->user()->id;

            $query = SystemNotification::query()
                ->where('user_id', $userId)
                ->latest('id');

            if ($sinceId > 0) {
                $query->where('id', '>', $sinceId);
            } else {
                $query->limit(30);
            }

            $notifications = $query->get();

            return response()->json([
                'count' => $this->unreadCountFor($userId),
                'data' => $notifications->map(fn (SystemNotification $item) => [
                    'id' => $item->id,
                    'title' => $item->title,
                    'message' => $item->message,
                    'url' => SystemNotifier::toAppRelativeUrl($item->url),
                    'module' => $item->module,
                    'action' => $item->action,
                    'type' => $item->type ?? 'system',
                    'read_at' => $item->read_at?->toIso8601String(),
                    'created_at' => $item->created_at?->toIso8601String(),
                    'time_ago' => $item->created_at?->diffForHumans(),
                ]),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Notifications index failed', ['error' => $e->getMessage()]);

            return response()->json(['count' => 0, 'data' => []]);
        }
    }

    public function unreadCount(Request $request): JsonResponse
    {
        if (! $this->notificationsReady()) {
            return response()->json(['count' => 0]);
        }

        try {
            $count = SystemNotification::query()
                ->where('user_id', $request->user()->id)
                ->whereNull('read_at')
                ->count();

            return response()->json(['count' => $count]);
        } catch (\Throwable $e) {
            Log::warning('Notifications unread-count failed', ['error' => $e->getMessage()]);

            return response()->json(['count' => 0]);
        }
    }

    public function markRead(Request $request, SystemNotification $notification): JsonResponse
    {
        if (! $this->notificationsReady()) {
            return response()->json(['ok' => false], 503);
        }

        try {
            abort_unless($notification->user_id === $request->user()->id, 403);

            $notification->markAsRead();

            return response()->json([
                'ok' => true,
                'count' => $this->unreadCountFor($request->user()->id),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Notifications markRead failed', ['error' => $e->getMessage()]);

            return response()->json(['ok' => false], 500);
        }
    }

    public function markAllRead(Request $request): JsonResponse
    {
        if (! $this->notificationsReady()) {
            return response()->json(['ok' => false], 503);
        }

        try {
            SystemNotification::query()
                ->where('user_id', $request->user()->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            return response()->json(['ok' => true, 'count' => 0]);
        } catch (\Throwable $e) {
            Log::warning('Notifications markAllRead failed', ['error' => $e->getMessage()]);

            return response()->json(['ok' => false], 500);
        }
    }

    /**
     * Tránh 500 khi production chưa migrate bảng system_notifications.
     */
    private function notificationsReady(): bool
    {
        try {
            return Schema::hasTable('system_notifications');
        } catch (\Throwable) {
            return false;
        }
    }

    private function unreadCountFor(int $userId): int
    {
        return SystemNotification::query()
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }
}
