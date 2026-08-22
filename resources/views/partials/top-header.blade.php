@props(['title' => 'Hệ thống quản lý Đào tạo'])

@php
    // Inline Blade sections are already HTML-escaped by View::startSection().
    // Normalize once before the escaped output below to avoid displaying "&amp;".
    $displayTitle = html_entity_decode((string) $title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
@endphp

<header class="admin-top-header text-white shadow-md relative z-[200]">
    <div class="flex items-center justify-between gap-3 px-4 sm:px-6 py-3 sm:py-4 flex-wrap">
        <h1 class="text-lg sm:text-xl font-semibold shrink-0">{{ $displayTitle }}</h1>
        <div class="flex items-center flex-wrap gap-2 sm:gap-3 relative z-[210] ml-auto">
            {{-- Notifications --}}
            <div class="relative" id="notification-root">
                <button type="button"
                        data-notification-trigger
                        class="header-action-btn relative p-2 rounded-full"
                        aria-label="Thông báo"
                        id="notification-bell">
                    <i class="bi bi-bell text-xl"></i>
                    <span id="notification-badge"
                          data-count="0"
                          class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full min-w-5 h-5 px-1 flex items-center justify-center font-semibold leading-none"></span>
                </button>
                <div id="notification-panel"
                     class="absolute right-0 top-full mt-2 rounded-xl shadow-xl z-[300] hidden ui-dropdown overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200/60 bg-white/50 backdrop-blur-md">
                        <div class="flex items-center gap-2 text-gray-800">
                            <i class="bi bi-bell"></i>
                            <span class="text-sm font-semibold">Thông báo hệ thống</span>
                        </div>
                        <button type="button" id="notification-mark-all"
                                class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                            Đánh dấu đã đọc
                        </button>
                    </div>
                    <div id="notification-list" class="notification-list"></div>
                </div>
            </div>

            {{-- User Menu --}}
            <div class="relative">
                <button type="button" data-user-menu-trigger
                        class="header-action-btn flex items-center space-x-2 px-3 py-2 rounded"
                        onclick="toggleDropdown()">
                    <i class="bi bi-person-circle text-xl"></i>
                    <span>{{ Auth::user() ? Auth::user()->name : 'Admin' }}</span>
                    <i class="bi bi-chevron-down transition-transform duration-200" data-user-menu-chevron></i>
                </button>
                {{-- Dropdown menu --}}
                <div id="userDropdown" class="absolute right-0 top-full mt-2 w-48 rounded-xl shadow-lg py-1 z-[300] hidden ui-dropdown" data-panel-width="12rem">
                    <a href="{{ route('profile') }}"
                       class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="bi bi-person"></i>
                        <span>Thông tin tài khoản</span>
                    </a>
                    @if(Route::has('settings.dashboard'))
                        <a href="{{ route('settings.dashboard') }}"
                           class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('settings.dashboard') ? 'bg-blue-50 text-blue-700 font-semibold' : '' }}">
                            <i class="bi bi-gear"></i>
                            <span>Cài đặt</span>
                        </a>
                    @endif
                    <div class="border-t border-gray-100"></div>
                    @if(Auth::check())
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            Đăng xuất
                        </button>
                    </form>
                    @else
                    <a href="{{ route('login') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Đăng nhập</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</header>
