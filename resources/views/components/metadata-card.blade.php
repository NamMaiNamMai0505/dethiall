@props([
    'title' => 'Thông tin hệ thống',
    'icon' => 'info',
    'iconColor' => 'gray',
    'data' => []
])

<div class="bg-white rounded-lg shadow-sm border">
    <div class="px-4 py-3 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">
            <i class="bi bi-{{ $icon }} text-{{ $iconColor }}-500 mr-2"></i>
            {{ $title }}
        </h3>
    </div>
    <div class="p-4">
        <div class="space-y-3 text-sm">
            @foreach($data as $item)
                <div class="flex justify-between">
                    <span class="text-gray-500">{{ $item['label'] }}:</span>
                    <span class="font-medium">
                        @if(isset($item['component']))
                            {!! $item['component'] !!}
                        @else
                            {{ $item['value'] }}
                        @endif
                    </span>
                </div>
            @endforeach

            {{-- Additional content --}}
            {{ $slot }}
        </div>
    </div>
</div>
