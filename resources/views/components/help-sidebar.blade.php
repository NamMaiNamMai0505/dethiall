@props([
    'title' => 'Hướng dẫn',
    'tips' => [],
    'warnings' => [],
    'icon' => 'lightbulb',
    'iconColor' => 'yellow'
])

<div class="bg-white rounded-lg shadow-sm border">
    <div class="px-4 py-3 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">
            <i class="bi bi-{{ $icon }} text-{{ $iconColor }}-500 mr-2"></i>
            {{ $title }}
        </h3>
    </div>
    <div class="p-4">
        @if(count($tips) > 0)
            <div class="bg-blue-50 p-3 rounded-lg mb-4">
                <h4 class="text-sm font-medium text-blue-800 mb-2">
                    <i class="bi bi-info-circle mr-1"></i>Mẹo:
                </h4>
                <ul class="text-sm text-blue-700 space-y-1">
                    @foreach($tips as $tip)
                        <li>• {{ $tip }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(count($warnings) > 0)
            <div class="bg-yellow-50 p-3 rounded-lg">
                <h4 class="text-sm font-medium text-yellow-800 mb-2">
                    <i class="bi bi-exclamation-triangle mr-1"></i>Lưu ý:
                </h4>
                <ul class="text-sm text-yellow-700 space-y-1">
                    @foreach($warnings as $warning)
                        <li>• {{ $warning }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Additional content --}}
        {{ $slot }}
    </div>
</div>
