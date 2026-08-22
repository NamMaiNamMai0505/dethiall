@extends('layouts.admin')

@section('title', 'Bộ môn')
@section('page-title', 'Bộ môn')

@section('content')
    <x-breadcrumb :items="[
        ['title' => 'Trang chủ'],
        ['title' => 'Giờ chuẩn GV', 'url' => route('standard-hours.hub')],
        ['title' => 'Bộ môn'],
    ]" />

    <x-page-header
        title="DANH SÁCH BỘ MÔN"
        subtitle="Bộ môn thuộc khoa — gán giảng viên để tính vượt định mức (TT 06/2026 Đ.17). Các module khác vẫn lọc theo khoa."
        :actions="[
            \Modules\StandardHours\Support\HubNavigation::backAction(),
            [
                'url' => route('standard-hours.departments.create'),
                'label' => 'Thêm bộ môn',
                'icon' => 'plus',
                'color' => 'blue',
            ],
        ]"
    />

    <x-filter-form
        :action="route('standard-hours.departments.index')"
        :clear-url="route('standard-hours.departments.index')"
        :filters="[
            [
                'type' => 'search',
                'name' => 'search',
                'placeholder' => 'Tìm tên hoặc mã bộ môn...',
            ],
            [
                'type' => 'select',
                'name' => 'unit_id',
                'placeholder' => 'Tất cả khoa',
                'options' => collect($units)->mapWithKeys(fn ($u) => [$u->id => $u->name])->all(),
            ],
            [
                'type' => 'select',
                'name' => 'status',
                'placeholder' => 'Tất cả trạng thái',
                'options' => [
                    'active' => 'Đang sử dụng',
                    'inactive' => 'Ngừng sử dụng',
                ],
            ],
        ]"
    />

    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        @if($departments->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-100 text-slate-700 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold w-14">#</th>
                            <x-table.sort-header column="code" label="Mã" />
                            <th class="px-4 py-3 text-left font-semibold">Bộ môn</th>
                            <th class="px-4 py-3 text-left font-semibold">Khoa</th>
                            <th class="px-4 py-3 text-center font-semibold">Số GV</th>
                            <th class="px-4 py-3 text-left font-semibold">Trạng thái</th>
                            <th class="px-4 py-3 text-right font-semibold w-44">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($departments as $i => $d)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-4 py-3 text-slate-400 tabular-nums">
                                    {{ $departments->firstItem() + $i }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex font-mono text-xs font-semibold text-slate-700 bg-slate-100 px-2 py-0.5 rounded">
                                        {{ $d->code }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-900">{{ $d->name }}</div>
                                    @if($d->description)
                                        <div class="text-xs text-slate-400 mt-0.5 line-clamp-1">{{ $d->description }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    {{ $d->unit?->name ?? '—' }}
                                    @if($d->unit?->code)
                                        <span class="text-xs text-slate-400">({{ $d->unit->code }})</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center justify-center min-w-[2rem] px-2 py-0.5 rounded-full text-xs font-semibold
                                        {{ $d->instructors_count > 0 ? 'bg-teal-50 text-teal-800' : 'bg-slate-100 text-slate-500' }}">
                                        {{ $d->instructors_count }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    @if($d->is_active)
                                        <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Đang dùng
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-xs font-medium text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Tắt
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end items-center gap-1.5">
                                        <a href="{{ route('standard-hours.departments.show', $d) }}"
                                           class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold
                                                  text-blue-700 bg-blue-50 hover:bg-blue-100 transition"
                                           title="Chi tiết & gán GV">
                                            <i class="bi bi-eye"></i>
                                            <span class="hidden sm:inline">Chi tiết</span>
                                        </a>
                                        <a href="{{ route('standard-hours.department-overtime.show', $d) }}"
                                           class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold
                                                  text-amber-800 bg-amber-50 hover:bg-amber-100 transition"
                                           title="Vượt định mức">
                                            <i class="bi bi-graph-up-arrow"></i>
                                            <span class="hidden sm:inline">Vượt DM</span>
                                        </a>
                                        @can('standard-hours.departments.manage')
                                            <a href="{{ route('standard-hours.departments.edit', $d) }}"
                                               class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-600
                                                      hover:bg-slate-100 transition"
                                               title="Sửa">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-slate-100">
                {{ $departments->links() }}
            </div>
        @else
            <div class="px-6 py-16 text-center">
                <div class="mx-auto w-14 h-14 rounded-full bg-slate-100 flex items-center justify-center mb-3">
                    <i class="bi bi-diagram-3 text-2xl text-slate-400"></i>
                </div>
                <p class="font-semibold text-slate-800 mb-1">Chưa có bộ môn</p>
                <p class="text-sm text-slate-500 mb-4 max-w-sm mx-auto">
                    Tạo bộ môn thuộc khoa, gán giảng viên, rồi dùng để tính vượt định mức giờ chuẩn.
                </p>
                @can('standard-hours.departments.manage')
                    <a href="{{ route('standard-hours.departments.create') }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition">
                        <i class="bi bi-plus-lg"></i> Thêm bộ môn
                    </a>
                @endcan
            </div>
        @endif
    </div>
@endsection
