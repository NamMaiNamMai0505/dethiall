<?php

namespace Modules\ExportTemplates\Data;

use Illuminate\Support\Collection;
use Modules\ScheduleDetail\Models\ScheduleDetail;

class LhlScheduleGroupService
{
    /**
     * Gom các tiết liên tiếp theo môn học. Các giá trị phụ khác nhau (GV, bài,
     * địa điểm...) được nối lại trong cùng nhóm để vừa đúng bố cục LHL vừa
     * không làm mất dữ liệu.
     *
     * @param  Collection<int, ScheduleDetail>  $details
     * @return list<array<string, mixed>>
     */
    public function group(Collection $details): array
    {
        $rows = $details
            ->sortBy(fn (ScheduleDetail $detail) => sprintf(
                '%s-%04d',
                $detail->date instanceof \DateTimeInterface
                    ? $detail->date->format('Y-m-d')
                    : (string) $detail->date,
                (int) $detail->period
            ))
            ->values();

        $groups = [];
        $current = null;

        foreach ($rows as $detail) {
            $row = $this->row($detail);
            $signature = implode('|', [
                $row['date'],
                $detail->subject_id ?: $row['subject_code'] ?: $row['subject_name'],
            ]);

            if (
                $current !== null
                && $current['_signature'] === $signature
                && $row['period_start'] === $current['period_end'] + 1
                // Hai buổi 1-5 và 6-9 là ranh giới bố cục của mẫu LHL cũ.
                // Không gom xuyên qua ranh giới này dù cùng môn.
                && ! ($current['period_end'] === 5 && $row['period_start'] === 6)
            ) {
                $current['period_end'] = $row['period_end'];
                $current['period_label'] = $current['period_start'].'-'.$current['period_end'];
                foreach ([
                    'teacher_name',
                    'teacher_code',
                    'teacher_unit',
                    'teacher_position',
                    'location',
                    'content',
                    'lesson_type',
                    'note',
                ] as $field) {
                    $current[$field] = $this->appendDistinct(
                        (string) $current[$field],
                        (string) $row[$field]
                    );
                }

                continue;
            }

            if ($current !== null) {
                unset($current['_signature']);
                $groups[] = $current;
            }

            $current = $row + ['_signature' => $signature];
        }

        if ($current !== null) {
            unset($current['_signature']);
            $groups[] = $current;
        }

        return $groups;
    }

    /**
     * @return array<string, mixed>
     */
    private function row(ScheduleDetail $detail): array
    {
        $date = $detail->date instanceof \DateTimeInterface
            ? $detail->date->format('Y-m-d')
            : (string) $detail->date;
        $period = (int) $detail->period;
        $classroom = $detail->classroom;
        $building = $classroom?->building;

        return [
            'date' => $date,
            'day' => $date !== '' ? (int) date('d', strtotime($date)) : null,
            'weekday' => $date !== '' ? $this->weekday((int) date('N', strtotime($date))) : '',
            'period_start' => $period,
            'period_end' => $period,
            'period_label' => (string) $period,
            'subject_name' => (string) ($detail->subject?->name ?? ''),
            'subject_code' => (string) ($detail->subject?->code ?? ''),
            'subject_short_name' => (string) ($detail->subject?->short_label ?? ''),
            'teacher_name' => (string) ($detail->instructor?->name ?? ''),
            'teacher_code' => (string) ($detail->instructor?->code ?? ''),
            'teacher_unit' => (string) ($detail->instructor?->unit?->name ?? ''),
            'teacher_position' => (string) ($detail->instructor?->position?->name ?? ''),
            'location' => $this->location($classroom?->name, $building?->code, $building?->name),
            'content' => (string) ($detail->subjectLesson?->display_label ?? ''),
            'lesson_type' => (string) ($detail->lesson_type ?? ''),
            'note' => '',
        ];
    }

    private function weekday(int $isoDay): string
    {
        return match ($isoDay) {
            1 => 'Thứ Hai',
            2 => 'Thứ Ba',
            3 => 'Thứ Tư',
            4 => 'Thứ Năm',
            5 => 'Thứ Sáu',
            6 => 'Thứ Bảy',
            default => 'Chủ Nhật',
        };
    }

    private function appendDistinct(string $current, string $next): string
    {
        $next = trim($next);
        if ($next === '') {
            return $current;
        }

        $values = array_values(array_filter(array_map('trim', preg_split('/\R/u', $current) ?: [])));
        if (! in_array($next, $values, true)) {
            $values[] = $next;
        }

        return implode("\n", $values);
    }

    private function location(?string $room, ?string $buildingCode, ?string $buildingName): string
    {
        $building = trim((string) ($buildingCode ?: $buildingName));
        $room = trim((string) $room);

        return $room !== '' && $building !== '' ? "{$room}/{$building}" : ($room ?: $building);
    }
}
