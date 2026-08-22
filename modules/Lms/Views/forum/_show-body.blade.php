@php
    $isAdmin = !empty($layoutAdmin);
    // Học viên: back về phòng học tab diễn đàn (không kẹt ở list forum riêng)
    $listRoute = $isAdmin
        ? route('lms.courses.forum.index', $course)
        : route('lms.learn.courses.show', $course).'?tab=forum';
    $replyRoute = $isAdmin ? route('lms.courses.forum.reply', [$course, $topic]) : route('lms.learn.forum.reply', [$course, $topic]);
@endphp

<div class="mb-4">
    <a href="{{ $listRoute }}" class="text-sm text-teal-700 hover:underline font-medium">
        ← {{ $isAdmin ? 'Diễn đàn' : 'Về phòng học · Diễn đàn' }}
    </a>
</div>

@php
    $canModForum = $isAdmin
        || \Modules\Lms\Support\LmsAccess::canTeachCourse($course)
        || auth()->user()?->can('lms.edit');
    $pinRoute = $isAdmin
        ? route('lms.courses.forum.pin', [$course, $topic])
        : route('lms.learn.forum.pin', [$course, $topic]);
    $lockRoute = $isAdmin
        ? route('lms.courses.forum.lock', [$course, $topic])
        : route('lms.learn.forum.lock', [$course, $topic]);
@endphp
<div class="{{ $isAdmin ? 'bg-white border rounded-xl p-5 shadow-sm mb-4' : 'lms-card p-5 mb-4' }}">
    <div class="flex flex-wrap gap-2 justify-between items-start">
        <div class="min-w-0 flex-1">
            <h1 class="text-xl font-bold">
                @if($topic->is_pinned)<span class="text-amber-600 text-sm mr-1"><i class="bi bi-pin-angle-fill"></i></span>@endif
                @if($topic->is_locked)<span class="text-slate-500 text-sm mr-1"><i class="bi bi-lock-fill"></i></span>@endif
                {{ $topic->title }}
            </h1>
            <p class="text-xs text-slate-500 mt-1">{{ $topic->author->name ?? '' }} · {{ $topic->created_at?->format('d/m/Y H:i') }}</p>
        </div>
        @if($canModForum)
            <div class="flex flex-wrap gap-1">
                <form method="POST" action="{{ $pinRoute }}">
                    @csrf
                    <input type="hidden" name="from" value="show">
                    <button class="{{ $isAdmin ? 'px-2 py-1 text-xs border rounded' : 'lms-btn lms-btn-ghost text-xs' }}" style="padding:0.25rem 0.5rem">
                        {{ $topic->is_pinned ? 'Bỏ ghim' : 'Ghim' }}
                    </button>
                </form>
                <form method="POST" action="{{ $lockRoute }}">
                    @csrf
                    <input type="hidden" name="from" value="show">
                    <button class="{{ $isAdmin ? 'px-2 py-1 text-xs border rounded' : 'lms-btn lms-btn-ghost text-xs' }}" style="padding:0.25rem 0.5rem">
                        {{ $topic->is_locked ? 'Mở khóa' : 'Khóa' }}
                    </button>
                </form>
            </div>
        @endif
    </div>
    <div class="mt-3 text-sm whitespace-pre-wrap text-slate-800">{{ $topic->body }}</div>
</div>

<div class="space-y-3 mb-6">
    @foreach($topic->replies as $reply)
        <div class="{{ $isAdmin ? 'bg-white border rounded-xl p-4 shadow-sm' : 'lms-card p-4' }}">
            <div class="text-xs text-slate-500 mb-1">{{ $reply->author->name ?? '' }} · {{ $reply->created_at?->format('d/m/Y H:i') }}</div>
            <div class="text-sm whitespace-pre-wrap">{{ $reply->body }}</div>
        </div>
    @endforeach
</div>

@if(!$topic->is_locked || $canModForum)
    <form method="POST" action="{{ $replyRoute }}" class="{{ $isAdmin ? 'bg-white border rounded-xl p-4 shadow-sm' : 'lms-card p-4' }}">
        @csrf
        <textarea name="body" required rows="3" class="w-full border rounded-lg text-sm px-3 py-2" placeholder="Trả lời…"></textarea>
        <button type="submit" class="mt-2 lms-btn-solid" style="padding:0.5rem 1rem;font-size:0.875rem">
            <i class="bi bi-send"></i> Gửi trả lời
        </button>
    </form>
@endif
