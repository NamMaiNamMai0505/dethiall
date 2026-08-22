@extends(request()->is('lms/hoc*') || request()->is('lms/gv*') ? 'layouts.lms-learner' : 'layouts.admin')

@section('title', 'Không có quyền · LMS')
@section('page-title', 'Không có quyền')

@section('content')
    <div class="max-w-lg mx-auto {{ request()->is('lms/hoc*') || request()->is('lms/gv*') ? 'py-16 px-4' : 'py-12' }}">
        <div class="{{ request()->is('lms/hoc*') || request()->is('lms/gv*') ? 'lms-card' : 'bg-white rounded-xl border shadow-sm' }} p-8 text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-rose-50 text-rose-600">
                <i class="bi bi-shield-lock text-3xl"></i>
            </div>
            <p class="text-xs font-semibold uppercase tracking-wider text-rose-600 mb-1">HTTP 403</p>
            <h1 class="text-xl font-bold text-slate-900 mb-2">Bạn không có quyền truy cập</h1>
            <p class="text-sm text-slate-500 mb-6 leading-relaxed">
                {{ $exception?->getMessage() && $exception->getMessage() !== 'This action is unauthorized.'
                    ? $exception->getMessage()
                    : 'Tài khoản của bạn không được phép mở trang LMS này (khóa không thuộc phạm vi giảng dạy / học tập, hoặc thiếu permission).' }}
            </p>
            <div class="flex flex-wrap justify-center gap-2">
                @auth
                    @if(method_exists(auth()->user(), 'isStudent') && auth()->user()->isStudent())
                        <a href="{{ route('lms.learn.home') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold bg-teal-600 text-white hover:bg-teal-700">
                            Về trang học
                        </a>
                    @elseif(method_exists(auth()->user(), 'isInstructor') && auth()->user()->isInstructor())
                        <a href="{{ route('lms.teach.home') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold bg-teal-600 text-white hover:bg-teal-700">
                            Về portal GV
                        </a>
                    @else
                        <a href="{{ route('lms.hub') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold bg-blue-600 text-white hover:bg-blue-700">
                            Hub LMS
                        </a>
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold border border-slate-200 text-slate-700 hover:bg-slate-50">
                            Dashboard
                        </a>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold bg-blue-600 text-white">
                        Đăng nhập
                    </a>
                @endauth
            </div>
        </div>
    </div>
@endsection
