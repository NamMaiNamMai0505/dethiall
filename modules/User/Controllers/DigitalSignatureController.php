<?php

namespace Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DigitalSignature;
use App\Services\DigitalSignatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DigitalSignatureController extends Controller
{
    public function __construct(
        protected DigitalSignatureService $signatures
    ) {}

    public function index()
    {
        $user = Auth::user();
        $items = $this->signatures->listForUser($user);
        $slots = DigitalSignature::systemSlots();

        return view('user::signatures.index', compact('items', 'slots', 'user'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $data = $request->validate([
            'display_name' => 'required|string|max:255',
            'role_line1' => 'nullable|string|max:255',
            'role_line2' => 'nullable|string|max:255',
            'slot_key' => 'nullable|string|max:64',
            'image' => 'required|image|mimes:png,jpg,jpeg,webp|max:2048',
            'is_default' => 'nullable|boolean',
        ]);

        $path = $this->signatures->storeUpload($request->file('image'), $user);
        $slot = $data['slot_key'] ?? DigitalSignature::SLOT_CUSTOM;
        if (! array_key_exists($slot, DigitalSignature::systemSlots())) {
            $slot = DigitalSignature::SLOT_CUSTOM;
        }

        if (! empty($data['is_default'])) {
            DigitalSignature::query()
                ->where('user_id', $user->id)
                ->where('slot_key', $slot)
                ->update(['is_default' => false]);
        }

        DigitalSignature::query()->create([
            'user_id' => $user->id,
            'slot_key' => $slot,
            'display_name' => $data['display_name'],
            'role_line1' => $data['role_line1'] ?? '',
            'role_line2' => $data['role_line2'] ?? '',
            'image_path' => $path,
            'match_names' => [],
            'is_system_template' => false,
            'is_active' => true,
            'is_default' => ! empty($data['is_default']),
            'sort_order' => 100,
        ]);

        return redirect()->route('signatures.index')->with('success', 'Đã thêm chữ ký.');
    }

    public function update(Request $request, DigitalSignature $signature)
    {
        $user = Auth::user();
        abort_unless($signature->canManage($user), 403);

        $data = $request->validate([
            'display_name' => 'required|string|max:255',
            'role_line1' => 'nullable|string|max:255',
            'role_line2' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
            'user_id' => 'nullable|integer|exists:users,id',
        ]);

        $signature->display_name = $data['display_name'];
        $signature->role_line1 = $data['role_line1'] ?? '';
        $signature->role_line2 = $data['role_line2'] ?? '';
        $signature->is_active = $request->boolean('is_active');
        $signature->is_default = $request->boolean('is_default');

        if ($request->hasFile('image')) {
            $old = $signature->image_path;
            $signature->image_path = $this->signatures->storeUpload($request->file('image'), $user);
            if ($old && ! $signature->is_system_template && Storage::disk('public')->exists($old)) {
                Storage::disk('public')->delete($old);
            }
        }

        if ($signature->is_default) {
            DigitalSignature::query()
                ->where('user_id', $signature->user_id ?: $user->id)
                ->where('slot_key', $signature->slot_key)
                ->where('id', '!=', $signature->id)
                ->update(['is_default' => false]);
        }

        // Super-admin gán chủ sở hữu
        if ($user->isSuperAdmin() && array_key_exists('user_id', $data)) {
            $signature->user_id = $data['user_id'] ?: null;
        }

        $signature->save();

        return redirect()->route('signatures.index')->with('success', 'Đã cập nhật chữ ký.');
    }

    public function destroy(DigitalSignature $signature)
    {
        $user = Auth::user();
        abort_unless($signature->canManage($user), 403);

        // Không xoá hẳn mẫu hệ thống — chỉ unclaim / deactivate
        if ($signature->is_system_template) {
            if ($user->isSuperAdmin()) {
                $signature->user_id = null;
                $signature->save();

                return redirect()->route('signatures.index')->with('success', 'Đã gỡ gán mẫu hệ thống (unclaim).');
            }
            abort(403, 'Không thể xoá mẫu chữ ký hệ thống.');
        }

        $path = $signature->image_path;
        $signature->delete();
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        return redirect()->route('signatures.index')->with('success', 'Đã xoá chữ ký.');
    }

    public function claim()
    {
        $user = Auth::user();
        $claimed = $this->signatures->claimMatchingTemplates($user);

        return redirect()->route('signatures.index')->with(
            'success',
            $claimed->isEmpty()
                ? 'Không có mẫu chữ ký nào khớp tên tài khoản của bạn.'
                : 'Đã nhận '.$claimed->count().' chữ ký mẫu khớp tên.'
        );
    }

    public function adminClaimAll()
    {
        $user = Auth::user();
        abort_unless($user && $user->isSuperAdmin(), 403);
        $n = $this->signatures->seedSystemTemplates();
        $c = $this->signatures->claimAllUsers();

        return redirect()->route('signatures.index')->with(
            'success',
            "Đã seed {$n} mẫu, claim {$c} chữ ký theo tên user."
        );
    }
}
