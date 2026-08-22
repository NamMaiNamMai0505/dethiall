<?php

namespace Modules\Subject\Support;

use Illuminate\Support\Str;
use Modules\Specialization\Models\Specialization;

/**
 * Build specialization/subject code prefixes.
 *
 * Convention:
 * - A_ = hệ quân sự, B_ = hệ dân sự
 * - Suffix ưu tiên phần số của Mã số chương trình (A.6720101/B.6720101)
 * Example: B.6720301 → B_6720301 → subject M009K2 → B_6720301_M009K2
 */
class SubjectCodePrefix
{
    public static function suggest(?Specialization $specialization): string
    {
        if (! $specialization) {
            return 'B_NN';
        }

        $existing = trim((string) $specialization->code);
        if ($existing !== '' && preg_match('/^[AB]_[A-Z0-9]+$/i', $existing)) {
            return strtoupper($existing);
        }

        if ($existing !== '' && preg_match('/^([AB])[._-](.+)$/i', $existing, $matches)) {
            $programCode = self::normalizeCodePart($matches[2]);
            if ($programCode !== '') {
                return strtoupper($matches[1]).'_'.$programCode;
            }
        }

        $systemCode = strtolower((string) $specialization->trainingSystem?->code);
        $system = match ($systemCode) {
            'military' => 'A',
            'civilian' => 'B',
            default => self::detectSystemLetter($specialization->name ?? ''),
        };
        $abbr = $existing !== ''
            ? self::normalizeCodePart($existing)
            : self::abbreviateName($specialization->name ?? '');

        if ($abbr === '') {
            $abbr = 'NN';
        }

        // Avoid A_A_CDDD if code already starts with system letter only
        if (preg_match('/^[AB]$/i', $abbr)) {
            $abbr = self::abbreviateName($specialization->name ?? '') ?: 'NN';
        }

        return $system.'_'.$abbr;
    }

    public static function normalizePrefix(?string $prefix, ?Specialization $specialization = null): string
    {
        $prefix = strtoupper(trim((string) $prefix));
        $prefix = preg_replace('/[^A-Z0-9_]/', '', $prefix) ?? '';
        $prefix = trim($prefix, '_');

        if ($prefix === '') {
            return self::suggest($specialization);
        }

        // Ensure system letter prefix when user only typed industry abbr
        if (! preg_match('/^[AB]_/', $prefix)) {
            $system = self::detectSystemLetter($specialization?->name ?? '');
            $prefix = $system.'_'.$prefix;
        }

        return $prefix;
    }

    public static function buildSubjectCode(string $prefix, string $subjectCode): string
    {
        $prefix = strtoupper(trim($prefix, " \t\n\r\0\x0B_"));
        $subjectCode = trim($subjectCode);

        if ($subjectCode === '') {
            return $prefix;
        }

        // Already fully prefixed
        if (str_starts_with(strtoupper($subjectCode), $prefix.'_')) {
            return strtoupper($subjectCode);
        }

        // Subject code already looks like B_CDDD_M009K2 with same prefix stem
        if (preg_match('/^[AB]_[A-Z0-9]+_/i', $subjectCode)) {
            return strtoupper($subjectCode);
        }

        return $prefix.'_'.$subjectCode;
    }

    /**
     * Mã hiển thị trên UI / xuất: ẩn prefix hệ+ngành (B_CDDD_ / A_xxx_).
     * Ví dụ: B_CDDD_M001K1 → M001K1. Không xoá trong DB.
     */
    public static function displayCode(?string $fullCode): string
    {
        $fullCode = trim((string) $fullCode);
        if ($fullCode === '') {
            return '';
        }

        // B_CDDD_M001K1 hoặc A_CDYS_M001K1
        if (preg_match('/^[AB]_[A-Z0-9]+_(.+)$/iu', $fullCode, $m)) {
            return $m[1];
        }

        return $fullCode;
    }

    /**
     * Local code dùng so khớp import (bỏ prefix nếu có).
     */
    public static function localCode(?string $fullCode): string
    {
        return self::displayCode($fullCode);
    }

    /**
     * Mã khoa từ mã môn (hậu tố K1–K8).
     * Ví dụ: B_CDD_M098K8 / M098K8 → K8
     */
    public static function facultyCodeFromSubjectCode(?string $fullCode): ?string
    {
        $candidates = [
            self::displayCode($fullCode),
            strtoupper(trim((string) $fullCode)),
        ];

        foreach ($candidates as $code) {
            if ($code === '') {
                continue;
            }
            if (preg_match('/(K[1-8])$/i', $code, $m)) {
                return strtoupper($m[1]);
            }
        }

        return null;
    }

    /**
     * Scope query môn thuộc khoa. Ưu tiên `subjects.faculty_code` (phân công
     * tường minh qua form Môn học); môn chưa được gán thì mới rơi về hậu tố
     * mã môn (K1…K8) như trước — dự phòng cho dữ liệu cũ chưa cập nhật, vì
     * mã môn có thể đổi theo quy ước khác hoặc không có hậu tố khoa nào cả
     * (vd môn của NVQY). Không giới hạn cứng vào danh sách K1-K8 — Khoa mới
     * (K9, hoặc quy ước khác) lọc được ngay, không cần sửa code. Xem
     * docs/sprints/SPRINT44_LMS_AND_SCOPE_UX.md §2.2.
     */
    public static function applyFacultyCodeScope($query, string $facultyCode)
    {
        $facultyCode = strtoupper(trim($facultyCode));
        if ($facultyCode === '') {
            return $query->whereRaw('1 = 0');
        }

        $driver = $query->getConnection()->getDriverName();
        $suffixExpression = in_array($driver, ['mysql', 'mariadb'], true)
            ? 'UPPER(RIGHT(code, 2))'
            : 'UPPER(SUBSTR(code, -2))';

        return $query->where(function ($scoped) use ($facultyCode, $suffixExpression) {
            $scoped->where('faculty_code', $facultyCode)
                ->orWhere(function ($legacy) use ($facultyCode, $suffixExpression) {
                    $legacy->whereNull('faculty_code')
                        ->whereRaw("{$suffixExpression} = ?", [$facultyCode]);
                });
        });
    }

    public static function detectSystemLetter(string $name): string
    {
        $normalized = self::removeAccents(mb_strtolower($name));

        if (str_contains($normalized, 'quan su') || preg_match('/\bqs\b/', $normalized)) {
            return 'A';
        }

        if (str_contains($normalized, 'dan su') || preg_match('/\bds\b/', $normalized)) {
            return 'B';
        }

        // Heuristic: names containing "quan" (quân y, quân sự...) without dân sự → A
        if (preg_match('/\bquan\b/', $normalized) && ! str_contains($normalized, 'dan')) {
            return 'A';
        }

        return 'B';
    }

    public static function abbreviateName(string $name): string
    {
        $name = self::removeAccents($name);
        $name = preg_replace('/[^A-Za-z0-9\s]/', ' ', $name) ?? '';
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        $stopWords = [
            'cua', 'va', 'and', 'the', 'he', 'nganh', 'khoa',
            'dao', 'tao', // đào tạo — still useful for length? skip "đào" "tạo" only as pair
        ];

        $letters = [];
        foreach ($parts as $part) {
            $part = strtolower($part);
            if ($part === '' || in_array($part, $stopWords, true)) {
                continue;
            }
            $letters[] = strtoupper(mb_substr($part, 0, 1));
        }

        $abbr = implode('', $letters);

        return self::normalizeCodePart($abbr !== '' ? $abbr : 'NN');
    }

    public static function normalizeCodePart(string $code): string
    {
        $code = strtoupper(self::removeAccents($code));
        $code = preg_replace('/[^A-Z0-9]/', '', $code) ?? '';

        return $code;
    }

    public static function removeAccents(string $value): string
    {
        $value = Str::ascii($value);
        $value = str_replace(['đ', 'Đ'], ['d', 'D'], $value);

        return $value;
    }

    /**
     * Human-readable convention note for UI.
     */
    public static function conventionNote(): string
    {
        return 'Quy ước tiền tố môn: lấy Mã số chương trình, đổi dấu chấm thành gạch dưới. '
            .'Ví dụ B.6720301 → B_6720301; mã môn M009K2 sẽ thành B_6720301_M009K2. '
            .'Mã cũ vẫn được hỗ trợ để không ảnh hưởng dữ liệu hiện có. '
            .'Có thể chỉnh tay tiền tố nội bộ; để trống sẽ áp dụng quy ước tự động.';
    }
}
