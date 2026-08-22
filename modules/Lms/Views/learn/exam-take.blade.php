@extends('layouts.lms-learner')
@section('title', 'Làm bài — '.$exam->title)
@section('content')
@php
    $proctorOn = (bool) ($exam->proctor_basic || $exam->require_fullscreen);
    // @json() dùng explode(',', ...) khi tách tham số encode-options nên biểu thức
    // có >= 2 dấu phẩy (route('x', [$a, $b, $c])) bị cắt cụt lúc compile — phải gán
    // ra biến trước rồi mới @json($var).
    $heartbeatUrl = route('lms.learn.exams.proctor', [$course, $exam, $attempt]);
@endphp
<div class="max-w-5xl mx-auto space-y-4" id="exam-root"
     data-proctor="{{ $proctorOn ? '1' : '0' }}"
     data-require-fs="{{ $exam->require_fullscreen ? '1' : '0' }}"
     data-auto-submit="{{ $exam->auto_submit_on_leave ? '1' : '0' }}"
     data-max-blur="{{ $exam->max_blur_events ?? '' }}">
    <div class="lms-card sticky z-10 overflow-hidden" id="exam-header">
        <div class="flex items-center justify-between gap-3 px-5 py-3.5 bg-gradient-to-r from-teal-600 to-teal-500 text-white">
            <div class="min-w-0">
                <h1 class="font-bold text-base truncate">{{ $exam->title }}</h1>
                <p class="text-xs text-teal-50/90">{{ $ordered->count() }} câu · {{ $exam->duration_minutes }} phút</p>
            </div>
            <div class="text-right shrink-0">
                <div class="text-[11px] text-teal-50/90 uppercase tracking-wide">Còn lại</div>
                <div id="exam-timer" class="text-2xl font-mono font-bold leading-none" data-ends="{{ $endsAt?->toIso8601String() }}">--:--</div>
            </div>
        </div>
        <div class="h-2.5 bg-teal-950/40 px-1 py-0.5">
            <div id="exam-timer-bar" class="h-full rounded-full bg-white shadow-[0_0_4px_rgba(0,0,0,0.25)] transition-[width,background-color] duration-500 ease-linear" style="width:100%"></div>
        </div>
    </div>

    @if($proctorOn)
        <div class="lms-card px-4 py-3 text-sm bg-amber-50 border border-amber-200 space-y-1.5 rounded-xl">
            <div class="font-semibold text-amber-900 flex items-center gap-1.5"><i class="bi bi-shield-exclamation"></i> Giám sát thi (không webcam / không screen share)</div>
            <ul class="text-xs text-amber-900/90 list-disc pl-4 space-y-0.5">
                <li>Ghi nhận: rời tab, ẩn cửa sổ, thoát fullscreen.</li>
                @if($exam->require_fullscreen)
                    <li><strong>Bắt buộc fullscreen</strong> trước khi làm bài.</li>
                @endif
                @if($exam->auto_submit_on_leave)
                    <li><strong>Tự nộp</strong> nếu rời tab / thoát fullscreen quá giới hạn.</li>
                @endif
                @if($exam->max_blur_events)
                    <li>Tối đa <strong>{{ $exam->max_blur_events }}</strong> sự kiện rời tab trước khi tự nộp.</li>
                @endif
            </ul>
            <div class="text-xs mt-1 flex items-center gap-3">
                <span>Sự kiện proctor: <strong id="proctor-count">0</strong></span>
                <span>Blur: <strong id="blur-count">0</strong></span>
            </div>
        </div>
        @if($exam->require_fullscreen)
            <div id="fs-gate" class="lms-card p-8 text-center space-y-3 border-2 border-teal-200 bg-teal-50/60 rounded-2xl">
                <i class="bi bi-arrows-fullscreen text-3xl text-teal-600"></i>
                <p class="font-semibold text-teal-900">Bài thi yêu cầu chế độ toàn màn hình</p>
                <p class="text-sm text-slate-600">Bấm nút bên dưới để bắt đầu. Không dùng webcam hay chia sẻ màn hình.</p>
                <button type="button" id="btn-enter-fs" class="lms-btn-solid">Vào fullscreen &amp; làm bài</button>
            </div>
        @endif
    @endif

    <div class="lg:grid lg:grid-cols-[1fr_15rem] lg:gap-4 lg:items-start">
        <form id="exam-form" method="POST" action="{{ route('lms.learn.exams.submit', [$course, $exam, $attempt]) }}" class="space-y-3.5 {{ $exam->require_fullscreen ? 'hidden' : '' }}">
            @csrf
            <input type="hidden" name="proctor_events" id="proctor_events" value="[]">
            @foreach($ordered as $i => $q)
                <div class="lms-card p-5 rounded-2xl space-y-3 transition-shadow hover:shadow-md" id="exam-q-{{ $i+1 }}" data-question-id="{{ $q->id }}">
                    <div class="flex items-start gap-3">
                        <span class="shrink-0 h-7 w-7 rounded-full bg-teal-50 text-teal-700 text-xs font-bold flex items-center justify-center">{{ $i+1 }}</span>
                        <div class="min-w-0 flex-1 space-y-1">
                            <div class="whitespace-pre-wrap font-medium text-slate-800">{{ $q->stem }}</div>
                            <div class="text-[11px] text-slate-400">{{ $q->points }} điểm</div>
                        </div>
                    </div>
                    <div class="pl-10 space-y-1.5">
                        @if($q->type === 'mcq')
                            @foreach(($q->display_options ?? []) as $opt)
                                <label class="flex gap-2 text-sm p-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 hover:border-teal-300 cursor-pointer transition-colors has-[:checked]:bg-teal-50 has-[:checked]:border-teal-400 has-[:checked]:text-teal-900">
                                    <input type="radio" name="answers[{{ $q->id }}]" value="{{ $opt['key'] }}" class="accent-teal-600 mt-0.5">
                                    <span>{{ $opt['text'] }}</span>
                                </label>
                            @endforeach
                        @elseif($q->type === 'true_false')
                            <div class="flex gap-2">
                                <label class="flex-1 flex items-center justify-center gap-2 text-sm p-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 hover:border-teal-300 cursor-pointer transition-colors has-[:checked]:bg-teal-50 has-[:checked]:border-teal-400 has-[:checked]:text-teal-900">
                                    <input type="radio" name="answers[{{ $q->id }}]" value="true" class="accent-teal-600"> Đúng
                                </label>
                                <label class="flex-1 flex items-center justify-center gap-2 text-sm p-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 hover:border-teal-300 cursor-pointer transition-colors has-[:checked]:bg-teal-50 has-[:checked]:border-teal-400 has-[:checked]:text-teal-900">
                                    <input type="radio" name="answers[{{ $q->id }}]" value="false" class="accent-teal-600"> Sai
                                </label>
                            </div>
                        @else
                            <input type="text" name="answers[{{ $q->id }}]" class="w-full border border-slate-200 rounded-xl text-sm px-3 py-2.5 focus:border-teal-400 focus:ring-1 focus:ring-teal-400 outline-none" placeholder="Nhập đáp án…">
                        @endif
                    </div>
                </div>
            @endforeach
            <div class="flex justify-end pb-8 pt-1">
                <button type="button" id="btn-submit-exam" class="px-6 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-medium shadow-sm transition-colors">Nộp bài</button>
            </div>
        </form>

        {{-- Hộp xác nhận nộp bài — tự viết riêng cho trang này, không dùng
             hệ thống confirm-modal dùng chung, vì bài thi là hành động
             không thể hoàn tác nên cần chắc chắn tuyệt đối, không phụ
             thuộc code/trạng thái ở nơi khác. --}}
        <div id="exam-submit-confirm" class="fixed inset-0 z-[10070] hidden items-center justify-center p-4 bg-slate-900/50">
            <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6 space-y-4">
                <div class="flex items-start gap-3">
                    <span class="shrink-0 h-10 w-10 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center">
                        <i class="bi bi-exclamation-triangle text-lg"></i>
                    </span>
                    <div>
                        <h3 class="font-semibold text-slate-900">Nộp bài thi?</h3>
                        <p class="text-sm text-slate-600 mt-1">Bạn không thể sửa đáp án sau khi nộp. Kiểm tra kỹ trước khi xác nhận.</p>
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-1">
                    <button type="button" id="btn-cancel-submit-exam" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-sm font-medium">Hủy</button>
                    <button type="button" id="btn-confirm-submit-exam" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-sm font-medium">Nộp bài</button>
                </div>
            </div>
        </div>

        {{-- Bảng theo dõi câu đã làm / chưa làm --}}
        <aside class="lms-card p-4 space-y-3 rounded-2xl order-first lg:order-last lg:sticky mb-4 lg:mb-0" id="exam-nav">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-600">Câu hỏi</span>
                <span class="text-xs font-semibold text-teal-700"><span id="exam-nav-done">0</span>/{{ $ordered->count() }}</span>
            </div>
            <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden">
                <div id="exam-nav-progress" class="h-full bg-teal-500 transition-[width] duration-300" style="width:0%"></div>
            </div>
            <div class="grid grid-cols-8 sm:grid-cols-10 lg:grid-cols-5 gap-1.5" id="exam-nav-grid">
                @foreach($ordered as $i => $q)
                    <button type="button"
                            class="exam-nav-item h-8 w-8 rounded-lg border border-slate-200 text-xs font-medium text-slate-500 flex items-center justify-center transition-colors hover:border-teal-400 hover:text-teal-700"
                            data-question-id="{{ $q->id }}"
                            data-nav-target="exam-q-{{ $i+1 }}">{{ $i+1 }}</button>
                @endforeach
            </div>
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                <span class="flex items-center gap-1 pt-2"><span class="h-3 w-3 rounded bg-teal-600 inline-block"></span> Đã làm</span>
                <span class="flex items-center gap-1 pt-2"><span class="h-3 w-3 rounded border border-slate-300 inline-block"></span> Chưa làm</span>
            </div>
        </aside>
    </div>
</div>
@push('scripts')
<script>
(function(){
  const root = document.getElementById('exam-root');
  const el = document.getElementById('exam-timer');
  const timerBar = document.getElementById('exam-timer-bar');
  const form = document.getElementById('exam-form');
  const pe = document.getElementById('proctor_events');
  const ends = el && el.dataset.ends ? new Date(el.dataset.ends) : null;
  const durationMs = (@json((int) $exam->duration_minutes)) * 60000;
  const events = [];
  let blurCount = 0;
  let submitted = false;
  const proctorOn = root && root.dataset.proctor === '1';
  const requireFs = root && root.dataset.requireFs === '1';
  // Chi tinh su kien proctor (blur/rroi tab/thoat fullscreen) SAU KHI nguoi
  // dung thuc su vao phong thi (qua cong fullscreen) - truoc do cac thao tac
  // binh thuong (bam nut, popup xin quyen fullscreen cua trinh duyet lam mat
  // focus...) se bi dem nham va tu nop bai truoc khi kip lam gi.
  let examStarted = !requireFs;
  const autoSubmit = root && root.dataset.autoSubmit === '1';
  const maxBlur = root && root.dataset.maxBlur ? parseInt(root.dataset.maxBlur, 10) : null;
  const heartbeatUrl = @json($heartbeatUrl);
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

  function tick(){
    if(!el||!ends) return;
    const d = ends - new Date();
    if(d<=0){ el.textContent='00:00'; if(timerBar) timerBar.style.width='0%'; doSubmit('timeout'); return; }
    const m=Math.floor(d/60000), s=Math.floor((d%60000)/1000);
    el.textContent=String(m).padStart(2,'0')+':'+String(s).padStart(2,'0');
    if (timerBar && durationMs > 0) {
      const pct = Math.max(0, Math.min(100, (d / durationMs) * 100));
      timerBar.style.width = pct + '%';
    }
    // Cảnh báo màu khi sắp hết giờ (chữ số + thanh tiến trình đổi màu cùng lúc)
    if (d <= 60000) {
      el.classList.remove('text-white');
      el.classList.add('text-rose-100');
      if (timerBar) timerBar.classList.replace('bg-white', 'bg-rose-300');
    } else if (d <= 5 * 60000) {
      el.classList.add('animate-pulse');
      if (timerBar) timerBar.classList.replace('bg-white', 'bg-amber-300');
    }
    setTimeout(tick,400);
  }
  tick();

  function push(t, detail){
    if(!proctorOn || !examStarted || submitted) return;
    events.push({type:t, detail: detail||'', at: new Date().toISOString()});
    if(pe) pe.value = JSON.stringify(events);
    if(['blur','visibility','window_blur','exit_fullscreen','tab_hidden'].indexOf(t)>=0){
      blurCount++;
    }
    const pc = document.getElementById('proctor-count');
    const bc = document.getElementById('blur-count');
    if(pc) pc.textContent = String(events.length);
    if(bc) bc.textContent = String(blurCount);
    // heartbeat batch (fire-and-forget)
    try {
      fetch(heartbeatUrl, {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-TOKEN': csrf||'','Accept':'application/json','X-Requested-With':'XMLHttpRequest'},
        body: JSON.stringify({proctor_events: [{type:t, detail: detail||'', at: new Date().toISOString()}]})
      }).catch(function(){});
    } catch(e){}
    if(autoSubmit && maxBlur && blurCount >= maxBlur){
      doSubmit('max_blur');
    } else if(autoSubmit && (t==='exit_fullscreen' || t==='tab_hidden') && requireFs){
      // soft warn first; hard submit after 2nd exit
      if(blurCount >= 2) doSubmit('leave_fullscreen');
    }
  }

  function doSubmit(reason){
    if(submitted || !form) return;
    submitted = true;
    push('auto_submit', reason||'');
    if(pe) pe.value = JSON.stringify(events);
    // Tu dong nop (het gio / vi pham proctor): nop thang, khong hoi lai -
    // nguoi dung khong con lua chon "Huy" khi da het gio.
    form.requestSubmit ? form.requestSubmit() : form.submit();
  }

  function isFs(){
    return !!(document.fullscreenElement || document.webkitFullscreenElement);
  }

  if(proctorOn){
    document.addEventListener('visibilitychange', function(){
      if(document.hidden) push('tab_hidden','document.hidden');
      else push('focus','visible');
    });
    window.addEventListener('blur', function(){ push('window_blur'); });
    document.addEventListener('fullscreenchange', function(){
      if(!isFs()) push('exit_fullscreen');
      else push('enter_fullscreen');
    });
  }

  // Xac nhan nop bai: hop thoai tu viet rieng cho trang nay (khong dung
  // confirm-modal dung chung toan he thong) - de dam bao chac chan, khong
  // phu thuoc trang thai/code o noi khac cho hanh dong khong the hoan tac.
  (function(){
    const btnSubmit = document.getElementById('btn-submit-exam');
    const confirmBox = document.getElementById('exam-submit-confirm');
    const btnConfirm = document.getElementById('btn-confirm-submit-exam');
    const btnCancel = document.getElementById('btn-cancel-submit-exam');
    if (!btnSubmit || !confirmBox || !btnConfirm || !btnCancel || !form) return;

    btnSubmit.addEventListener('click', function(){
      confirmBox.classList.remove('hidden');
      confirmBox.classList.add('flex');
    });
    btnCancel.addEventListener('click', function(){
      confirmBox.classList.add('hidden');
      confirmBox.classList.remove('flex');
    });
    btnConfirm.addEventListener('click', function(){
      if (submitted) return;
      submitted = true;
      btnConfirm.disabled = true;
      form.requestSubmit ? form.requestSubmit() : form.submit();
    });
  })();

  // Bảng theo dõi câu đã làm / chưa làm
  (function(){
    const navGrid = document.getElementById('exam-nav-grid');
    const navDone = document.getElementById('exam-nav-done');
    const navProgress = document.getElementById('exam-nav-progress');
    const navAside = document.getElementById('exam-nav');
    const header = document.getElementById('exam-header');
    const siteHeader = document.getElementById('lms-header');
    const total = navGrid ? navGrid.querySelectorAll('.exam-nav-item').length : 0;
    if (!navGrid || !form) return;

    // Thanh top-bar cua he thong (#lms-header) cung sticky/top:0 voi z-index
    // rieng - phai lay dung chieu cao that cua no lam "top" cho header bai
    // thi, khong thi header bi che/ket phia sau thanh top-bar khi cuon. Bang
    // nav ben canh cung phai bam ngay duoi header bai thi (khong dinh nhau),
    // nen tinh tiep tu offset cua header - moi thu deu do dong, khong hardcode
    // vi chieu cao cac thanh nay co the doi theo noi dung/breakpoint.
    function syncStickyOffsets(){
      if (!header) return;
      const siteHeaderH = siteHeader ? siteHeader.getBoundingClientRect().height : 0;
      header.style.top = siteHeaderH + 'px';
      if (navAside) {
        const gap = window.innerWidth >= 1024 ? 16 : 0;
        navAside.style.top = (siteHeaderH + header.getBoundingClientRect().height + gap) + 'px';
      }
    }
    syncStickyOffsets();
    window.addEventListener('resize', syncStickyOffsets);

    function isAnswered(questionId){
      const radios = form.querySelectorAll('input[type="radio"][name="answers[' + questionId + ']"]');
      if (radios.length) {
        return Array.from(radios).some(function(r){ return r.checked; });
      }
      const text = form.querySelector('input[type="text"][name="answers[' + questionId + ']"]');
      return !!(text && text.value.trim() !== '');
    }

    function refreshNav(){
      let done = 0;
      navGrid.querySelectorAll('.exam-nav-item').forEach(function(btn){
        const answered = isAnswered(btn.dataset.questionId);
        btn.classList.toggle('bg-teal-600', answered);
        btn.classList.toggle('border-teal-600', answered);
        btn.classList.toggle('text-white', answered);
        btn.classList.toggle('text-slate-500', !answered);
        if (answered) done++;
      });
      if (navDone) navDone.textContent = String(done);
      if (navProgress && total) navProgress.style.width = Math.round((done / total) * 100) + '%';
    }

    navGrid.addEventListener('click', function(e){
      const btn = e.target.closest('.exam-nav-item');
      if (!btn) return;
      const target = document.getElementById(btn.dataset.navTarget);
      if (target) target.scrollIntoView({behavior: 'smooth', block: 'center'});
    });

    form.addEventListener('change', function(e){
      if (e.target.matches('input[type="radio"], input[type="text"]')) refreshNav();
    });
    form.addEventListener('input', function(e){
      if (e.target.matches('input[type="text"]')) refreshNav();
    });

    refreshNav();
  })();

  const btnFs = document.getElementById('btn-enter-fs');
  const gate = document.getElementById('fs-gate');
  if(btnFs){
    btnFs.addEventListener('click', function(){
      const target = document.documentElement;
      const p = target.requestFullscreen ? target.requestFullscreen() : (target.webkitRequestFullscreen && target.webkitRequestFullscreen());
      Promise.resolve(p).then(function(){
        if(gate) gate.classList.add('hidden');
        if(form) form.classList.remove('hidden');
        examStarted = true;
        push('enter_fullscreen','user');
      }).catch(function(){
        window.LmsPopup.error('Trình duyệt chặn fullscreen. Cho phép rồi thử lại.');
      });
    });
  }
})();
</script>
@endpush
@endsection
