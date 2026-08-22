@props(['title' => 'Hướng dẫn cấp độ'])

<div class="bg-white rounded-lg shadow-sm border">
    <div class="px-4 py-3 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">{{ $title }}</h3>
    </div>
    <div class="p-4">
        <div class="space-y-3 text-sm">
            <div class="flex items-center">
                <x-level-badge level="beginner" text="Cơ bản" />
                <span class="text-gray-600 ml-3">Dành cho người mới bắt đầu</span>
            </div>
            <div class="flex items-center">
                <x-level-badge level="intermediate" text="Trung cấp" />
                <span class="text-gray-600 ml-3">Có kiến thức nền tảng cơ bản</span>
            </div>
            <div class="flex items-center">
                <x-level-badge level="advanced" text="Nâng cao" />
                <span class="text-gray-600 ml-3">Có kinh nghiệm và kỹ năng tốt</span>
            </div>
            <div class="flex items-center">
                <x-level-badge level="expert" text="Chuyên gia" />
                <span class="text-gray-600 ml-3">Trình độ cao, có thể đào tạo người khác</span>
            </div>
        </div>
    </div>
</div>
