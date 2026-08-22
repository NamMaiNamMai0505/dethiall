<?php

namespace Modules\ExportTemplates\Services;

class TemplateValueFormatter
{
    public function format(mixed $value, ?string $formatter = null): string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? 'Có' : 'Không';
        }
        if (is_array($value)) {
            return implode(', ', array_map(
                static fn (mixed $item): string => is_scalar($item)
                    ? (string) $item
                    : (json_encode($item, JSON_UNESCAPED_UNICODE) ?: ''),
                $value
            ));
        }

        $formatter = trim((string) $formatter);
        if ($formatter === '') {
            return (string) $value;
        }

        return match (mb_strtolower($formatter)) {
            'uppercase' => mb_strtoupper((string) $value),
            'lowercase' => mb_strtolower((string) $value),
            default => $this->formattedValue($value, $formatter),
        };
    }

    private function formattedValue(mixed $value, string $formatter): string
    {
        if (str_starts_with($formatter, 'number:') && is_numeric($value)) {
            $decimals = max(0, min(10, (int) substr($formatter, 7)));

            return number_format((float) $value, $decimals, ',', '.');
        }

        $timestamp = strtotime((string) $value);
        if ($timestamp !== false && preg_match('/[dDjlNSwzWFmMntLoYyaABgGhHisueIOPTZcrU]/', $formatter)) {
            return date($formatter, $timestamp);
        }

        return (string) $value;
    }
}
