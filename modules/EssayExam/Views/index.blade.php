@extends('layouts.admin')
@section('title','Đề thi')
@section('page-title','Đề thi')
@section('content')
@include('partials.module-menu', ['module' => 'exam'])
@php($mine=$mine??false)
@if(auth()->user()->can('essay-exams.authoring.index'))
    <div class="flex justify-end mb-4"><a href="{{ route('essay-exams.create') }}" class="px-4 py-2 rounded-lg bg-blue-600 text-white">Soạn / Import đề</a></div>
@endif
<h1 class="text-2xl font-bold mb-5">{{ $mine?'Đề của tôi':'Danh sách đề thi' }}</h1>
<form id="essay-list-filter" method="GET" action="{{ route($mine ? 'essay-exams.mine' : 'essay-exams.index') }}" data-turbo="false" class="bg-white border rounded-lg p-4 mb-4 grid gap-3 md:grid-cols-2 xl:grid-cols-5">
    <input name="search" value="{{ request('search') }}" placeholder="Tìm mã hoặc tên đề..." aria-label="Tìm mã hoặc tên đề" class="border rounded-lg px-3 py-2">
    <select name="training_system_id" id="essay-filter-system" data-native-select="1" class="border rounded-lg px-3 py-2"><option value="">Tất cả hệ đào tạo</option>@foreach($trainingSystems as $system)<option value="{{ $system->id }}" @selected(request('training_system_id') == $system->id)>{{ $system->name }}</option>@endforeach</select>
    <select name="specialization_id" id="essay-filter-specialization" data-native-select="1" class="border rounded-lg px-3 py-2"><option value="">Tất cả ngành</option>@foreach($specializations as $specialization)<option value="{{ $specialization->id }}" data-system="{{ $specialization->training_system_id }}" @selected(request('specialization_id') == $specialization->id)>{{ $specialization->name }}</option>@endforeach</select>
    <select name="class_id" id="essay-filter-class" data-native-select="1" class="border rounded-lg px-3 py-2"><option value="">Tất cả lớp</option>@foreach($classes as $class)<option value="{{ $class->id }}" data-specialization="{{ $class->specialization_id }}" data-system="{{ $class->specialization?->training_system_id }}" @selected(request('class_id') == $class->id)>{{ $class->code }} - {{ $class->name }}</option>@endforeach</select>
    <a href="{{ route($mine ? 'essay-exams.mine' : 'essay-exams.index') }}" data-turbo="false" class="px-4 py-2 rounded-lg border text-center">Xóa lọc</a>
</form>
<div class="space-y-5">@if($exams->count()) @foreach($exams->groupBy(fn($e)=>(string)($e->class?->specialization_id??0).'|'.(string)($e->class_id??0)) as $group) @php($first=$group->first())<section class="bg-white border rounded-xl overflow-hidden"><div class="px-4 py-3 bg-blue-50 font-bold">Ngành: {{ $first->class?->specialization?->name ?? 'Chưa phân ngành' }} · Lớp: {{ $first->class?->name ?? 'Chưa gán lớp' }}</div><table class="w-full text-sm"><thead class="bg-slate-100"><tr><th class="p-3 text-left">Mã đề</th><th class="p-3 text-left">Bộ đề</th><th class="p-3 text-left">Môn</th><th class="p-3">Đề số</th><th class="p-3">Câu hỏi</th><th class="p-3">Trạng thái</th><th></th></tr></thead><tbody class="divide-y">@foreach($group as $exam) @foreach($exam->questions->groupBy('paper_number') as $paper=>$questions) @php($status=$questions->first()->paper_status??$exam->status) @php($labels=['DRAFT'=>'Bản nháp','PENDING_DEPT'=>'Chờ khoa duyệt','PENDING_EXAM_OFFICE'=>'Chờ khảo thí duyệt','PENDING_BGH'=>'Chờ BGH duyệt','APPROVED'=>'Đã duyệt','RETURNED'=>'Trả lại'])<tr><td class="p-3 font-semibold">{{ $exam->paperCode((int) $paper) }}</td><td class="p-3">{{ $exam->title }}</td><td class="p-3">{{ $exam->subject?->name }}</td><td class="p-3 text-center font-bold">{{ $paper }}</td><td class="p-3 text-center">{{ $questions->count() }}</td><td class="p-3"><span class="px-2 py-1 rounded-full text-xs bg-slate-100">{{ $labels[$status]??$status }}</span></td><td class="p-3"><a class="text-blue-600" href="{{ route('essay-exams.show',$exam) }}?paper={{ $paper }}">Xem</a></td></tr>@endforeach @endforeach</tbody></table></section>@endforeach @else<div class="bg-white border rounded-xl p-10 text-center text-slate-500">Chưa có đề thi tự luận.</div>@endif</div>
<div class="mt-4">{{ $exams->links() }}</div>
<script>
(() => {
const essayFilterForm = document.getElementById('essay-list-filter');
if (!essayFilterForm) return;
const searchFilter = essayFilterForm.querySelector('input[name="search"]');
const systemFilter = document.getElementById('essay-filter-system');
const specializationFilter = document.getElementById('essay-filter-specialization');
const classFilter = document.getElementById('essay-filter-class');
function refreshEssayFilters() {
    const systemId = systemFilter.value;
    [...specializationFilter.options].slice(1).forEach(option => { option.hidden = !!systemId && option.dataset.system !== systemId; });
    if (specializationFilter.selectedOptions[0]?.hidden) specializationFilter.value = '';
    const specializationId = specializationFilter.value;
    [...classFilter.options].slice(1).forEach(option => {
        option.hidden = (!!systemId && option.dataset.system !== systemId)
            || (!!specializationId && option.dataset.specialization !== specializationId);
    });
    if (classFilter.selectedOptions[0]?.hidden) classFilter.value = '';
}
const submitEssayFilters = () => essayFilterForm.requestSubmit ? essayFilterForm.requestSubmit() : essayFilterForm.submit();
systemFilter.addEventListener('change', () => { refreshEssayFilters(); submitEssayFilters(); });
specializationFilter.addEventListener('change', () => { refreshEssayFilters(); submitEssayFilters(); });
classFilter.addEventListener('change', submitEssayFilters);
let searchTimer;
searchFilter.addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(submitEssayFilters, 450);
});
refreshEssayFilters();
})();
</script>
@endsection
