@php
    $isAdmin = !empty($layoutAdmin);
    $postUrl = $isAdmin ? route('lms.courses.chat.store', $course) : route('lms.learn.chat.store', $course);
    $pollUrl = $isAdmin ? route('lms.courses.chat.poll', $course) : route('lms.learn.chat.poll', $course);
    $historyUrl = $isAdmin ? route('lms.courses.chat.history', $course) : route('lms.learn.chat.history', $course);
    $back = $isAdmin ? route('lms.courses.show', $course) : route('lms.learn.courses.show', $course);
    $lastId = $messages->last()->id ?? 0;
    $lockedForUser = $course->chat_locked && empty($canModerate);
@endphp

<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <a href="{{ $back }}" class="text-sm text-teal-700 hover:underline">← {{ $course->title }}</a>
    <span class="inline-flex items-center gap-2 rounded-full border border-teal-100 bg-teal-50 px-3 py-1 text-xs font-semibold text-teal-800">
        <span class="h-2 w-2 rounded-full bg-teal-500"></span> Chat khóa học
    </span>
</div>

<div id="lms-chat-room"
     class="{{ $isAdmin ? 'bg-white border rounded-xl shadow-sm' : 'lms-card' }} overflow-hidden"
     data-store="{{ $postUrl }}"
     data-poll="{{ $pollUrl }}"
     data-history="{{ $historyUrl }}"
     data-locked="{{ $lockedForUser ? '1' : '0' }}"
     data-last-id="{{ (int) $lastId }}">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b bg-slate-50 px-4 py-3">
        <div>
            <h1 class="font-bold text-slate-900">Trò chuyện trong {{ $course->title }}</h1>
            <p class="text-xs text-slate-500">Tin chung hoặc nhắn riêng cho thành viên đang hoạt động.</p>
        </div>
        <label class="flex items-center gap-2 text-xs font-semibold text-slate-600">
            Cuộc trò chuyện
            <select id="chat-room-peer" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-normal text-slate-800">
                <option value="">Chat chung khóa học</option>
                @foreach($members as $member)
                    <option value="{{ $member->user_id }}">{{ $member->user->name }} · {{ $member->role }}</option>
                @endforeach
            </select>
        </label>
    </div>

    <div id="chat-scroll" class="h-[min(62vh,36rem)] min-h-80 overflow-y-auto bg-gradient-to-b from-slate-50 to-white p-4 space-y-2 text-sm" style="scroll-behavior: smooth;">
        @foreach($messages as $message)
            <div class="chat-row {{ (int) $message->user_id === (int) auth()->id() ? 'text-right' : '' }}" data-id="{{ $message->id }}">
                <div class="inline-block max-w-[85%] rounded-2xl px-3 py-2 text-left {{ (int) $message->user_id === (int) auth()->id() ? 'bg-teal-600 text-white' : 'bg-slate-200 text-slate-800' }}">
                    @if((int) $message->user_id !== (int) auth()->id())
                        <div class="chat-who text-[10px] opacity-70 mb-0.5">{{ $message->author->name ?? '' }}</div>
                    @endif
                    <div class="chat-body whitespace-pre-wrap">{{ $message->body }}</div>
                    <div class="chat-at text-[10px] opacity-60 mt-0.5">{{ $message->created_at?->format('H:i d/m') }}</div>
                </div>
            </div>
        @endforeach
        @if($messages->isEmpty())
            <p id="chat-room-empty" class="py-12 text-center text-sm text-slate-400">Chưa có tin nhắn.</p>
        @endif
    </div>

    <form id="chat-form" class="border-t bg-white p-3" method="POST" action="{{ $postUrl }}" data-turbo="false">
        @csrf
        <div class="flex gap-2">
            <input type="text" name="body" id="chat-body" required maxlength="2000" autocomplete="off"
                   class="min-w-0 flex-1 rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:ring-teal-500"
                   placeholder="{{ $lockedForUser ? 'Chat đang bị khóa' : 'Nhập tin nhắn…' }}"
                   @disabled($lockedForUser)>
            <button type="submit" id="chat-room-send" class="lms-btn-solid px-4 py-2" @disabled($lockedForUser)>
                <i class="bi bi-send"></i> Gửi
            </button>
        </div>
        <p id="chat-room-status" class="mt-2 min-h-4 text-xs {{ $lockedForUser ? 'text-amber-700' : 'text-slate-500' }}" role="status" aria-live="polite">
            {{ $lockedForUser ? 'Chat đang bị khóa bởi giảng viên / quản trị.' : '' }}
        </p>
    </form>
</div>

@push('scripts')
<script>
(function () {
    function bootStandaloneChat() {
        const room = document.getElementById('lms-chat-room');
        if (!room || room.dataset.bound === '1') return;
        room.dataset.bound = '1';

        const scroll = document.getElementById('chat-scroll');
        const form = document.getElementById('chat-form');
        const input = document.getElementById('chat-body');
        const send = document.getElementById('chat-room-send');
        const peer = document.getElementById('chat-room-peer');
        const status = document.getElementById('chat-room-status');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        let afterId = Number(room.dataset.lastId) || 0;
        let blockedUntil = 0;

        function setStatus(message, error = false) {
            if (!status) return;
            status.textContent = message || '';
            status.className = 'mt-2 min-h-4 text-xs ' + (error ? 'text-rose-600' : 'text-slate-500');
        }

        function scrollBottom() {
            if (scroll) scroll.scrollTop = scroll.scrollHeight;
        }

        // Sprint 44 / D1 — chỉ tự cuộn khi người dùng đang đứng gần cuối
        // (đọc tin mới nhất). Nếu họ đã kéo lên xem tin cũ, tin mới tới từ
        // poll() không được ép cuộn xuống — mất chỗ đang đọc là bực nhất.
        var NEAR_BOTTOM_THRESHOLD = 80;
        function isNearBottom() {
            if (!scroll) return true;
            return scroll.scrollHeight - scroll.scrollTop - scroll.clientHeight < NEAR_BOTTOM_THRESHOLD;
        }

        function applyState(data) {
            if (!data) return;
            const canSend = data.can_send !== false;
            room.dataset.locked = canSend ? '0' : '1';
            if (input) input.disabled = !canSend;
            if (send) send.disabled = !canSend;
            if (!canSend) setStatus('Chat đang bị khóa bởi giảng viên / quản trị.', true);
        }

        async function jsonRequest(url, options = {}) {
            const response = await fetch(url, {
                credentials: 'same-origin',
                cache: 'no-store',
                ...options,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(options.headers || {}),
                },
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                const error = new Error(data.message || (response.status === 419
                    ? 'Phiên đăng nhập đã hết hạn. Hãy tải lại trang.'
                    : (response.status === 429 || response.status === 468
                        ? 'Chat đang tạm giới hạn kết nối. Hệ thống sẽ tự thử lại.'
                        : 'Không kết nối được dịch vụ chat.')));
                error.status = response.status;
                throw error;
            }
            return data;
        }

        function appendMessage(message, shouldScroll) {
            if (!scroll || !message || !message.id || scroll.querySelector('[data-id="' + message.id + '"]')) return;
            document.getElementById('chat-room-empty')?.remove();
            const row = document.createElement('div');
            row.className = 'chat-row ' + (message.mine ? 'text-right' : '');
            row.dataset.id = message.id;
            const bubble = document.createElement('div');
            bubble.className = 'inline-block max-w-[85%] rounded-2xl px-3 py-2 text-left ' + (message.mine ? 'bg-teal-600 text-white' : 'bg-slate-200 text-slate-800');
            if (!message.mine) {
                const who = document.createElement('div');
                who.className = 'chat-who text-[10px] opacity-70 mb-0.5';
                who.textContent = message.user || '';
                bubble.appendChild(who);
            }
            const body = document.createElement('div');
            body.className = 'chat-body whitespace-pre-wrap';
            body.textContent = message.body || '';
            const at = document.createElement('div');
            at.className = 'chat-at text-[10px] opacity-60 mt-0.5';
            at.textContent = message.at || '';
            bubble.append(body, at);
            row.appendChild(bubble);
            scroll.appendChild(row);
            afterId = Math.max(afterId, Number(message.id) || 0);
            // Mặc định: tự cuộn (tin của mình / tải lại toàn bộ). poll() tự
            // quyết định trước khi gọi hàm này — xem ghi chú isNearBottom().
            if (shouldScroll !== false) scrollBottom();
        }

        async function loadHistory() {
            try {
                setStatus('Đang tải cuộc trò chuyện…');
                const url = new URL(room.dataset.history, window.location.origin);
                if (peer?.value) url.searchParams.set('recipient_user_id', peer.value);
                const data = await jsonRequest(url.toString());
                scroll.innerHTML = '';
                afterId = 0;
                (data.messages || []).forEach(function (m) { appendMessage(m, false); });
                scrollBottom();
                if (!(data.messages || []).length) {
                    scroll.innerHTML = '<p id="chat-room-empty" class="py-12 text-center text-sm text-slate-400">Chưa có tin nhắn.</p>';
                }
                applyState(data);
                setStatus('Đã đồng bộ');
                scrollBottom();
            } catch (error) {
                setStatus(error.message || 'Không tải được cuộc trò chuyện.', true);
            }
        }

        async function poll() {
            if (document.hidden || Date.now() < blockedUntil) return;
            try {
                const url = new URL(room.dataset.poll, window.location.origin);
                url.searchParams.set('after_id', String(afterId));
                if (peer?.value) url.searchParams.set('recipient_user_id', peer.value);
                const data = await jsonRequest(url.toString());
                applyState(data);
                // Chốt "đang gần cuối?" TRƯỚC khi thêm tin — thêm xong thì
                // scrollHeight đổi, kiểm tra sau sẽ luôn sai ra true.
                const stickToBottom = isNearBottom();
                (data.messages || []).forEach(function (m) { appendMessage(m, false); });
                if (stickToBottom) scrollBottom();
            } catch (error) {
                if (error.status === 429 || error.status === 468) blockedUntil = Date.now() + 30000;
                setStatus(error.message || 'Mất kết nối chat, đang thử lại…', true);
            }
        }

        form?.addEventListener('submit', async function (event) {
            event.preventDefault();
            const body = (input?.value || '').trim();
            if (!body || room.dataset.locked === '1') return;
            input.value = '';
            send.disabled = true;
            setStatus('Đang gửi…');
            try {
                const payload = { body };
                if (peer?.value) payload.recipient_user_id = Number(peer.value);
                const data = await jsonRequest(room.dataset.store, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify(payload),
                });
                if (!data.ok || !data.message) throw new Error(data.message || 'Không gửi được tin nhắn.');
                appendMessage(data.message);
                setStatus('Đã gửi');
            } catch (error) {
                input.value = body;
                setStatus(error.message || 'Không gửi được tin nhắn.', true);
            } finally {
                send.disabled = room.dataset.locked === '1';
                if (!send.disabled) input.focus();
            }
        });

        peer?.addEventListener('change', loadHistory);
        scrollBottom();
        if (window.__lmsStandaloneChatPoll) clearInterval(window.__lmsStandaloneChatPoll);
        window.__lmsStandaloneChatPoll = setInterval(poll, 5000);
    }

    document.addEventListener('turbo:load', bootStandaloneChat);
    document.addEventListener('DOMContentLoaded', bootStandaloneChat);
    if (document.readyState !== 'loading') bootStandaloneChat();
    document.addEventListener('turbo:before-cache', function () {
        if (window.__lmsStandaloneChatPoll) {
            clearInterval(window.__lmsStandaloneChatPoll);
            window.__lmsStandaloneChatPoll = null;
        }
        const room = document.getElementById('lms-chat-room');
        if (room) room.dataset.bound = '';
    });
})();
</script>
@endpush
