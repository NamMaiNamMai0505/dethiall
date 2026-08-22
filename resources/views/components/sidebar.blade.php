@props([
    'title' => config('app.name'),
    'menuItems' => []
])

<div class="bg-gray-800 text-white w-64 flex-shrink-0">
    {{-- Logo/Brand --}}
    <div class="p-4 border-b border-gray-700">
        <h1 class="text-lg font-semibold">{{ $title }}</h1>
    </div>

    {{-- Navigation Menu --}}
    <nav class="mt-4">
        <ul class="space-y-1">
            @foreach($menuItems as $item)
                <li>
                    {{-- Main menu item --}}
                    @if(isset($item['submenu']) && count($item['submenu']) > 0)
                        {{-- Menu với submenu --}}
                        <a href="#"
                           class="flex items-center px-4 py-2 text-sm hover:bg-gray-700">
                            @if(isset($item['icon']))
                                <i class="bi bi-{{ $item['icon'] }} mr-3"></i>
                            @endif
                            {{ $item['label'] }}
                        </a>
                        {{-- Submenu --}}
                        <ul class="ml-8 mt-1 space-y-1">
                            @foreach($item['submenu'] as $subItem)
                                <li>
                                    <a href="{{ $subItem['url'] ?? '#' }}"
                                       class="block px-4 py-1 text-sm text-gray-400 hover:text-white {{ isset($subItem['active']) && $subItem['active'] ? 'text-white' : '' }}">
                                        {{ $subItem['label'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        {{-- Menu thông thường --}}
                        <a href="{{ $item['url'] ?? '#' }}"
                           class="flex items-center px-4 py-2 text-sm hover:bg-gray-700 {{ isset($item['active']) && $item['active'] ? 'bg-blue-600' : '' }}">
                            @if(isset($item['icon']))
                                <i class="bi bi-{{ $item['icon'] }} mr-3"></i>
                            @endif
                            {{ $item['label'] }}
                        </a>
                    @endif
                </li>
            @endforeach
        </ul>
    </nav>
</div>
