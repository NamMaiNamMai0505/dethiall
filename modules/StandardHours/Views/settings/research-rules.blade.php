@extends('layouts.admin')

@section('title', 'Luật quy đổi NCKH')
@section('page-title', 'Luật quy đổi NCKH')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Giờ chuẩn GV', 'url' => route('standard-hours.hub')],
    ['title' => 'Luật quy đổi NCKH']
]" />

<x-page-header title="CHỈNH SỬA LUẬT QUY ĐỔI NCKH" :actions="[[
    'url' => route('standard-hours.hub'), 'label' => 'Quay lại', 'icon' => 'arrow-left', 'color' => 'gray'
]]" />

<div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6 text-sm text-amber-900">
    Thay đổi tại đây sẽ áp dụng ngay cho các kê khai NCKH mới và khi cập nhật kê khai hiện có.
    Dùng khi thông tư quy đổi thay đổi.
</div>

<form action="{{ route('standard-hours.settings.research-rules.update') }}" method="POST" class="bg-white rounded-lg shadow-sm border p-6">
    @csrf @method('PUT')

    <div class="space-y-8">
        <section>
            <h3 class="font-semibold text-gray-900 mb-4">1 thành viên</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Tỷ lệ hưởng giờ</label>
                    <input type="number" step="0.0001" min="0" max="1" name="rules[single][lead]"
                           value="{{ old('rules.single.lead', $rules['single']['lead'] ?? 1) }}" class="form-input w-full" required>
                </div>
            </div>
        </section>

        <section>
            <h3 class="font-semibold text-gray-900 mb-4">2 thành viên</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Chủ nhiệm / chủ biên</label>
                    <input type="number" step="0.0001" min="0" max="1" name="rules[two][lead]"
                           value="{{ old('rules.two.lead', $rules['two']['lead'] ?? 0.6667) }}" class="form-input w-full" required>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Thành viên</label>
                    <input type="number" step="0.0001" min="0" max="1" name="rules[two][member]"
                           value="{{ old('rules.two.member', $rules['two']['member'] ?? 0.3333) }}" class="form-input w-full" required>
                </div>
            </div>
        </section>

        <section>
            <h3 class="font-semibold text-gray-900 mb-4">3 thành viên</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Chủ nhiệm / chủ biên</label>
                    <input type="number" step="0.0001" min="0" max="1" name="rules[three][lead]"
                           value="{{ old('rules.three.lead', $rules['three']['lead'] ?? 0.5) }}" class="form-input w-full" required>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Mỗi thành viên</label>
                    <input type="number" step="0.0001" min="0" max="1" name="rules[three][member]"
                           value="{{ old('rules.three.member', $rules['three']['member'] ?? 0.25) }}" class="form-input w-full" required>
                </div>
            </div>
        </section>

        <section>
            <h3 class="font-semibold text-gray-900 mb-4">Hơn 3 thành viên</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Tỷ lệ cố định chủ nhiệm/chủ biên</label>
                    <input type="number" step="0.0001" min="0" max="1" name="rules[four_plus][lead_fixed]"
                           value="{{ old('rules.four_plus.lead_fixed', $rules['four_plus']['lead_fixed'] ?? 0.3333) }}" class="form-input w-full" required>
                </div>
                <div class="flex items-end gap-4">
                    <label class="inline-flex items-center gap-2">
                        <input type="hidden" name="rules[four_plus][use_contribution_percent]" value="0">
                        <input type="checkbox" name="rules[four_plus][use_contribution_percent]" value="1"
                               {{ old('rules.four_plus.use_contribution_percent', $rules['four_plus']['use_contribution_percent'] ?? true) ? 'checked' : '' }}>
                        <span class="text-sm">Dùng tỷ lệ đóng góp (%)</span>
                    </label>
                </div>
                <div class="flex items-end gap-4">
                    <label class="inline-flex items-center gap-2">
                        <input type="hidden" name="rules[four_plus][equal_split_remainder]" value="0">
                        <input type="checkbox" name="rules[four_plus][equal_split_remainder]" value="1"
                               {{ old('rules.four_plus.equal_split_remainder', $rules['four_plus']['equal_split_remainder'] ?? true) ? 'checked' : '' }}>
                        <span class="text-sm">Chia đều phần còn lại nếu không có %</span>
                    </label>
                </div>
            </div>
        </section>
    </div>

    <div class="flex justify-end gap-3 mt-8 pt-6 border-t">
        <a href="{{ route('standard-hours.hub') }}" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Hủy</a>
        <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium">Lưu và áp dụng</button>
    </div>
</form>
@endsection