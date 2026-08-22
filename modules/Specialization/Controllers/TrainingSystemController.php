<?php

namespace Modules\Specialization\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Specialization\Models\TrainingSystem;

class TrainingSystemController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:specializations.index']);
    }

    public function index()
    {
        $systems = TrainingSystem::query()
            ->withCount('specializations')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('specialization::training-systems.index', compact('systems'));
    }

    public function store(Request $request)
    {
        $this->middleware(['permission:specializations.create']);
        abort_unless(auth()->user()?->can('specializations.create') || auth()->user()?->can('specializations.edit'), 403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:40|alpha_dash|unique:training_systems,code',
            'description' => 'nullable|string|max:1000',
            'sort_order' => 'nullable|integer|min:0|max:999',
            'is_active' => 'nullable|boolean',
        ]);

        if (empty($data['code'])) {
            $data['code'] = Str::slug($data['name'], '_');
            $base = $data['code'];
            $i = 1;
            while (TrainingSystem::query()->where('code', $data['code'])->exists()) {
                $data['code'] = $base.'_'.$i++;
            }
        }

        TrainingSystem::create([
            'name' => $data['name'],
            'code' => $data['code'],
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Đã thêm hệ đào tạo.');
    }

    public function update(Request $request, TrainingSystem $trainingSystem)
    {
        abort_unless(auth()->user()?->can('specializations.edit'), 403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:40|alpha_dash|unique:training_systems,code,'.$trainingSystem->id,
            'description' => 'nullable|string|max:1000',
            'sort_order' => 'nullable|integer|min:0|max:999',
            'is_active' => 'nullable|boolean',
        ]);

        $trainingSystem->update([
            'name' => $data['name'],
            'code' => $data['code'],
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? $trainingSystem->sort_order,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Đã cập nhật hệ đào tạo.');
    }

    public function destroy(TrainingSystem $trainingSystem)
    {
        abort_unless(auth()->user()?->can('specializations.delete'), 403);

        if ($trainingSystem->specializations()->exists()) {
            return back()->with('error', 'Không xóa được: còn ngành thuộc hệ này.');
        }

        $trainingSystem->delete();

        return back()->with('success', 'Đã xóa hệ đào tạo.');
    }
}
