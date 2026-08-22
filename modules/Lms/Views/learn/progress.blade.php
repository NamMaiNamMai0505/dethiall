@extends('layouts.lms-learner')
@section('title', 'Tiến độ — '.$course->title)
@section('content')
<a href="{{ route('lms.learn.courses.show', $course) }}" class="text-sm text-teal-700">← Phòng học</a>
<h1 class="text-xl font-bold mt-2 mb-4">Tiến độ học tập</h1>
@php $mine = $summaries->firstWhere('user_id', auth()->id()); @endphp
<div class="lms-card p-6 max-w-lg" id="progress-panel"
     data-poll="{{ route('lms.learn.progress.poll', $course) }}">
    <div class="text-center mb-4">
        <div class="text-sm text-slate-500">Hoàn thành</div>
        <div class="text-5xl font-bold text-teal-700" data-overall>{{ $mine->overall_pct ?? 0 }}%</div>
        <div class="mt-3 h-3 bg-slate-100 rounded-full overflow-hidden max-w-xs mx-auto">
            <div data-bar class="h-full bg-teal-500 transition-all" style="width:{{ min(100, $mine->overall_pct ?? 0) }}%"></div>
        </div>
    </div>
    <ul class="text-sm space-y-2">
        <li class="flex justify-between"><span>Bài học</span><span data-lessons>{{ ($mine->lessons_done ?? 0).'/'.($mine->lessons_total ?? 0) }}</span></li>
        <li class="flex justify-between"><span>Học liệu</span><span data-materials>{{ ($mine->materials_done ?? 0).'/'.($mine->materials_total ?? 0) }}</span></li>
        <li class="flex justify-between"><span>Bài tập</span><span data-assignments>{{ ($mine->assignments_done ?? 0).'/'.($mine->assignments_total ?? 0) }}</span></li>
        <li class="flex justify-between"><span>Thi</span><span data-exams>{{ ($mine->exams_done ?? 0).'/'.($mine->exams_total ?? 0) }}</span></li>
    </ul>
    <p class="text-xs text-slate-400 mt-4 text-center">Tự cập nhật mỗi 8 giây</p>
</div>
@push('scripts')
<script>
(function(){
  const p=document.getElementById('progress-panel'); if(!p) return;
  const url=p.dataset.poll;
  async function tick(){
    try{
      const r=await fetch(url,{headers:{'Accept':'application/json'}});
      if(!r.ok) return;
      const j=await r.json();
      p.querySelector('[data-overall]').textContent=(j.overall_pct||0)+'%';
      p.querySelector('[data-bar]').style.width=Math.min(100,j.overall_pct||0)+'%';
      p.querySelector('[data-lessons]').textContent=(j.lessons||[]).join('/');
      p.querySelector('[data-materials]').textContent=(j.materials||[]).join('/');
      p.querySelector('[data-assignments]').textContent=(j.assignments||[]).join('/');
      p.querySelector('[data-exams]').textContent=(j.exams||[]).join('/');
    }catch(e){}
  }
  setInterval(tick,8000);
})();
</script>
@endpush
@endsection
