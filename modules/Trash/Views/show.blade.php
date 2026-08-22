@extends('layouts.admin')

@section('title', 'Chi tiết thùng rác')
@section('page-title', 'Chi tiết thùng rác')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Thùng rác', 'url' => route('trash.index')],
    ['title' => 'Chi tiết'],
]" />

<x-page-header
    title="CHI TIẾT MỤC ĐÃ XÓA"
    :actions="[
        ['url' => route('trash.index'), 'label' => 'Quay lại', 'icon' => 'arrow-left', 'color' => 'gray'],
    ]" />

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-start gap-3 mb-6">
            <span class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-slate-100 text-slate-700 text-xl">
                <i class="bi {{ $item['icon'] }}"></i>
            </span>
            <div>
                <p class="text-sm text-gray-500">{{ $item['type_label'] }}</p>
                <h2 class="text-xl font-bold text-gray-900">{{ $item['title'] }}</h2>
                @if($item['identifier'])
                    <p class="font-mono text-blue-700 mt-1">{{ $item['identifier'] }}</p>
                @endif
            </div>
        </div>

        <h3 class="text-sm font-semibold text-gray-700 mb-3 uppercase tracking-wide">Thông tin giá trị đã xóa</h3>
        @if(is_array($item['summary']) && count($item['summary']))
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach($item['summary'] as $key => $value)
                    <div class="border rounded-lg p-3 bg-slate-50">
                        <dt class="text-xs text-gray-500">{{ $key }}</dt>
                        <dd class="text-sm font-medium text-gray-900 mt-0.5 break-words">
                            {{ is_scalar($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}
                        </dd>
                    </div>
                @endforeach
            </dl>
        @else
            <p class="text-gray-500 text-sm">Không có snapshot chi tiết. Dữ liệu gốc vẫn còn trong DB (soft delete).</p>
        @endif
    </div>

    <div class="space-y-4">
        <div class="bg-white rounded-lg shadow-sm border p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-3 uppercase tracking-wide">Siêu dữ liệu</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-2">
                    <dt class="text-gray-500">Module</dt>
                    <dd class="font-mono text-gray-900">{{ $item['module_key'] }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-gray-500">Model</dt>
                    <dd class="font-mono text-xs text-gray-900 text-right break-all">{{ class_basename($item['model_type']) }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-gray-500">ID</dt>
                    <dd class="font-medium text-gray-900">{{ $item['model_id'] }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-gray-500">Xóa lúc</dt>
                    <dd class="text-gray-900">{{ $item['deleted_at']?->format('d/m/Y H:i:s') ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-gray-500">Người xóa</dt>
                    <dd class="text-gray-900">{{ $item['deleted_by_name'] ?? 'Không ghi nhận' }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-gray-500">Thời hạn</dt>
                    <dd class="text-green-700 font-medium">Vĩnh viễn (không auto-xóa)</dd>
                </div>
            </dl>
        </div>

        <div class="bg-white rounded-lg shadow-sm border p-5 space-y-3">
            <form method="POST" action="{{ route('trash.restore', [$item['module_key'], $item['model_id']]) }}"
                  data-confirm='Khôi phục mục này về hệ thống?'>
                @csrf
                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium">
                    <i class="bi bi-arrow-clockwise" aria-hidden="true"></i> Khôi phục
                </button>
            </form>

            @if(auth()->user()->isSuperAdmin())
                <form method="POST" action="{{ route('trash.force-delete', [$item['module_key'], $item['model_id']]) }}"
                      data-confirm='XÓA VĨNH VIỄN? Hành động không thể hoàn tác.'>
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium">
                        <i class="bi bi-trash3-fill" aria-hidden="true"></i> Xóa vĩnh viễn
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
