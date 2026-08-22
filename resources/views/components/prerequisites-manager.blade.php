@props([
    'label' => 'Điều kiện tiên quyết',
    'name' => 'prerequisites',
    'values' => [],
    'placeholder' => 'Nhập điều kiện tiên quyết...'
])

@php
    $prerequisites = old($name, $values);
    if (empty($prerequisites)) {
        $prerequisites = [''];
    }
@endphp

<div>
    <label class="block text-sm font-medium text-gray-700 mb-2">{{ $label }}</label>

    <div id="{{ $name }}-container">
        @foreach($prerequisites as $index => $prerequisite)
            <div class="prerequisite-item flex mb-2">
                <input type="text"
                       class="flex-1 px-3 py-2 border border-gray-300 rounded-l-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       name="{{ $name }}[]"
                       value="{{ is_array($prerequisite) ? implode(', ', $prerequisite) : $prerequisite }}"
                       placeholder="{{ $placeholder }}">
                <button type="button" class="px-3 py-2 bg-red-600 text-white rounded-r-lg hover:bg-red-700 remove-prerequisite">
                    <i class="bi bi-x"></i>
                </button>
            </div>
        @endforeach
    </div>

    <button type="button" class="mt-2 bg-blue-100 text-blue-700 px-3 py-1 rounded-lg text-sm hover:bg-blue-200" id="add-{{ $name }}">
        <i class="bi bi-plus mr-1"></i>Thêm điều kiện
    </button>

    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

@push('scripts')
<script>
function bootPrerequisites_{{ str_replace(['-','.'], '_', $name) }}() {
    const container = document.getElementById('{{ $name }}-container');
    const addButton = document.getElementById('add-{{ $name }}');
    if (!container || !addButton || addButton.dataset.bound === '1') return;
    addButton.dataset.bound = '1';

    addButton.addEventListener('click', function() {
        const item = document.createElement('div');
        item.className = 'prerequisite-item flex mb-2';
        item.innerHTML = `
            <input type="text"
                   class="flex-1 px-3 py-2 border border-gray-300 rounded-l-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                   name="{{ $name }}[]"
                   placeholder="{{ $placeholder }}">
            <button type="button" class="px-3 py-2 bg-red-600 text-white rounded-r-lg hover:bg-red-700 remove-prerequisite">
                <i class="bi bi-x"></i>
            </button>
        `;
        container.appendChild(item);
    });

    container.addEventListener('click', function(e) {
        if (e.target.closest('.remove-prerequisite')) {
            const item = e.target.closest('.prerequisite-item');
            if (container.children.length > 1) {
                item.remove();
            } else {
                item.querySelector('input').value = '';
            }
        }
    });
}
document.addEventListener('turbo:load', bootPrerequisites_{{ str_replace(['-','.'], '_', $name) }});
document.addEventListener('DOMContentLoaded', bootPrerequisites_{{ str_replace(['-','.'], '_', $name) }});
if (document.readyState !== 'loading') {
    bootPrerequisites_{{ str_replace(['-','.'], '_', $name) }}();
}
</script>
@endpush
