@props([
    'icon' => 'bi-inbox',
    'title' => 'Không có dữ liệu',
    'description' => '',
    'action' => null
])

<div class="text-center py-12">
    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-gray-100">
        <i class="{{ $icon }} text-gray-400 text-xl"></i>
    </div>
    <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $title }}</h3>
    @if($description)
        <p class="mt-1 text-sm text-gray-500">{{ $description }}</p>
    @endif
    @if($action)
        <div class="mt-6">
            <a href="{{ $action['url'] }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="bi bi-plus mr-2"></i>
                {{ $action['label'] }}
            </a>
        </div>
    @endif
</div>
