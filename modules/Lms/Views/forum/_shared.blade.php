@php
    $isAdmin = !empty($layoutAdmin);
    $storeRoute = $isAdmin ? route('lms.courses.forum.store', $course) : route('lms.learn.forum.store', $course);
    $showRoute = fn ($t) => $isAdmin
        ? route('lms.courses.forum.show', [$course, $t])
        : route('lms.learn.forum.show', [$course, $t]);
    $back = $isAdmin ? route('lms.courses.show', $course) : route('lms.learn.courses.show', $course);
@endphp

<div class="mb-4">
    <a href="{{ $back }}" class="text-sm text-blue-600 hover:underline">← {{ $course->title }}</a>
</div>
<h1 class="text-2xl font-bold mb-4">Diễn đàn — {{ $course->title }}</h1>

@can('lms.index')
    <div class="{{ $isAdmin ? 'bg-white border rounded-xl p-4 mb-6 shadow-sm' : 'lms-card p-4 mb-6' }}">
        <h2 class="font-semibold mb-2 text-sm">Chủ đề mới</h2>
        <form method="POST" action="{{ $storeRoute }}" class="space-y-2">
            @csrf
            <input type="text" name="title" required placeholder="Tiêu đề" class="w-full border rounded-lg text-sm px-3 py-2">
            <textarea name="body" required rows="3" placeholder="Nội dung…" class="w-full border rounded-lg text-sm px-3 py-2"></textarea>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium">Đăng</button>
        </form>
    </div>
@endcan

<div class="{{ $isAdmin ? 'bg-white border rounded-xl overflow-hidden shadow-sm' : 'lms-card overflow-hidden' }}">
    <ul class="divide-y">
        @forelse($topics as $topic)
            <li class="px-4 py-3 hover:bg-slate-50">
                <a href="{{ $showRoute($topic) }}" class="no-underline text-inherit block">
                    <div class="font-semibold text-slate-900">
                        @if($topic->is_pinned)<span class="text-amber-600 text-xs mr-1">PIN</span>@endif
                        {{ $topic->title }}
                    </div>
                    <div class="text-xs text-slate-500 mt-1">
                        {{ $topic->author->name ?? 'User' }} · {{ $topic->replies_count }} trả lời
                        @if($topic->last_reply_at) · {{ $topic->last_reply_at->diffForHumans() }} @endif
                    </div>
                </a>
            </li>
        @empty
            <li class="px-4 py-10 text-center text-sm text-slate-500">Chưa có chủ đề.</li>
        @endforelse
    </ul>
    <div class="p-3 border-t">{{ $topics->links() }}</div>
</div>
