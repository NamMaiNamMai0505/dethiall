<?php

namespace App\Services;

use App\Models\DigitalSignature;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DigitalSignatureService
{
    /**
     * Seed 3 chữ ký mẫu từ config lhl_export (idempotent).
     */
    public function seedSystemTemplates(): int
    {
        $signers = config('lhl_export.signers', []);
        $count = 0;
        foreach (array_values($signers) as $i => $s) {
            $key = (string) ($s['key'] ?? '');
            if ($key === '') {
                continue;
            }
            $row = DigitalSignature::query()->firstOrNew([
                'slot_key' => $key,
                'is_system_template' => true,
            ]);
            // Config chỉ dùng để khởi tạo. Sau khi mẫu đã tồn tại, thông tin do
            // người dùng cập nhật là nguồn dữ liệu chính và không được seed lại
            // mỗi khi mở trang danh sách/xuất biểu mẫu.
            if (! $row->exists) {
                $row->user_id = null;
                $row->fill([
                    'display_name' => (string) ($s['name'] ?? ''),
                    'role_line1' => (string) ($s['role_line1'] ?? ''),
                    'role_line2' => (string) ($s['role_line2'] ?? ''),
                    'image_path' => (string) ($s['image'] ?? ''),
                    'match_names' => array_values(array_filter($s['match_names'] ?? [])),
                    'is_active' => true,
                    'sort_order' => $i + 1,
                ]);
                $row->save();
                $count++;
            }
        }

        return $count;
    }

    /**
     * Claim mẫu hệ thống cho user nếu tên khớp match_names.
     */
    public function claimMatchingTemplates(User $user): Collection
    {
        $claimed = collect();
        $normUser = $this->normalizeName($user->name ?? '');
        if ($normUser === '') {
            return $claimed;
        }

        $templates = DigitalSignature::query()
            ->systemTemplates()
            ->whereNull('user_id')
            ->get();

        foreach ($templates as $sig) {
            if ($this->nameMatches($normUser, $sig->match_names ?? [], $sig->display_name)) {
                $sig->user_id = $user->id;
                $sig->save();
                $claimed->push($sig);
            }
        }

        return $claimed;
    }

    /**
     * Claim lại tất cả user (admin/cron).
     */
    public function claimAllUsers(): int
    {
        $n = 0;
        User::query()->whereNotNull('name')->orderBy('id')->chunkById(100, function ($users) use (&$n) {
            foreach ($users as $user) {
                $n += $this->claimMatchingTemplates($user)->count();
            }
        });

        return $n;
    }

    public function nameMatches(string $normalizedUserName, array $matchNames, ?string $displayName = null): bool
    {
        $candidates = $matchNames;
        if ($displayName) {
            $candidates[] = $displayName;
        }
        foreach ($candidates as $raw) {
            $n = $this->normalizeName((string) $raw);
            if ($n === '') {
                continue;
            }
            // Khớp đầy đủ hoặc user name chứa tên người (bỏ cấp bậc)
            if ($normalizedUserName === $n) {
                return true;
            }
            if (str_contains($normalizedUserName, $n) || str_contains($n, $normalizedUserName)) {
                // Tránh khớp quá ngắn
                if (mb_strlen($n) >= 6 && mb_strlen($normalizedUserName) >= 6) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Chuẩn hoá tên: lower, bỏ dấu, bỏ cấp bậc thường gặp.
     */
    public function normalizeName(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '';
        }
        // Bỏ cấp bậc / học hàm
        $name = preg_replace(
            '/\b(thượng\s*uý|trung\s*tá|đại\s*tá|thiếu\s*tá|thượng\s*tá|đại\s*uý|trung\s*uý|thiếu\s*uý|binh\s*nhì|binh\s*nhất|hạ\s*sĩ|trung\s*sĩ|thượng\s*sĩ|bsck\d*|ts\.?|ths\.?|pgs\.?|gs\.?)\b/iu',
            ' ',
            $name
        ) ?? $name;
        $name = Str::ascii(mb_strtolower($name, 'UTF-8'));
        $name = preg_replace('/[^a-z0-9\s]/', ' ', $name) ?? $name;
        $name = preg_replace('/\s+/', ' ', $name) ?? $name;

        return trim($name);
    }

    public function storeUpload(UploadedFile $file, User $user): string
    {
        $dir = 'signatures/users/'.$user->id;
        $name = 'sig_'.time().'_'.Str::random(6).'.png';

        $cleaned = $this->makeSignatureBackgroundTransparent($file);
        if ($cleaned !== null) {
            Storage::disk('public')->put($dir.'/'.$name, $cleaned);

            return $dir.'/'.$name;
        }

        $fallbackName = 'sig_'.time().'_'.Str::random(6).'.'.$file->getClientOriginalExtension();

        return $file->storeAs($dir, $fallbackName, 'public');
    }

    private function makeSignatureBackgroundTransparent(UploadedFile $file): ?string
    {
        return $this->cleanSignatureImageBinary((string) file_get_contents($file->getRealPath()));
    }

    public function removeBackgroundFromStoredImage(string $relativePath): bool
    {
        if (! Storage::disk('public')->exists($relativePath)) {
            return false;
        }
        $cleaned = $this->cleanSignatureImageBinary((string) Storage::disk('public')->get($relativePath));
        if ($cleaned === null) {
            return false;
        }
        Storage::disk('public')->put($relativePath, $cleaned);

        return true;
    }

    private function cleanSignatureImageBinary(string $binary): ?string
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        $source = @imagecreatefromstring($binary);
        if (! $source) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $background = $this->estimateSignatureBackgroundColor($source, $width, $height);
        $rowCounts = array_fill(0, $height, 0);
        $isInkPixel = function (int $r, int $g, int $b) use ($background): bool {
            $spread = max($r, $g, $b) - min($r, $g, $b);
            $average = ($r + $g + $b) / 3;
            $distanceToBackground = sqrt(($r - $background[0]) ** 2 + ($g - $background[1]) ** 2 + ($b - $background[2]) ** 2);
            $blueInk = $b > $r + 18 && $b > $g + 6 && $spread > 28 && $average < 220;
            $purpleInk = $b > $g + 18 && $r > $g + 8 && $spread > 22 && $average < 220;
            $darkInk = ($average < 125 || ($average < 150 && $spread > 12)) && $distanceToBackground > 48;

            return $blueInk || $purpleInk || $darkInk;
        };

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgba = imagecolorsforindex($source, imagecolorat($source, $x, $y));
                if ($isInkPixel((int) $rgba['red'], (int) $rgba['green'], (int) $rgba['blue'])) {
                    $rowCounts[$y]++;
                }
            }
        }

        [$cropTop, $cropBottom] = $this->dominantInkRange($rowCounts, max(2, (int) floor($width * 0.002)), 0, $height - 1);
        $colCounts = array_fill(0, $width, 0);
        for ($y = $cropTop; $y <= $cropBottom; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgba = imagecolorsforindex($source, imagecolorat($source, $x, $y));
                if ($isInkPixel((int) $rgba['red'], (int) $rgba['green'], (int) $rgba['blue'])) {
                    $colCounts[$x]++;
                }
            }
        }

        [$cropLeft, $cropRight] = $this->dominantInkRange($colCounts, max(1, (int) floor(($cropBottom - $cropTop + 1) * 0.002)), 0, $width - 1);
        $padding = max(8, (int) floor(min($width, $height) * 0.012));
        $cropLeft = max(0, $cropLeft - $padding);
        $cropRight = min($width - 1, $cropRight + $padding);
        $cropTop = max(0, $cropTop - $padding);
        $cropBottom = min($height - 1, $cropBottom + $padding);
        $targetWidth = max(1, $cropRight - $cropLeft + 1);
        $targetHeight = max(1, $cropBottom - $cropTop + 1);

        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($target, false);
        imagesavealpha($target, true);
        $transparent = imagecolorallocatealpha($target, 255, 255, 255, 127);
        imagefill($target, 0, 0, $transparent);

        for ($y = $cropTop; $y <= $cropBottom; $y++) {
            for ($x = $cropLeft; $x <= $cropRight; $x++) {
                $rgba = imagecolorsforindex($source, imagecolorat($source, $x, $y));
                $r = (int) $rgba['red'];
                $g = (int) $rgba['green'];
                $b = (int) $rgba['blue'];
                $alpha = (int) ($rgba['alpha'] ?? 0);
                $spread = max($r, $g, $b) - min($r, $g, $b);
                $average = ($r + $g + $b) / 3;
                $distanceToBackground = sqrt(($r - $background[0]) ** 2 + ($g - $background[1]) ** 2 + ($b - $background[2]) ** 2);
                $isInk = $isInkPixel($r, $g, $b);
                $isPaper = ! $isInk && (
                    ($r >= 238 && $g >= 238 && $b >= 238)
                    || ($average >= 182 && $spread <= 42)
                    || ($distanceToBackground <= 62 && $spread <= 55)
                );
                if ($isPaper || ! $isInk) {
                    imagesetpixel($target, $x - $cropLeft, $y - $cropTop, $transparent);
                    continue;
                }
                $outputAlpha = min(126, max($alpha, 0));
                $color = imagecolorallocatealpha($target, $r, $g, $b, $outputAlpha);
                imagesetpixel($target, $x - $cropLeft, $y - $cropTop, $color);
            }
        }

        ob_start();
        imagepng($target);
        $binary = ob_get_clean();
        imagedestroy($source);
        imagedestroy($target);

        return is_string($binary) && $binary !== '' ? $binary : null;
    }

    /**
     * @param  array<int,int>  $counts
     * @return array{0:int,1:int}
     */
    private function dominantInkRange(array $counts, int $threshold, int $fallbackStart, int $fallbackEnd): array
    {
        $bestStart = null;
        $bestEnd = null;
        $bestWeight = 0;
        $currentStart = null;
        $currentWeight = 0;
        $lastIndex = $fallbackStart;

        foreach ($counts as $index => $count) {
            if ($count >= $threshold) {
                if ($currentStart === null) {
                    $currentStart = (int) $index;
                    $currentWeight = 0;
                }
                $currentWeight += (int) $count;
                $lastIndex = (int) $index;
                continue;
            }
            if ($currentStart !== null && $currentWeight > $bestWeight) {
                $bestStart = $currentStart;
                $bestEnd = $lastIndex;
                $bestWeight = $currentWeight;
            }
            $currentStart = null;
            $currentWeight = 0;
        }

        if ($currentStart !== null && $currentWeight > $bestWeight) {
            $bestStart = $currentStart;
            $bestEnd = $lastIndex;
        }

        return [$bestStart ?? $fallbackStart, $bestEnd ?? $fallbackEnd];
    }

    /**
     * The uploaded signature is often a phone photo, so the paper may be gray.
     * Sampling the outer frame gives us the actual paper color for that photo.
     *
     * @return array{0:int,1:int,2:int}
     */
    private function estimateSignatureBackgroundColor(\GdImage $source, int $width, int $height): array
    {
        $step = max(1, (int) floor(min($width, $height) / 80));
        $samples = [];
        for ($x = 0; $x < $width; $x += $step) {
            $samples[] = imagecolorsforindex($source, imagecolorat($source, $x, 0));
            $samples[] = imagecolorsforindex($source, imagecolorat($source, $x, $height - 1));
        }
        for ($y = 0; $y < $height; $y += $step) {
            $samples[] = imagecolorsforindex($source, imagecolorat($source, 0, $y));
            $samples[] = imagecolorsforindex($source, imagecolorat($source, $width - 1, $y));
        }
        $channels = ['red' => [], 'green' => [], 'blue' => []];
        foreach ($samples as $sample) {
            foreach ($channels as $channel => $_) {
                $channels[$channel][] = (int) $sample[$channel];
            }
        }
        foreach ($channels as $channel => $values) {
            sort($values);
            $channels[$channel] = $values[(int) floor(count($values) / 2)] ?? 255;
        }

        return [(int) $channels['red'], (int) $channels['green'], (int) $channels['blue']];
    }

    /**
     * Chữ ký thuộc đúng tài khoản đang đăng nhập.
     *
     * @return Collection<int, DigitalSignature>
     */
    public function listForUser(User $user): Collection
    {
        $this->seedSystemTemplates();
        $this->claimMatchingTemplates($user);

        return DigitalSignature::query()
            ->where('user_id', $user->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * Danh sách chữ ký có thể chọn khi xuất LHL (theo slot).
     *
     * @return Collection<int, DigitalSignature>
     */
    public function optionsForExportSlot(string $slotKey, ?User $user = null): Collection
    {
        $this->seedSystemTemplates();

        $q = DigitalSignature::query()
            ->active()
            ->where(function ($q) use ($slotKey) {
                $q->where('slot_key', $slotKey)
                    ->orWhere('slot_key', DigitalSignature::SLOT_CUSTOM);
            })
            ->orderByDesc('is_system_template')
            ->orderBy('sort_order')
            ->orderBy('display_name');

        return $q->get();
    }

    /**
     * Build 3 signers cho export từ request meta + DB.
     *
     * @param  array<string, mixed>  $meta
     * @return list<array{key:string,role_line1:string,role_line2:string,name:string,image:string,enabled:bool,signature_id?:int}>
     */
    public function resolveExportSigners(array $meta = []): array
    {
        $this->seedSystemTemplates();
        $cfg = config('lhl_export.signers', []);
        $result = [];

        foreach (array_values($cfg) as $s) {
            $key = (string) ($s['key'] ?? '');
            if ($key === '') {
                continue;
            }
            $enabledKey = 'signer_'.$key.'_enabled';
            $idKey = 'signer_'.$key.'_id';
            $nameKey = 'signer_'.$key.'_name';
            $role1Key = 'signer_'.$key.'_role1';
            $role2Key = 'signer_'.$key.'_role2';

            $enabled = true;
            if (array_key_exists($enabledKey, $meta)) {
                $enabled = filter_var($meta[$enabledKey], FILTER_VALIDATE_BOOLEAN)
                    || $meta[$enabledKey] === '1'
                    || $meta[$enabledKey] === 1
                    || $meta[$enabledKey] === true;
            }

            $sig = null;
            if (! empty($meta[$idKey])) {
                $sig = DigitalSignature::query()->find((int) $meta[$idKey]);
            }
            if (! $sig) {
                $sig = DigitalSignature::query()
                    ->active()
                    ->where('slot_key', $key)
                    ->orderByDesc('is_system_template')
                    ->orderBy('sort_order')
                    ->first();
            }

            $image = $sig?->image_path ?: (string) ($s['image'] ?? '');
            // Prefer absolute path relative storage for exporters
            if ($sig && $sig->absoluteImagePath()) {
                $abs = $sig->absoluteImagePath();
                // Keep relative public path if under storage
                if (str_starts_with($abs, storage_path('app/public/'))) {
                    $image = substr($abs, strlen(storage_path('app/public/')));
                } elseif (str_starts_with($abs, public_path('images/'))) {
                    $image = 'images/'.substr($abs, strlen(public_path('images/')));
                } else {
                    $image = $sig->image_path;
                }
            }

            $result[] = [
                'key' => $key,
                'role_line1' => (string) ($meta[$role1Key] ?? $sig?->role_line1 ?? $s['role_line1'] ?? ''),
                'role_line2' => (string) ($meta[$role2Key] ?? $sig?->role_line2 ?? $s['role_line2'] ?? ''),
                'name' => (string) ($meta[$nameKey] ?? $sig?->display_name ?? $s['name'] ?? ''),
                'image' => (string) $image,
                'enabled' => $enabled,
                'signature_id' => $sig?->id,
            ];
        }

        return $result;
    }
}
