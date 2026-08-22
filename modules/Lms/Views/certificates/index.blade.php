@extends('layouts.admin')
@section('title', 'Chứng chỉ — '.$course->title)
@section('page-title', 'Chứng chỉ khóa học')
@section('content')
<a href="{{ route('lms.courses.show', $course) }}" class="text-sm text-blue-600">← {{ $course->title }}</a>
<div class="flex flex-wrap justify-between gap-2 mt-2 mb-4">
    <h1 class="text-xl font-bold">Chứng chỉ hoàn thành</h1>
    <form method="POST" action="{{ route('lms.courses.certificates.issue-eligible', $course) }}">@csrf
        <button class="px-3 py-2 bg-emerald-600 text-white rounded-lg text-sm">Cấp cho HV đủ điều kiện</button>
    </form>
</div>

@php
    $layout = old('layout_json')
        ? json_decode(old('layout_json'), true)
        : ($template->layout_json ?? [
            'bg' => '#f8fafc',
            'border' => '#0f766e',
            'title_y' => 18,
            'name_y' => 42,
            'course_y' => 55,
            'issuer_y' => 78,
            'show_code' => true,
            'show_date' => true,
        ]);
@endphp
<div class="bg-white border rounded-xl p-4 mb-4 space-y-3">
    <h2 class="font-semibold text-sm">Mẫu và điều kiện cấp chứng chỉ</h2>
    <form method="POST" action="{{ route('lms.courses.certificates.template', $course) }}" class="grid sm:grid-cols-2 gap-3 text-sm" id="cert-template-form">
        @csrf
        <input name="title" id="cert-title" required value="{{ old('title', $template->title ?? 'Chứng nhận hoàn thành khóa học') }}" class="border rounded-lg px-3 py-2 sm:col-span-2" placeholder="Tiêu đề chứng chỉ">
        <input name="issuer_name" id="cert-issuer" value="{{ old('issuer_name', $template->issuer_name ?? 'Trường Cao đẳng Hậu cần 2') }}" class="border rounded-lg px-3 py-2" placeholder="Đơn vị cấp">
        <input type="number" step="0.1" name="min_score" value="{{ old('min_score', $template->min_score ?? 5) }}" class="border rounded-lg px-3 py-2" placeholder="Điểm tối thiểu (thang 10)">
        <input type="number" step="1" name="min_progress_pct" value="{{ old('min_progress_pct', $template->min_progress_pct ?? 80) }}" class="border rounded-lg px-3 py-2" placeholder="% tiến độ tối thiểu">
        <label class="flex items-center gap-2"><input type="checkbox" name="require_survey" value="1" @checked(old('require_survey', $template->require_survey ?? false))> Bắt buộc hoàn thành khảo sát</label>
        <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $template->is_active ?? true))> Đang hiệu lực</label>
        <textarea name="body_html" rows="2" class="sm:col-span-2 border rounded-lg px-3 py-2" placeholder="Nội dung thêm (tuỳ chọn)">{{ old('body_html', $template->body_html ?? '') }}</textarea>

        <div class="sm:col-span-2 border rounded-xl p-3 bg-slate-50 space-y-2">
            <div class="font-semibold text-xs uppercase text-slate-500">Layout kéo-thả đơn giản (vị trí % chiều cao)</div>
            <div class="grid sm:grid-cols-3 gap-2">
                <label class="text-xs">Màu nền <input type="color" id="lay-bg" value="{{ $layout['bg'] ?? '#f8fafc' }}" class="block w-full h-8"></label>
                <label class="text-xs">Viền <input type="color" id="lay-border" value="{{ $layout['border'] ?? '#0f766e' }}" class="block w-full h-8"></label>
                <label class="text-xs flex items-center gap-2 mt-4"><input type="checkbox" id="lay-code" @checked($layout['show_code'] ?? true)> Hiện mã CC</label>
                <label class="text-xs">Tiêu đề Y% <input type="range" id="lay-title-y" min="5" max="40" value="{{ $layout['title_y'] ?? 18 }}" class="w-full"></label>
                <label class="text-xs">Tên HV Y% <input type="range" id="lay-name-y" min="30" max="60" value="{{ $layout['name_y'] ?? 42 }}" class="w-full"></label>
                <label class="text-xs">Tên khóa Y% <input type="range" id="lay-course-y" min="45" max="75" value="{{ $layout['course_y'] ?? 55 }}" class="w-full"></label>
                <label class="text-xs">Đơn vị cấp Y% <input type="range" id="lay-issuer-y" min="60" max="90" value="{{ $layout['issuer_y'] ?? 78 }}" class="w-full"></label>
                <label class="text-xs flex items-center gap-2 mt-4"><input type="checkbox" id="lay-date" @checked($layout['show_date'] ?? true)> Hiện ngày cấp</label>
            </div>
            <input type="hidden" name="layout_json" id="layout_json" value="{{ e(json_encode($layout)) }}">
            <div id="cert-preview" class="relative mx-auto mt-2 rounded-lg shadow-inner overflow-hidden"
                 style="width:100%;max-width:520px;aspect-ratio:1.414/1;border:4px solid {{ $layout['border'] ?? '#0f766e' }};background:{{ $layout['bg'] ?? '#f8fafc' }};">
                <div id="pv-title" class="absolute left-0 right-0 text-center font-bold text-teal-900 px-4" style="top:{{ $layout['title_y'] ?? 18 }}%">Chứng nhận hoàn thành</div>
                <div id="pv-name" class="absolute left-0 right-0 text-center text-2xl font-serif text-slate-900 px-4" style="top:{{ $layout['name_y'] ?? 42 }}%">Nguyễn Văn A</div>
                <div id="pv-course" class="absolute left-0 right-0 text-center text-sm text-slate-600 px-6" style="top:{{ $layout['course_y'] ?? 55 }}%">{{ $course->title }}</div>
                <div id="pv-issuer" class="absolute left-0 right-0 text-center text-xs text-slate-500" style="top:{{ $layout['issuer_y'] ?? 78 }}%">Trường Cao đẳng Hậu cần 2</div>
                <div id="pv-meta" class="absolute bottom-3 left-0 right-0 text-center text-[10px] text-slate-400">Mã: DEMO-001 · {{ now()->format('d/m/Y') }}</div>
            </div>
        </div>

        <button class="sm:col-span-2 bg-blue-600 text-white rounded-lg px-3 py-2">Lưu mẫu + layout</button>
    </form>
</div>
@push('scripts')
<script>
(function(){
  function sync(){
    const layout = {
      bg: document.getElementById('lay-bg').value,
      border: document.getElementById('lay-border').value,
      title_y: +document.getElementById('lay-title-y').value,
      name_y: +document.getElementById('lay-name-y').value,
      course_y: +document.getElementById('lay-course-y').value,
      issuer_y: +document.getElementById('lay-issuer-y').value,
      show_code: document.getElementById('lay-code').checked,
      show_date: document.getElementById('lay-date').checked,
    };
    document.getElementById('layout_json').value = JSON.stringify(layout);
    const pv = document.getElementById('cert-preview');
    pv.style.background = layout.bg;
    pv.style.borderColor = layout.border;
    document.getElementById('pv-title').style.top = layout.title_y + '%';
    document.getElementById('pv-title').textContent = document.getElementById('cert-title').value || 'Chứng nhận';
    document.getElementById('pv-name').style.top = layout.name_y + '%';
    document.getElementById('pv-course').style.top = layout.course_y + '%';
    document.getElementById('pv-issuer').style.top = layout.issuer_y + '%';
    document.getElementById('pv-issuer').textContent = document.getElementById('cert-issuer').value || '';
    const bits = [];
    if (layout.show_code) bits.push('Mã: DEMO-001');
    if (layout.show_date) bits.push(new Date().toLocaleDateString('vi-VN'));
    document.getElementById('pv-meta').textContent = bits.join(' · ');
  }
  ['lay-bg','lay-border','lay-title-y','lay-name-y','lay-course-y','lay-issuer-y','lay-code','lay-date','cert-title','cert-issuer']
    .forEach(function(id){ document.getElementById(id)?.addEventListener('input', sync); document.getElementById(id)?.addEventListener('change', sync); });
  sync();
})();
</script>
@endpush

<div class="bg-white border rounded-xl overflow-hidden">
<table class="min-w-full text-sm">
<thead class="bg-slate-50"><tr>
<th class="text-left px-4 py-2">HV</th><th class="text-left px-4 py-2">Mã</th>
<th class="text-left px-4 py-2">Điểm</th><th class="text-left px-4 py-2">Tiến độ</th>
<th class="text-left px-4 py-2">Cấp lúc</th><th class="px-4 py-2"></th>
</tr></thead>
<tbody class="divide-y">
@forelse($certificates as $c)
<tr>
    <td class="px-4 py-2">{{ $c->user?->name }}</td>
    <td class="px-4 py-2 font-mono text-xs">{{ $c->code }}</td>
    <td class="px-4 py-2">{{ $c->final_score ?? '—' }}</td>
    <td class="px-4 py-2">{{ $c->progress_pct !== null ? $c->progress_pct.'%' : '—' }}</td>
    <td class="px-4 py-2">{{ $c->issued_at?->format('d/m/Y H:i') }}</td>
    <td class="px-4 py-2 text-right">
        <a href="{{ route('lms.courses.certificates.show', [$course, $c]) }}" class="text-blue-600">Xem</a>
    </td>
</tr>
@empty
<tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Chưa cấp chứng chỉ nào.</td></tr>
@endforelse
</tbody>
</table>
</div>
<p class="text-xs text-slate-500 mt-3">Xác minh công khai: <a class="text-blue-600" href="{{ route('lms.certificates.verify') }}">{{ route('lms.certificates.verify') }}</a></p>
@endsection
