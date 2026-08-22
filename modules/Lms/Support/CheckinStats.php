<?php

namespace Modules\Lms\Support;

use Illuminate\Support\Facades\DB;
use Modules\Lms\Models\LmsAttendanceRecord;
use Modules\Lms\Models\LmsCheckinEvent;

/**
 * P2: thống kê check-in mạng / probe / GPS cho admin.
 */
class CheckinStats
{
    /**
     * @return array{
     *   days:int,
     *   events:array{total:int,ok:int,fail:int,by_reason:array<string,int>},
     *   records:array{total:int,network_ok:int,network_fail:int,probe_ok:int,probe_fail:int,gps_ok:int,gps_fail:int,avg_distance_m:?float},
     *   recent_fails:list<array{id:int,reason:?string,note:?string,ip:?string,created_at:?string,user_id:?int}>
     * }
     */
    public static function summary(int $days = 14): array
    {
        $days = max(1, min(90, $days));
        $since = now()->subDays($days);

        $eventsTotal = LmsCheckinEvent::query()->where('created_at', '>=', $since)->count();
        $eventsOk = LmsCheckinEvent::query()->where('created_at', '>=', $since)->where('ok', true)->count();
        $byReason = LmsCheckinEvent::query()
            ->where('created_at', '>=', $since)
            ->where('ok', false)
            ->select('reason', DB::raw('COUNT(*) as c'))
            ->groupBy('reason')
            ->pluck('c', 'reason')
            ->map(fn ($c) => (int) $c)
            ->all();

        $recordsQ = LmsAttendanceRecord::query()
            ->where('checked_in_at', '>=', $since)
            ->whereIn('method', ['qr', 'self', 'gps']);

        $recordsTotal = (clone $recordsQ)->count();
        $networkOk = (clone $recordsQ)->where('network_ok', true)->count();
        $networkFail = (clone $recordsQ)->where('network_ok', false)->count();
        $probeOk = (clone $recordsQ)->where('probe_ok', true)->count();
        $probeFail = (clone $recordsQ)->where('probe_ok', false)->count();
        $gpsOk = (clone $recordsQ)->where('gps_ok', true)->count();
        $gpsFail = (clone $recordsQ)->where('gps_ok', false)->count();
        $avgDist = (clone $recordsQ)->whereNotNull('distance_m')->avg('distance_m');

        $recentFails = LmsCheckinEvent::query()
            ->where('created_at', '>=', $since)
            ->where('ok', false)
            ->orderByDesc('id')
            ->limit(25)
            ->get(['id', 'reason', 'note', 'client_ip', 'created_at', 'user_id'])
            ->map(fn ($e) => [
                'id' => $e->id,
                'reason' => $e->reason,
                'note' => $e->note,
                'ip' => $e->client_ip,
                'created_at' => $e->created_at?->format('d/m H:i'),
                'user_id' => $e->user_id,
            ])
            ->all();

        return [
            'days' => $days,
            'events' => [
                'total' => $eventsTotal,
                'ok' => $eventsOk,
                'fail' => max(0, $eventsTotal - $eventsOk),
                'by_reason' => $byReason,
            ],
            'records' => [
                'total' => $recordsTotal,
                'network_ok' => $networkOk,
                'network_fail' => $networkFail,
                'probe_ok' => $probeOk,
                'probe_fail' => $probeFail,
                'gps_ok' => $gpsOk,
                'gps_fail' => $gpsFail,
                'avg_distance_m' => $avgDist !== null ? round((float) $avgDist, 1) : null,
            ],
            'recent_fails' => $recentFails,
        ];
    }
}
