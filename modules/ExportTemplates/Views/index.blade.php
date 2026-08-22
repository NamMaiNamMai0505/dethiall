@extends('layouts.admin')
@section('title', 'Mẫu xuất')
@section('content')
<x-breadcrumb :items="[['title'=>'Trang chủ'],['title'=>'Mẫu xuất']]" />
<x-page-header title="MẪU XUẤT CHUNG (Dashboard · LMS · Điểm)" :actions="array_filter([
    auth()->user()->can('export-templates.create') ? ['url'=>route('export-templates.create'),'label'=>'Tải mẫu lên','icon'=>'upload','color'=>'blue'] : null,
])" />

<div class="mb-4 flex flex-wrap gap-2 text-sm">
    @foreach([''=> 'Tất cả','dashboard'=>'Dashboard','lms'=>'LMS','grades'=>'Điểm','shared'=>'Chung'] as $k=>$lab)
        <a href="{{ route('export-templates.index', $k ? ['scope'=>$k] : []) }}"
           class="px-3 py-1.5 rounded-lg border {{ ($scope??'')===$k || ($k==='' && empty($scope)) ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-slate-700' }}">{{ $lab }}</a>
    @endforeach
    @if(Route::has('grades.hub'))
        <a href="{{ route('grades.hub') }}" class="px-3 py-1.5 rounded-lg border border-orange-200 text-orange-800 bg-orange-50" data-turbo="false">→ Quản lý điểm</a>
    @endif
    @if(Route::has('lms.hub'))
        <a href="{{ route('lms.hub') }}" class="px-3 py-1.5 rounded-lg border border-teal-200 text-teal-800 bg-teal-50" data-turbo="false">→ LMS</a>
    @endif
    <a href="{{ route('dashboard') }}" class="px-3 py-1.5 rounded-lg border bg-white">→ Dashboard</a>
</div>

<div class="bg-white rounded-lg shadow border overflow-hidden">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-100 text-xs uppercase text-slate-600">
        <tr>
            <th class="px-3 py-2 text-left">Tên</th>
            <th class="px-3 py-2 text-left">Phạm vi</th>
            <th class="px-3 py-2 text-left">Feature</th>
            <th class="px-3 py-2 text-left">Placeholders</th>
            <th class="px-3 py-2 text-left">File</th>
            <th class="px-3 py-2"></th>
        </tr>
        </thead>
        <tbody class="divide-y">
        @forelse($templates as $t)
            <tr class="hover:bg-slate-50">
                <td class="px-3 py-2 font-medium">{{ $t->name }}</td>
                <td class="px-3 py-2"><span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-100">{{ $t->scopeLabel() }}</span></td>
                <td class="px-3 py-2 font-mono text-xs">{{ $t->feature_key }}</td>
                <td class="px-3 py-2 text-xs text-slate-600">{{ count($t->placeholders ?? []) }} · gợi ý {{ count($t->cell_map['hints'] ?? []) }}</td>
                <td class="px-3 py-2 text-xs">{{ $t->original_name }}</td>
                <td class="px-3 py-2 text-right space-x-2">
                    <a href="{{ route('export-templates.show', $t) }}" class="text-blue-600">Chi tiết</a>
                    <form action="{{ route('export-templates.destroy', $t) }}" method="POST" class="inline" data-confirm="Xoá mẫu?">
                        @csrf @method('DELETE')
                        <button class="text-rose-600">Xoá</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">Chưa có mẫu. Tải xlsx/docx có <code>@{{student_name}}</code> hoặc nhãn cột.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="p-3">{{ $templates->links() }}</div>
</div>
@endsection
