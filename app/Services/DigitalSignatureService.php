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
        $name = 'sig_'.time().'_'.Str::random(6).'.'.$file->getClientOriginalExtension();

        return $file->storeAs($dir, $name, 'public');
    }

    /**
     * Chữ ký user được phép quản lý (owned + super-admin xem all owned by self).
     *
     * @return Collection<int, DigitalSignature>
     */
    public function listForUser(User $user): Collection
    {
        $this->seedSystemTemplates();
        $this->claimMatchingTemplates($user);

        return DigitalSignature::query()
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id);
                if ($user->isSuperAdmin()) {
                    // Super-admin cũng thấy mẫu chưa claim để gán
                    $q->orWhere(function ($q2) {
                        $q2->where('is_system_template', true);
                    });
                }
            })
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
