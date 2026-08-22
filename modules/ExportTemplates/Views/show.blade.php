@extends('layouts.admin')
@section('title', $template->name)
@section('content')
<x-breadcrumb :items="[['title'=>'Trang chủ'],['title'=>'Mẫu xuất','url'=>route('export-templates.index')],['title'=>$template->name]]" />
<x-page-header title="{{ $template->name }}" :actions="[['url'=>route('export-templates.index'),'label'=>'Danh sách','icon'=>'list','color'=>'gray']]" />

<div class="grid lg:grid-cols-2 gap-4">
    <div class="bg-white rounded-lg border p-5 text-sm space-y-2">
        <div><span class="text-slate-500">Phạm vi:</span> <strong>{{ $template->scopeLabel() }}</strong></div>
        <div><span class="text-slate-500">Feature:</span> <code class="bg-slate-100 px-1 rounded">{{ $template->feature_key }}</code></div>
        <div><span class="text-slate-500">File:</span> {{ $template->original_name }}</div>
        <div><span class="text-slate-500">Ghi chú:</span> {{ $template->notes ?: '—' }}</div>
    </div>
    <div class="bg-white rounded-lg border p-5 text-sm">
        <h3 class="font-semibold mb-2">Placeholders phát hiện</h3>
        @forelse($template->placeholders ?? [] as $ph)
            <code class="inline-block bg-teal-50 text-teal-900 px-2 py-0.5 rounded mr-1 mb-1">{{'{{'.$ph.'}}'}}</code>
        @empty
            <p class="text-slate-500">Không có <code>{{'{{var}}'}}</code> trong file — dùng gợi ý nhãn bên dưới.</p>
        @endforelse
        <h3 class="font-semibold mt-4 mb-2">Gợi ý nhãn (AI light)</h3>
        <ul class="space-y-1 max-h-64 overflow-y-auto text-xs">
            @forelse($template->cell_map['hints'] ?? [] as $h)
                <li class="border-b border-slate-50 py-1">
                    <span class="font-mono text-slate-500">{{ $h['cell'] }}</span>
                    — {{ \Illuminate\Support\Str::limit($h['label'], 40) }}
                    → <strong>{{ $h['suggest'] ?? '?' }}</strong>
                </li>
            @empty
                <li class="text-slate-400">Không có gợi ý</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
