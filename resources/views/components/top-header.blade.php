@props([
    'title' => '',
    'user' => null,
    'notifications' => 0
])

<header class="bg-blue-600 text-white shadow-md">
    <div class="flex items-center justify-between px-6 py-4">
        <h1 class="text-xl font-semibold">{{ $title }}</h1>
        <div class="flex items-center space-x-4">
            {{-- Notifications --}}
            @if($notifications > 0)
                <div class="relative">
                    <button class="relative p-2 rounded-full hover:bg-blue-700">
                        <i class="bi bi-bell text-xl"></i>
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                            {{ $notifications }}
                        </span>
                    </button>
                </div>
            @endif

            {{-- User Menu --}}
            <div class="relative">
                <button class="flex items-center space-x-2 hover:bg-blue-700 px-3 py-2 rounded" onclick="toggleDropdown()">
                    <i class="bi bi-person-circle text-xl"></i>
                    <span>{{ $user ? $user->name : 'Trợ lý' }}</span>
                    <i class="bi bi-chevron-down"></i>
                </button>
                {{-- Dropdown menu --}}
                <div id="userDropdown" class="absolute right-0 top-full mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 hidden">
                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Thông tin tài khoản</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Cài đặt</a>
                    <div class="border-t border-gray-100"></div>
                    @if(Route::has('logout'))
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                Đăng xuất
                            </button>
                        </form>
                    @else
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Đăng xuất</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</header>

@push('scripts')
<script>
// Dropdown toggle functionality
function toggleDropdown() {
    const dropdown = document.getElementById('userDropdown');
    dropdown.classList.toggle('hidden');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('userDropdown');
    const button = event.target.closest('button');

    if (!button || button.getAttribute('onclick') !== 'toggleDropdown()') {
        dropdown.classList.add('hidden');
    }
});
</script>
@endpush
